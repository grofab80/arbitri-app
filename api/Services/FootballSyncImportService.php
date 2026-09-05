<?php
namespace Api\Services;

use Api\Models\ExternalMapping;
use Api\Models\ExternalSource;
use Api\Models\ExternalSyncOverride;
use Api\Models\FootballSyncImport;
use Api\Models\SyncRun;

class FootballSyncImportService {

    private const EPOCH_CURSOR = '1970-01-01T00:00:00Z';
    private const ENTITY_ORDER = ['seasons', 'competitions', 'stadiums', 'teams', 'referees', 'matches'];

    public static function testConnection(array $source): array
    {
        $info = self::client($source)->info();
        $data = $info['data'] ?? [];

        if (($data['database'] ?? '') !== 'OK' || empty($data['incremental_sync'])) {
            throw new \RuntimeException('Il plugin remoto non e pronto per lo sync incrementale.');
        }

        return $data;
    }

    public static function run(array $source, string $mode, ?int $userId): array
    {
        if (empty($source['enabled'])) {
            throw new \RuntimeException('La sorgente WordPress e disabilitata.');
        }
        if (!in_array($mode, ['full', 'incremental'], true)) {
            throw new \InvalidArgumentException('Modalita sync non valida.');
        }
        if (SyncRun::isRunning((int)$source['id'])) {
            throw new \RuntimeException('Una sincronizzazione e gia in esecuzione.');
        }

        $cursorFrom = $mode === 'full'
            ? self::EPOCH_CURSOR
            : ((string)($source['last_sync_cursor'] ?? '') ?: self::EPOCH_CURSOR);
        $runId = SyncRun::start((int)$source['id'], $mode, $userId, $cursorFrom);
        $counts = self::emptyCounts();
        $cursorTo = null;

        try {
            $client = self::client($source);
            $changesResponse = $client->changes($cursorFrom, true);
            $cursorTo = (string)($changesResponse['indexed_at'] ?? '');
            if ($cursorTo === '') {
                throw new \RuntimeException('La sorgente non ha restituito il nuovo cursore.');
            }

            $changes = is_array($changesResponse['changes'] ?? null)
                ? $changesResponse['changes']
                : [];
            SyncRun::initializeProgress($runId, self::totalChanges($changes));

            foreach (self::ENTITY_ORDER as $entityType) {
                SyncRun::setCurrentEntity($runId, $entityType);
                $entityChanges = is_array($changes[$entityType] ?? null)
                    ? $changes[$entityType]
                    : [];

                $updatedIds = array_values(array_unique(array_map(
                    'strval',
                    (array)($entityChanges['updated'] ?? [])
                )));
                $deletedIds = array_values(array_unique(array_map(
                    'strval',
                    (array)($entityChanges['deleted'] ?? [])
                )));
                $records = $client->records($entityType, $updatedIds);

                foreach ($updatedIds as $externalId) {
                    if (!isset($records[$externalId])) {
                        SyncRun::addItem(
                            $runId,
                            $entityType,
                            $externalId,
                            null,
                            'failed',
                            'Record indicato da /sync ma non restituito dall endpoint entita.'
                        );
                        $counts['failed']++;
                        SyncRun::advance($runId);
                        continue;
                    }
                    self::processUpdated(
                        (int)$source['id'],
                        $runId,
                        $entityType,
                        (string)$externalId,
                        $records[$externalId],
                        $counts
                    );
                    SyncRun::advance($runId);
                }

                foreach ($deletedIds as $externalId) {
                    $localId = ExternalMapping::markDeleted(
                        (int)$source['id'],
                        $entityType,
                        (string)$externalId
                    );
                    SyncRun::addItem(
                        $runId,
                        $entityType,
                        (string)$externalId,
                        $localId,
                        'deleted_at_source',
                        'Record non piu presente nella sorgente; dato locale conservato.'
                    );
                    $counts['deleted_at_source']++;
                    SyncRun::advance($runId);
                }
            }

            $status = $counts['failed'] > 0 ? 'partial' : 'success';
            $message = self::summaryMessage($counts);
            SyncRun::finish($runId, $status, $cursorTo, $counts, $message);

            if ($status === 'success') {
                ExternalSource::recordStatus((int)$source['id'], 'success', $message, $cursorTo);
            } else {
                // Do not advance the cursor: failed records must be retried.
                ExternalSource::recordStatus((int)$source['id'], 'partial', $message);
            }

            OperationalNotificationService::syncCurrentSeasonSafely();

            return [
                'run_id' => $runId,
                'status' => $status,
                'cursor_from' => $cursorFrom,
                'cursor_to' => $cursorTo,
                'counts' => $counts,
                'message' => $message
            ];
        } catch (\Throwable $e) {
            $counts['failed']++;
            SyncRun::finish($runId, 'failed', $cursorTo, $counts, $e->getMessage());
            ExternalSource::recordStatus((int)$source['id'], 'failed', $e->getMessage());
            throw $e;
        }
    }

    private static function processUpdated(
        int $sourceId,
        int $runId,
        string $entityType,
        string $externalId,
        array $record,
        array &$counts
    ): void {
        try {
            $recordExternalId = (string)($record['external_id'] ?? '');
            if ($recordExternalId === '' || $recordExternalId !== $externalId) {
                throw new \RuntimeException('ID esterno della risposta non coerente.');
            }

            $override = ExternalSyncOverride::find($sourceId, $entityType, $externalId);
            if ($override && (int)$override['ignored'] === 1) {
                ExternalSyncOverride::clearIssue($sourceId, $entityType, $externalId);
                SyncRun::addItem(
                    $runId,
                    $entityType,
                    $externalId,
                    null,
                    'ignored',
                    'Record escluso manualmente dalla sincronizzazione.'
                );
                $counts['ignored']++;
                return;
            }

            if ($override) {
                $hasManualCorrection = false;
                if ($entityType === 'competitions' && !empty($override['football_type'])) {
                    $record['football_type'] = (string)$override['football_type'];
                    $hasManualCorrection = true;
                }
                if (!empty($override['season_local_id'])) {
                    $record['_sync_override_season_id'] = (int)$override['season_local_id'];
                    $hasManualCorrection = true;
                }
                if ($hasManualCorrection) {
                    unset($record['hash']);
                }
            }

            $mapping = ExternalMapping::find($sourceId, $entityType, $externalId);
            $localId = $mapping ? (int)$mapping['local_id'] : null;
            if ($localId && !FootballSyncImport::exists($entityType, $localId)) {
                $localId = null;
                $mapping = null;
            }

            $externalHash = self::recordHash($record);
            if (
                $mapping
                && empty($mapping['deleted_at_source'])
                && !empty($mapping['external_hash'])
                && hash_equals((string)$mapping['external_hash'], $externalHash)
            ) {
                SyncRun::addItem($runId, $entityType, $externalId, $localId, 'skipped', 'Record invariato.');
                $counts['skipped']++;
                return;
            }

            $result = FootballSyncImport::transaction(
                function () use ($sourceId, $entityType, $externalId, $localId, $record, $externalHash) {
                    $imported = self::importRecord($sourceId, $entityType, $localId, $record);
                    if (!empty($imported['ignored'])) {
                        return $imported;
                    }
                    ExternalMapping::save(
                        $sourceId,
                        $entityType,
                        $externalId,
                        (int)$imported['local_id'],
                        $externalHash
                    );
                    return $imported;
                }
            );

            if (!empty($result['ignored'])) {
                ExternalSyncOverride::clearIssue($sourceId, $entityType, $externalId);
                SyncRun::addItem(
                    $runId,
                    $entityType,
                    $externalId,
                    null,
                    'ignored',
                    $result['message'] ?? 'Record dipendente da un elemento ignorato.'
                );
                $counts['ignored']++;
                return;
            }

            $action = !empty($result['created']) ? 'created' : 'updated';
            SyncRun::addItem(
                $runId,
                $entityType,
                $externalId,
                (int)$result['local_id'],
                $action,
                $result['message'] ?? null
            );
            $counts[$action]++;
            ExternalSyncOverride::clearIssue($sourceId, $entityType, $externalId);
        } catch (\Throwable $e) {
            ExternalSyncOverride::recordIssue(
                $sourceId,
                $entityType,
                $externalId,
                $record,
                $e->getMessage()
            );
            SyncRun::addItem($runId, $entityType, $externalId, null, 'failed', $e->getMessage());
            $counts['failed']++;
        }
    }

    private static function importRecord(int $sourceId, string $entityType, ?int $localId, array $record): array
    {
        switch ($entityType) {
            case 'seasons':
                if (!empty($record['_sync_override_season_id'])) {
                    $seasonId = (int)$record['_sync_override_season_id'];
                    FootballSyncImport::seasonName($seasonId);
                    return [
                        'local_id' => $seasonId,
                        'created' => false,
                        'message' => 'Collegata manualmente a una stagione locale.'
                    ];
                }
                return FootballSyncImport::upsertSeason($localId, $record);

            case 'competitions':
                if (!empty($record['_sync_override_season_id'])) {
                    $seasonId = (int)$record['_sync_override_season_id'];
                    FootballSyncImport::seasonName($seasonId);
                } else {
                    $seasonExternalId = self::firstExternalId(
                        $record['season_external_id'] ?? null,
                        $record['season_external_ids'] ?? []
                    );
                    if (ExternalSyncOverride::isIgnored($sourceId, 'seasons', $seasonExternalId)) {
                        return [
                            'ignored' => true,
                            'message' => 'Competizione esclusa: la stagione collegata e ignorata.'
                        ];
                    }
                    $seasonId = self::requiredMapping($sourceId, 'seasons', $seasonExternalId, 'stagione');
                }
                return FootballSyncImport::upsertCompetition(
                    $localId,
                    $record,
                    $seasonId,
                    FootballSyncImport::seasonName($seasonId)
                );

            case 'stadiums':
                return FootballSyncImport::upsertField($localId, $record);

            case 'teams':
                $competitionIds = [];
                $externalCompetitionIds = (array)($record['competition_external_ids'] ?? []);
                foreach ($externalCompetitionIds as $externalCompetitionId) {
                    if (ExternalSyncOverride::isIgnored($sourceId, 'competitions', $externalCompetitionId)) {
                        continue;
                    }
                    $mappedId = ExternalMapping::localId($sourceId, 'competitions', $externalCompetitionId);
                    if ($mappedId) {
                        $competitionIds[] = $mappedId;
                    }
                }
                if (
                    !$competitionIds
                    && $externalCompetitionIds
                    && self::allIgnored($sourceId, 'competitions', $externalCompetitionIds)
                ) {
                    return [
                        'ignored' => true,
                        'message' => 'Squadra esclusa: tutte le competizioni collegate sono ignorate.'
                    ];
                }
                $fieldId = ExternalMapping::localId(
                    $sourceId,
                    'stadiums',
                    $record['primary_stadium_external_id'] ?? null
                );
                return FootballSyncImport::upsertTeam($localId, $record, $competitionIds, $fieldId);

            case 'referees':
                return FootballSyncImport::upsertReferee($localId, $record);

            case 'matches':
                $competitionExternalId = $record['competition_external_id'] ?? null;
                if (ExternalSyncOverride::isIgnored($sourceId, 'competitions', $competitionExternalId)) {
                    return [
                        'ignored' => true,
                        'message' => 'Partita esclusa: la competizione collegata e ignorata.'
                    ];
                }

                $competitionId = self::requiredMapping(
                    $sourceId,
                    'competitions',
                    $competitionExternalId,
                    'competizione'
                );
                $seasonId = ExternalMapping::localId(
                    $sourceId,
                    'seasons',
                    $record['season_external_id'] ?? null
                );
                if (!$seasonId) {
                    $seasonId = FootballSyncImport::competitionSeasonId($competitionId);
                }
                if (!$seasonId) {
                    throw new \RuntimeException('Mapping stagione non disponibile.');
                }

                $relations = [
                    'season_id' => $seasonId,
                    'competition_id' => $competitionId,
                    'home_team_id' => self::requiredMapping(
                        $sourceId,
                        'teams',
                        $record['home_team_external_id'] ?? null,
                        'squadra casa'
                    ),
                    'away_team_id' => self::requiredMapping(
                        $sourceId,
                        'teams',
                        $record['away_team_external_id'] ?? null,
                        'squadra trasferta'
                    ),
                    'field_id' => ExternalMapping::localId(
                        $sourceId,
                        'stadiums',
                        $record['field_external_id'] ?? null
                    ),
                    'referee_id' => ExternalMapping::localId(
                        $sourceId,
                        'referees',
                        $record['referee_external_id'] ?? null
                    )
                ];
                return FootballSyncImport::upsertMatch($localId, $record, $relations);
        }

        throw new \InvalidArgumentException('Entita import non supportata.');
    }

    private static function allIgnored(int $sourceId, string $entityType, array $externalIds): bool
    {
        foreach ($externalIds as $externalId) {
            if (!ExternalSyncOverride::isIgnored($sourceId, $entityType, $externalId)) {
                return false;
            }
        }
        return true;
    }

    private static function requiredMapping(
        int $sourceId,
        string $entityType,
        $externalId,
        string $label
    ): int {
        $localId = ExternalMapping::localId($sourceId, $entityType, $externalId);
        if (!$localId) {
            throw new \RuntimeException("Mapping $label non disponibile.");
        }
        return $localId;
    }

    private static function firstExternalId($primary, array $fallback)
    {
        if ($primary !== null && $primary !== '') {
            return $primary;
        }
        return $fallback[0] ?? null;
    }

    private static function recordHash(array $record): string
    {
        $hash = strtolower(trim((string)($record['hash'] ?? '')));
        if (preg_match('/^[a-f0-9]{64}$/', $hash)) {
            return $hash;
        }

        unset($record['updated_at'], $record['hash']);
        return hash('sha256', json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private static function client(array $source): FootballSyncGateway
    {
        return new FootballSyncClient(
            (string)$source['base_url'],
            ExternalSource::apiKey($source),
            !empty($source['verify_ssl']),
            (int)$source['request_timeout']
        );
    }

    private static function emptyCounts(): array
    {
        return [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'ignored' => 0,
            'failed' => 0,
            'deleted_at_source' => 0
        ];
    }

    private static function totalChanges(array $changes): int
    {
        $total = 0;
        foreach (self::ENTITY_ORDER as $entityType) {
            $entityChanges = is_array($changes[$entityType] ?? null)
                ? $changes[$entityType]
                : [];
            $updated = array_unique(array_map('strval', (array)($entityChanges['updated'] ?? [])));
            $deleted = array_unique(array_map('strval', (array)($entityChanges['deleted'] ?? [])));
            $total += count($updated) + count($deleted);
        }

        return $total;
    }

    private static function summaryMessage(array $counts): string
    {
        return sprintf(
            'Creati: %d, aggiornati: %d, invariati: %d, ignorati: %d, errori: %d, rimossi alla fonte: %d.',
            $counts['created'],
            $counts['updated'],
            $counts['skipped'],
            $counts['ignored'],
            $counts['failed'],
            $counts['deleted_at_source']
        );
    }
}

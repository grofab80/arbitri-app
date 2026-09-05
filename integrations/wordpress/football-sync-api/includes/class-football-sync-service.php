<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Football_Sync_Service
{
    private const LOCK_OPTION = 'football_sync_refresh_lock';
    private const LOCK_TTL = 600;

    private $catalog;
    private $index;
    private $cache;

    public function __construct(
        Football_Sync_Catalog_Service $catalog,
        Football_Sync_Index_Repository $index,
        Football_Sync_Cache $cache
    ) {
        $this->catalog = $catalog;
        $this->index = $index;
        $this->cache = $cache;
    }

    public function execute($updated_after, $refresh = true)
    {
        if (!$this->index->is_ready()) {
            throw new RuntimeException('Indice sync non disponibile. Verificare l upgrade del plugin.');
        }

        $baseline_created = !$this->index->has_baseline();
        $summary = null;

        if ($refresh || $baseline_created) {
            $summary = $this->refresh_index();
        }

        $indexed_at = $this->index->last_indexed_at();

        return array(
            'generated_at' => ($refresh || $baseline_created) && $indexed_at ? $indexed_at : gmdate('Y-m-d H:i:s'),
            'indexed_at' => $indexed_at,
            'baseline_created' => $baseline_created,
            'refreshed' => (bool) ($refresh || $baseline_created),
            'summary' => $summary,
            'changes' => $this->index->changes_since($updated_after, $this->catalog->entity_types()),
        );
    }

    public function refresh_index()
    {
        $this->acquire_lock();
        $observed_at = $this->next_index_timestamp();
        $summary = array();

        try {
            $this->index->begin();

            foreach ($this->catalog->entity_types() as $entity_type) {
                $records = $this->catalog->all($entity_type);
                $summary[$entity_type] = $this->index->refresh_entity($entity_type, $records, $observed_at);
            }

            $this->index->commit($observed_at);
            $this->cache->clear();

            return $summary;
        } catch (Throwable $exception) {
            $this->index->rollback();
            throw $exception;
        } finally {
            delete_option(self::LOCK_OPTION);
        }
    }

    private function acquire_lock()
    {
        $now = time();
        $locked_at = (int) get_option(self::LOCK_OPTION, 0);

        if ($locked_at > 0 && ($now - $locked_at) > self::LOCK_TTL) {
            delete_option(self::LOCK_OPTION);
        }

        if (!add_option(self::LOCK_OPTION, $now, '', false)) {
            throw new RuntimeException('Sincronizzazione gia in esecuzione.');
        }
    }

    private function next_index_timestamp()
    {
        $now = time();
        $last = $this->index->last_indexed_at();
        $last_timestamp = $last ? strtotime($last . ' UTC') : 0;

        if ($last_timestamp >= $now) {
            $now = $last_timestamp + 1;
        }

        return gmdate('Y-m-d H:i:s', $now);
    }
}

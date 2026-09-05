<?php
namespace Api\Services;

interface FootballSyncGateway {

    public function info(): array;

    public function changes(string $updatedAfter, bool $refresh = true): array;

    public function record(string $entityType, string $externalId): array;

    public function records(string $entityType, array $externalIds): array;
}

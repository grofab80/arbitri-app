<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Football_Sync_Catalog_Service
{
    private $repository;
    private $normalizer;

    public function __construct(Football_Sync_Repository $repository, Football_Sync_Normalizer $normalizer)
    {
        $this->repository = $repository;
        $this->normalizer = $normalizer;
    }

    public function entity_types()
    {
        return array('seasons', 'competitions', 'teams', 'stadiums', 'referees', 'matches');
    }

    public function page($entity_type, array $filters)
    {
        switch ($entity_type) {
            case 'seasons':
                return $this->seasons($filters);
            case 'competitions':
                return $this->competitions($filters);
            case 'teams':
                return $this->teams($filters);
            case 'stadiums':
                return $this->stadiums($filters);
            case 'referees':
                return $this->referees($filters);
            case 'matches':
                return $this->matches($filters);
            default:
                throw new InvalidArgumentException('Entita sync non supportata: ' . $entity_type);
        }
    }

    public function all($entity_type)
    {
        $records = array();
        $page = 1;

        do {
            $filters = $this->default_filters();
            $filters['page'] = $page;
            $filters['limit'] = 500;
            $filters['offset'] = ($page - 1) * 500;
            $result = $this->page($entity_type, $filters);
            if (!$result['records'] && count($records) < (int) $result['total']) {
                throw new RuntimeException('Paginazione incompleta per entita ' . $entity_type);
            }
            $records = array_merge($records, $result['records']);
            $page++;
        } while (count($records) < (int) $result['total']);

        return $records;
    }

    public function default_filters()
    {
        return array(
            'id' => 0,
            'page' => 1,
            'limit' => 100,
            'offset' => 0,
            'competition' => 0,
            'season' => 0,
            'team' => 0,
            'stadium' => 0,
            'referee' => 0,
            'status' => '',
            'date_from' => '',
            'date_to' => '',
        );
    }

    private function seasons(array $filters)
    {
        $records = $this->normalizer->seasons($this->repository->get_season_sources());

        if ($filters['id']) {
            $records = array_values(array_filter($records, static function ($record) use ($filters) {
                return (int) $record['external_id'] === (int) $filters['id'];
            }));
        }

        return $this->paginate($records, $filters);
    }

    private function competitions(array $filters)
    {
        $result = $this->repository->get_competitions($filters);
        $ids = array_map(static function ($row) {
            return (int) $row['competition_id'];
        }, $result['rows']);
        $modified = $this->repository->get_modified_map('competition', $ids);
        $records = array();

        foreach ($result['rows'] as $row) {
            $id = (int) $row['competition_id'];
            $records[] = $this->normalizer->competition($row, isset($modified[$id]) ? $modified[$id] : null);
        }

        return array('records' => $records, 'total' => $result['total']);
    }

    private function teams(array $filters)
    {
        $result = $this->repository->get_teams($filters);
        $ids = array_map(static function ($row) {
            return (int) $row['club_id'];
        }, $result['rows']);
        $competition_map = $this->repository->get_team_competition_map($ids);
        $modified = $this->repository->get_modified_map('team', $ids);
        $records = array();

        foreach ($result['rows'] as $row) {
            $id = (int) $row['club_id'];
            $records[] = $this->normalizer->team(
                $row,
                isset($competition_map[$id]) ? $competition_map[$id] : array(),
                isset($modified[$id]) ? $modified[$id] : null
            );
        }

        return array('records' => $records, 'total' => $result['total']);
    }

    private function stadiums(array $filters)
    {
        $ids = $this->repository->get_stadium_ids();
        if ($filters['id']) {
            $ids = array_values(array_filter($ids, static function ($id) use ($filters) {
                return (int) $id === (int) $filters['id'];
            }));
        }

        $total = count($ids);
        $page_ids = array_slice($ids, $filters['offset'], $filters['limit']);
        $posts = $this->repository->get_stadium_posts($page_ids);
        $records = array();

        foreach ($page_ids as $id) {
            $records[] = $this->normalizer->stadium($id, isset($posts[$id]) ? $posts[$id] : array());
        }

        return array('records' => $records, 'total' => $total);
    }

    private function referees(array $filters)
    {
        $names = $this->repository->get_referee_names();
        $records = array_map(array($this->normalizer, 'referee'), $names);

        if ($filters['id']) {
            $records = array_values(array_filter($records, static function ($record) use ($filters) {
                return (int) $record['external_id'] === (int) $filters['id'];
            }));
        }

        return $this->paginate($records, $filters);
    }

    private function matches(array $filters)
    {
        if ($filters['referee']) {
            $filters['referee_name'] = $this->repository->get_referee_name_by_external_id($filters['referee']);
            if (!$filters['referee_name']) {
                return array('records' => array(), 'total' => 0);
            }
        }

        $result = $this->repository->get_matches($filters);
        $ids = array_map(static function ($row) {
            return (int) $row['match_id'];
        }, $result['rows']);
        $modified = $this->repository->get_modified_map('match', $ids);
        $records = array();

        foreach ($result['rows'] as $row) {
            $id = (int) $row['match_id'];
            $records[] = $this->normalizer->match($row, isset($modified[$id]) ? $modified[$id] : null);
        }

        return array('records' => $records, 'total' => $result['total']);
    }

    private function paginate(array $records, array $filters)
    {
        return array(
            'records' => array_slice($records, $filters['offset'], $filters['limit']),
            'total' => count($records),
        );
    }
}

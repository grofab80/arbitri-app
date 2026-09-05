<?php

use Api\Controllers\AuthController;
use Api\Controllers\MovementController;
use Api\Controllers\CategoryController;
use Api\Controllers\CompetitionController;
use Api\Controllers\CompetitionStandingController;
use Api\Controllers\TeamController;
use Api\Controllers\RefereeController;
use Api\Controllers\RefereeAvailabilityController;
use Api\Controllers\SeasonController;
use Api\Controllers\MatchController;
use Api\Controllers\DashboardController;
use Api\Controllers\BalanceController;
use Api\Controllers\FieldController;
use Api\Controllers\PermissionController;
use Api\Controllers\UserController;
use Api\Controllers\DesignationController;
use Api\Controllers\NotificationController;
use Api\Controllers\FootballSyncController;

return [

    'GET /ping' => [
        'handler' => fn() => Api\V1\Response::ok(['pong' => true]),
        'auth' => false
    ],

    'POST /login' => [
        'handler' => [AuthController::class, 'login'],
        'auth' => false
    ],

    'GET /me' => [
        'handler' => [AuthController::class, 'me'],
        'auth' => true,
        'permissions' => ['auth.me']
    ],

    'GET /movements' => [
        'handler' => [MovementController::class, 'index'],
        'auth' => true,
        'permissions' => ['movements.view']
    ],

    'GET /movements-kpi' => [
        'handler' => [MovementController::class, 'kpi'],
        'auth' => true,
        'permissions' => ['movements.view']
    ],

    'GET /dashboard-kpi' => [
        'handler' => [DashboardController::class, 'kpi'],
        'auth' => true,
        'permissions' => ['dashboard.view']
    ],

    'GET /dashboard-charts' => [
        'handler' => [DashboardController::class, 'charts'],
        'auth' => true,
        'permissions' => ['dashboard.view']
    ],

    'GET /notifications' => [
        'handler' => [NotificationController::class, 'index'],
        'auth' => true,
        'permissions' => ['auth.me']
    ],

    'PUT /notifications-read' => [
        'handler' => [NotificationController::class, 'markRead'],
        'auth' => true,
        'permissions' => ['auth.me']
    ],

    'PUT /notifications-read-all' => [
        'handler' => [NotificationController::class, 'markAllRead'],
        'auth' => true,
        'permissions' => ['auth.me']
    ],

    'GET /balance' => [
        'handler' => [BalanceController::class, 'index'],
        'auth' => true,
        'permissions' => ['balance.view']
    ],

    'GET /balance-season-comparison' => [
        'handler' => [BalanceController::class, 'seasonComparison'],
        'auth' => true,
        'permissions' => ['balance.view']
    ],

    'GET /balance-season-analysis' => [
        'handler' => [BalanceController::class, 'seasonAnalysis'],
        'auth' => true,
        'permissions' => ['balance.view']
    ],

    'GET /balance-export' => [
        'handler' => [BalanceController::class, 'export'],
        'auth' => true,
        'permissions' => ['balance.export']
    ],

    'PUT /balance-closure-approve' => [
        'handler' => [BalanceController::class, 'approveClosure'],
        'auth' => true,
        'roles' => ['admin'],
        'permissions' => ['balance.approve']
    ],

    'PUT /balance-closure-archive' => [
        'handler' => [BalanceController::class, 'archiveClosureMovements'],
        'auth' => true,
        'roles' => ['admin'],
        'permissions' => ['balance.archive']
    ],

    'POST /movements' => [
        'handler' => [MovementController::class, 'store'],
        'auth' => true,
        'roles' => ['admin'],
        'permissions' => ['movements.create']
    ],

    'PUT /movements' => [
        'handler' => [MovementController::class, 'update'],
        'auth' => true,
        'roles' => ['admin'],
        'permissions' => ['movements.edit']
    ],

    'DELETE /movements' => [
        'handler' => [MovementController::class, 'delete'],
        'auth' => true,
        'roles' => ['admin'],
        'permissions' => ['movements.delete']
    ],

    'GET /categories-children' => [
        'handler' => [CategoryController::class, 'children'],
        'auth' => true,
        'permissions' => ['movements.view']
    ],

    'GET /categories-show' => [
        'handler' => [CategoryController::class, 'show'],
        'auth' => true,
        'permissions' => ['movements.view']
    ],

    'GET /categories-tree' => [
        'handler' => [CategoryController::class, 'tree'],
        'auth' => true,
        'permissions' => ['movements.view']
    ],

    'GET /competitions' => [
        'handler' => [CompetitionController::class, 'index'],
        'auth' => true,
        'permissions' => ['competitions.view']
    ],

    'POST /competitions' => [
        'handler' => [CompetitionController::class, 'store'],
        'auth' => true,
        'roles' => ['admin'],
        'permissions' => ['competitions.create']
    ],

    'PUT /competitions' => [
        'handler' => [CompetitionController::class, 'update'],
        'auth' => true,
        'roles' => ['admin'],
        'permissions' => ['competitions.edit']
    ],

    'DELETE /competitions' => [
        'handler' => [CompetitionController::class, 'delete'],
        'auth' => true,
        'roles' => ['admin'],
        'permissions' => ['competitions.delete']
    ],

    'GET /competition-standings' => [
        'handler' => [CompetitionStandingController::class, 'index'],
        'auth' => true,
        'permissions' => ['competitions.view']
    ],

    'PUT /competition-standings' => [
        'handler' => [CompetitionStandingController::class, 'update'],
        'auth' => true,
        'permissions' => ['competitions.standings.manage']
    ],

    'PUT /competition-standings-recalculate' => [
        'handler' => [CompetitionStandingController::class, 'recalculate'],
        'auth' => true,
        'permissions' => ['competitions.standings.manage']
    ],

    'GET /teams' => [
        'handler' => [TeamController::class, 'index'],
        'auth' => true,
        'permissions' => ['teams.view']
    ],

    'POST /teams' => [
        'handler' => [TeamController::class, 'store'],
        'auth' => true,
        'roles' => ['admin'],
        'permissions' => ['teams.create']
    ],

    'PUT /teams' => [
        'handler' => [TeamController::class, 'update'],
        'auth' => true,
        'roles' => ['admin'],
        'permissions' => ['teams.edit']
    ],

    'DELETE /teams' => [
        'handler' => [TeamController::class, 'delete'],
        'auth' => true,
        'roles' => ['admin'],
        'permissions' => ['teams.delete']
    ],

    'GET /referees' => [
        'handler' => [RefereeController::class, 'index'],
        'auth' => true,
        'permissions' => ['referees.view']
    ],

    'GET /referee-candidates' => [
        'handler' => [RefereeController::class, 'candidates'],
        'auth' => true,
        'permissions' => ['matches.view']
    ],

    'POST /referees' => [
        'handler' => [RefereeController::class, 'store'],
        'auth' => true,
        'roles' => ['admin'],
        'permissions' => ['referees.create']
    ],

    'PUT /referees' => [
        'handler' => [RefereeController::class, 'update'],
        'auth' => true,
        'roles' => ['admin'],
        'permissions' => ['referees.edit']
    ],

    'DELETE /referees' => [
        'handler' => [RefereeController::class, 'delete'],
        'auth' => true,
        'roles' => ['admin'],
        'permissions' => ['referees.delete']
    ],

    'PUT /referees-geocode' => [
        'handler' => [RefereeController::class, 'geocode'],
        'auth' => true,
        'roles' => ['admin'],
        'permissions' => ['referees.edit']
    ],

    'GET /referee-availabilities' => [
        'handler' => [RefereeAvailabilityController::class, 'index'],
        'auth' => true,
        'permissions' => ['referee_availabilities.view']
    ],

    'POST /referee-availabilities' => [
        'handler' => [RefereeAvailabilityController::class, 'store'],
        'auth' => true,
        'roles' => ['admin'],
        'permissions' => ['referee_availabilities.create']
    ],

    'PUT /referee-availabilities' => [
        'handler' => [RefereeAvailabilityController::class, 'update'],
        'auth' => true,
        'roles' => ['admin'],
        'permissions' => ['referee_availabilities.edit']
    ],

    'DELETE /referee-availabilities' => [
        'handler' => [RefereeAvailabilityController::class, 'delete'],
        'auth' => true,
        'roles' => ['admin'],
        'permissions' => ['referee_availabilities.delete']
    ],

    'GET /fields' => [
        'handler' => [FieldController::class, 'index'],
        'auth' => true,
        'permissions' => ['fields.view']
    ],

    'POST /fields' => [
        'handler' => [FieldController::class, 'store'],
        'auth' => true,
        'roles' => ['admin'],
        'permissions' => ['fields.create']
    ],

    'PUT /fields' => [
        'handler' => [FieldController::class, 'update'],
        'auth' => true,
        'roles' => ['admin'],
        'permissions' => ['fields.edit']
    ],

    'DELETE /fields' => [
        'handler' => [FieldController::class, 'delete'],
        'auth' => true,
        'roles' => ['admin'],
        'permissions' => ['fields.delete']
    ],

    'PUT /fields-geocode' => [
        'handler' => [FieldController::class, 'geocode'],
        'auth' => true,
        'roles' => ['admin'],
        'permissions' => ['fields.edit']
    ],

    'GET /current-season' => [
        'handler' => [SeasonController::class, 'current'],
        'auth' => true
    ],

    'GET /seasons' => [
        'handler' => [SeasonController::class, 'index'],
        'auth' => true,
        'permissions' => ['seasons.view']
    ],

    'POST /seasons' => [
        'handler' => [SeasonController::class, 'store'],
        'auth' => true,
        'roles' => ['admin'],
        'permissions' => ['seasons.create']
    ],

    'PUT /seasons-current' => [
        'handler' => [SeasonController::class, 'setCurrent'],
        'auth' => true,
        'roles' => ['admin'],
        'permissions' => ['seasons.status.change']
    ],

    'PUT /seasons-status' => [
        'handler' => [SeasonController::class, 'setStatus'],
        'auth' => true,
        'roles' => ['admin'],
        'permissions' => ['seasons.status.change']
    ],

    'GET /matches' => [
        'handler' => [MatchController::class, 'index'],
        'auth' => true,
        'permissions' => ['matches.view']
    ],

    'POST /matches' => [
        'handler' => [MatchController::class, 'store'],
        'auth' => true,
        'roles' => ['admin', 'user'],
        'permissions' => ['matches.create']
    ],

    'PUT /matches' => [
        'handler' => [MatchController::class, 'update'],
        'auth' => true,
        'roles' => ['admin', 'user'],
        'permissions' => ['matches.edit']
    ],

    'DELETE /matches' => [
        'handler' => [MatchController::class, 'delete'],
        'auth' => true,
        'roles' => ['admin', 'user'],
        'permissions' => ['matches.delete']
    ],

    'GET /designations' => [
        'handler' => [DesignationController::class, 'index'],
        'auth' => true,
        'permissions' => ['designations.view']
    ],

    'PUT /designations' => [
        'handler' => [DesignationController::class, 'assign'],
        'auth' => true,
        'permissions' => ['designations.edit']
    ],

    'GET /designations/summary' => [
        'handler' => [DesignationController::class, 'summary'],
        'auth' => true,
        'permissions' => ['designations.view']
    ],

    'POST /designations/generate' => [
        'handler' => [DesignationController::class, 'generate'],
        'auth' => true,
        'permissions' => ['designations.generate']
    ],

    'POST /designations/regenerate' => [
        'handler' => [DesignationController::class, 'regenerate'],
        'auth' => true,
        'permissions' => ['designations.generate']
    ],

    'POST /designations/confirm-filtered' => [
        'handler' => [DesignationController::class, 'confirmFiltered'],
        'auth' => true,
        'permissions' => ['designations.confirm']
    ],

    'POST /designations/clear-automatic' => [
        'handler' => [DesignationController::class, 'clearAutomatic'],
        'auth' => true,
        'permissions' => ['designations.generate']
    ],

    'POST /designations/confirm' => [
        'handler' => [DesignationController::class, 'confirm'],
        'auth' => true,
        'permissions' => ['designations.confirm']
    ],

    'GET /designation-blacklist' => [
        'handler' => [DesignationController::class, 'blacklist'],
        'auth' => true,
        'permissions' => ['designations.blacklist.manage']
    ],

    'POST /designation-blacklist' => [
        'handler' => [DesignationController::class, 'storeBlacklist'],
        'auth' => true,
        'permissions' => ['designations.blacklist.manage']
    ],

    'DELETE /designation-blacklist' => [
        'handler' => [DesignationController::class, 'deleteBlacklist'],
        'auth' => true,
        'permissions' => ['designations.blacklist.manage']
    ],

    'GET /permissions' => [
        'handler' => [PermissionController::class, 'index'],
        'auth' => true,
        'permissions' => ['permissions.view']
    ],

    'PUT /profile-permissions' => [
        'handler' => [PermissionController::class, 'syncProfile'],
        'auth' => true,
        'permissions' => ['permissions.manage']
    ],

    'GET /users' => [
        'handler' => [UserController::class, 'index'],
        'auth' => true,
        'permissions' => ['users.view']
    ],

    'POST /users' => [
        'handler' => [UserController::class, 'store'],
        'auth' => true,
        'permissions' => ['users.create']
    ],

    'PUT /users' => [
        'handler' => [UserController::class, 'update'],
        'auth' => true,
        'permissions' => ['users.edit']
    ],

    'DELETE /users' => [
        'handler' => [UserController::class, 'delete'],
        'auth' => true,
        'permissions' => ['users.delete']
    ],

    'PUT /users-profile' => [
        'handler' => [UserController::class, 'updateProfile'],
        'auth' => true,
        'permissions' => ['users.edit']
    ],

    'GET /football-sync/source' => [
        'handler' => [FootballSyncController::class, 'source'],
        'auth' => true,
        'permissions' => ['import.view']
    ],

    'PUT /football-sync/source' => [
        'handler' => [FootballSyncController::class, 'updateSource'],
        'auth' => true,
        'permissions' => ['import.manage']
    ],

    'POST /football-sync/test' => [
        'handler' => [FootballSyncController::class, 'test'],
        'auth' => true,
        'permissions' => ['import.manage']
    ],

    'POST /football-sync/run' => [
        'handler' => [FootballSyncController::class, 'run'],
        'auth' => true,
        'permissions' => ['import.run']
    ],

    'GET /football-sync/runs' => [
        'handler' => [FootballSyncController::class, 'runs'],
        'auth' => true,
        'permissions' => ['import.view']
    ],

    'GET /football-sync/progress' => [
        'handler' => [FootballSyncController::class, 'progress'],
        'auth' => true,
        'permissions' => ['import.view']
    ],

    'GET /football-sync/run-items' => [
        'handler' => [FootballSyncController::class, 'runItems'],
        'auth' => true,
        'permissions' => ['import.view']
    ],

    'GET /football-sync/overrides' => [
        'handler' => [FootballSyncController::class, 'overrides'],
        'auth' => true,
        'permissions' => ['import.view']
    ],

    'PUT /football-sync/overrides' => [
        'handler' => [FootballSyncController::class, 'updateOverrides'],
        'auth' => true,
        'permissions' => ['import.manage']
    ],
];

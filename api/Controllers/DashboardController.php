<?php
namespace Api\Controllers;

use Api\Models\Dashboard;
use Api\V1\Response;

class DashboardController {

    public function kpi(): void
    {
        Response::ok(Dashboard::kpi());
    }

    public function charts(): void
    {
        Response::ok(Dashboard::charts());
    }
}

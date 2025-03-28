<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardDataService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * The dashboard data service.
     *
     * @var DashboardDataService
     */
    protected DashboardDataService $dashboardService;

    /**
     * Create a new controller instance.
     *
     * @param DashboardDataService $dashboardService
     */
    public function __construct(DashboardDataService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Display the order processing dashboard.
     *
     * @return View
     */
    public function index(): View
    {
        // Get dashboard data from service
        $data = $this->dashboardService->getDashboardData();

        // Return view with data
        return view('dashboard', $data);
    }
}

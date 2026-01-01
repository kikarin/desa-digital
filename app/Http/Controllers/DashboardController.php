<?php

namespace App\Http\Controllers;

use App\Repositories\DashboardRepository;
use Illuminate\Http\Request;
use App\Traits\BaseTrait;
use Inertia\Inertia;

class DashboardController extends Controller
{
    use BaseTrait;
    private $repository;
    private $request;

    public function __construct(DashboardRepository $repository, Request $request)
    {
        $this->repository = $repository;
        $this->request    = $request;
        $this->initialize();
        $this->commonData['kode_first_menu'] = $this->kode_menu;
    }

    public function index()
    {
        $statistics = $this->repository->getStatistics();
        $recentActivities = $this->repository->getRecentActivities(5);

        return Inertia::render('Dashboard', [
            'statistics' => $statistics,
            'recentActivities' => $recentActivities,
        ]);
    }
}

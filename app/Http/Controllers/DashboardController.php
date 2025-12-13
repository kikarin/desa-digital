<?php

namespace App\Http\Controllers;

use App\Repositories\DashboardRepository;
use Illuminate\Http\Request;
use App\Traits\BaseTrait;

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
}

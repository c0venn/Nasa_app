<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Providers\NasaProvider;

class ApiController extends Controller
{
    private $nasaProvider;

    public function __construct()
    {
        $this->nasaProvider = new NasaProvider();
    }

    public function index()
    {
        return $this->nasaProvider->GetProjects();
    }
    public function instruments(){
        return $this->nasaProvider->instruments();
    }
    public function activityid(){
        return $this->nasaProvider->activityid();
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Providers\NasaProvider;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    private $nasaProvider;

    public function __construct(NasaProvider $nasaProvider)
    {
        $this->nasaProvider = $nasaProvider;
        $this->middleware('throttle:nasa-api');
    }

    public function index()
    {
        return $this->nasaProvider->GetProjects();
    }

    public function instruments()
    {
        return $this->nasaProvider->instruments();
    }

    public function activityid()
    {
        return $this->nasaProvider->activityid();
    }

    public function instrumentsUse()
    {
        return $this->nasaProvider->instrumentPercentages();
    }

    public function getInstrumentUsage(Request $request)
    {
        $request->validate([
            'instrument' => 'required|string'
        ]);

        return $this->nasaProvider->getInstrumentUsagePercentage($request->input('instrument'));
    }
}

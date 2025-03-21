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

    public function instruments(Request $request)
    {
        return $this->nasaProvider->instruments(
            $request->query('startDate'),
            $request->query('endDate')
        );
    }

    public function activityid(Request $request)
    {
        return $this->nasaProvider->activityid(
            $request->query('startDate'),
            $request->query('endDate')
        );
    }

    public function instrumentsUse(Request $request)
    {
        return $this->nasaProvider->instrumentPercentages(
            $request->query('startDate'),
            $request->query('endDate')
        );
    }

    public function getInstrumentUsage(Request $request)
    {
        $request->validate([
            'instrument' => 'required|string',
            'startDate' => 'nullable|date_format:Y-m-d',
            'endDate' => 'nullable|date_format:Y-m-d'
        ]);

        return $this->nasaProvider->getInstrumentUsagePercentage(
            $request->input('instrument'),
            $request->input('startDate'),
            $request->input('endDate')
        );
    }
}

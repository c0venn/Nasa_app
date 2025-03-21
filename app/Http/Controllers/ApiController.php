<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Contracts\NasaServiceInterface;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    private NasaServiceInterface $nasaService;

    public function __construct(NasaServiceInterface $nasaService)
    {
        $this->nasaService = $nasaService;
    }

    public function nasa()
    {
        return response()->json($this->nasaService->getProjects());
    }

    public function instruments(Request $request)
    {
        return response()->json($this->nasaService->getInstruments(
            $request->query('startDate'),
            $request->query('endDate')
        ));
    }

    public function activityid(Request $request)
    {
        return response()->json($this->nasaService->getActivityIds(
            $request->query('startDate'),
            $request->query('endDate')
        ));
    }

    public function instrumentsUse(Request $request)
    {
        return response()->json($this->nasaService->getInstrumentPercentages(
            $request->query('startDate'),
            $request->query('endDate')
        ));
    }

    public function getInstrumentUsage(Request $request)
    {
        $request->validate([
            'instrument' => 'required|string',
            'startDate' => 'nullable|date_format:Y-m-d',
            'endDate' => 'nullable|date_format:Y-m-d|after_or_equal:startDate'
        ]);

        return response()->json($this->nasaService->getInstrumentUsagePercentage(
            $request->input('instrument'),
            $request->input('startDate'),
            $request->input('endDate')
        ));
    }
}

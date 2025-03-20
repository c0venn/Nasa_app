<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NasaProvider
{
    private const BASE_URL = 'https://api.nasa.gov/DONKI';
    private const API_KEY = '5ZS2xZgePRfpukNoQVkqRKVQQ1bpEE6Ut9JVcflY';
    private const PROJECTS = [
        'CME',
        'SEP', 
        'IPS',
        'FLR',
        'MPC',
        'RBE',
        'HSS',
        'WSAEnlilSimulations'
    ];

    public function GetProjects()
    {
        return self::PROJECTS;
    }

    public function instruments()
    {
        $instruments = [];
        $startDate = "2025-02-01";
        $endDate = "2025-02-31";

        foreach (self::PROJECTS as $service) {
            try {
                $response = Http::withoutVerifying()
                    ->get(self::BASE_URL . '/' . $service, [
                        'api_key' => self::API_KEY,
                        'startDate' => $startDate,
                        'endDate' => $endDate
                    ]);

                if ($response->status() == 200) {
                    $data = $response->json();
                    $instruments[$service] = $this->extractInstrument($data, $service);
                } else {
                    Log::error("Failed to fetch data for service: {$service}");
                    $instruments[$service] = "not found data";
                }
            } catch (\Exception $e) {
                Log::error("Error fetching data for service {$service}: " . $e->getMessage());
            }
        }

        return $instruments;
    }

    private function extractInstrument($data, $service)
    {
        return array_unique(
            array_reduce($data, function($carry, $item) {
                if (isset($item['instruments'])) {
                    foreach ($item['instruments'] as $instrument) {
                        $carry[] = $instrument['displayName'];
                    }
                }
                return $carry;
            }, [])
        );
    }

    public function activityid() {}
    private function extractActivityId() {

    }
}

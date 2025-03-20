<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class NasaProvider
{
    private string $baseUrl;
    private string $apiKey;
    private array $projects;
    private string $startDate;
    private string $endDate;

    public function __construct()
    {
        $this->baseUrl = 'https://api.nasa.gov/DONKI';
        $this->apiKey = Config::get('services.nasa.api_key');
        $this->startDate = "2025-02-01";
        $this->endDate = "2025-02-31";

        $this->projects = [
            '/CME',
            '/HSS',
            '/IPS',
            '/FLR',
            '/SEP',
            '/MPC',
            '/RBE',
            '/WSAEnlilSimulations'
        ];
    }

    public function GetProjects()
    {
        return $this->projects;
    }

    public function instruments()
    {
        $instruments = [];

        $responses = Http::pool(fn ($pool) => 
            collect($this->projects)->map(fn ($service) => 
                $pool->withoutVerifying()
                    ->get($this->baseUrl . $service, [
                        'api_key' => $this->apiKey,
                        'startDate' => $this->startDate,
                        'endDate' => $this->endDate
                    ])
            )
        );

        foreach ($responses as $index => $response) {
            $service = $this->projects[$index];
            try {
                if ($response->status() == 200) {
                    Log::info("Respuesta de servicio: {$service}", ['response' => $response]);
                    $data = $response->json();
                    if (empty($data)) {
                        $instruments[$service] = [
                            'status' => 'no_data',
                            'message' => 'No data available for this period',
                            'instruments' => []
                        ];
                    } else {
                        $instruments[$service] = [
                            'status' => 'success',
                            'message' => 'Data retrieved successfully',
                            'instruments' => $this->extractInstrument($data, $service)
                        ];
                    }
                } else {
                    $instruments[$service] = [
                        'status' => 'error',
                        'message' => "Failed with status: {$response->status()}",
                        'instruments' => []
                    ];
                    Log::error("Failed to fetch data for service: {$service}", [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                }
            } catch (\Exception $e) {
                $instruments[$service] = [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'instruments' => []
                ];
                Log::error("Error processing {$service}: " . $e->getMessage());
            }
        }

        return $instruments;
    }

    private function extractInstrument($data, $service)
    {
        if (!is_array($data)) {
            return [];
        }

        return array_values(array_unique(
            array_reduce($data, function($carry, $item) {
                if (isset($item['instruments']) && is_array($item['instruments'])) {
                    foreach ($item['instruments'] as $instrument) {
                        if (isset($instrument['displayName'])) {
                            $carry[] = $instrument['displayName'];
                        }
                    }
                }
                return $carry;
            }, [])
        ));
    }

    public function activityid() {
        $activityId = [];
        $responses = Http::pool(fn ($pool) => 
            collect($this->projects)->map(fn ($service) => 
                $pool->withoutVerifying()
                    ->get($this->baseUrl . $service, [
                        'api_key' => $this->apiKey, 
                        'startDate' => $this->startDate,
                        'endDate' => $this->endDate
                    ])
            )
        );
        
        

    }
    private function extractActivityId() {

    }
}

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

        foreach ($responses as $index => $response) {
            $service = $this->projects[$index];
            try {
                if ($response->status() == 200) {
                    Log::info("Respuesta de servicio: {$service}", ['response' => $response]);
                    $data = $response->json();  

                    if (empty($data)) {
                        $activityId[$service] = [
                            'status' => 'no_data',
                            'message' => 'No data available for this period',
                            'activityId' => []
                        ];
                    } else {
                        $activityId[$service] = [
                            'status' => 'success',
                            'message' => 'Data retrieved successfully',
                            'activityId' => $this->extractActivityId($data, $service)
                        ];
                    }
                } else {
                    $activityId[$service] = [
                        'status' => 'error',
                        'message' => "Failed with status: {$response->status()}",
                        'activityId' => []
                    ];
                    Log::error("Failed to fetch data for service: {$service}", [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                }
            } catch (\Exception $e) {
                $activityId[$service] = [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'activityId' => []
                ];
                Log::error("Error processing {$service}: " . $e->getMessage());
            }
        }

        return $activityId;
    }

    private function extractActivityId($data, $service) {
        if (!is_array($data)) {
            return [];
        }

        $activityIds = [];
        foreach ($data as $item) {
            if (isset($item['linkedEvents']) && is_array($item['linkedEvents'])) {
                foreach ($item['linkedEvents'] as $event) {
                    if (isset($event['activityID'])) {
                        $parts = explode('-', $event['activityID']);
    
                        if (count($parts) >= 2) {
                            $eventCode = $parts[count($parts) - 2] . '-' . end($parts);
                            $activityIds[$eventCode] = true;
                        }
                    }
                }
            }
        }
    
        return array_keys($activityIds);
    }

    public function instrumentPercentages()
    {
        $allInstruments = $this->instruments();
        $instrumentCounts = [];
        $totalCount = 0;

        foreach ($allInstruments as $service => $data) {
            if ($data['status'] === 'success' && !empty($data['instruments'])) {
                foreach ($data['instruments'] as $instrument) {
                    if (!isset($instrumentCounts[$instrument])) {
                        $instrumentCounts[$instrument] = 0;
                    }
                    $instrumentCounts[$instrument]++;
                    $totalCount++;
                }
            }
        }

        $percentages = [];
        foreach ($instrumentCounts as $instrument => $count) {
            $percentages[$instrument] = round($count / $totalCount, 3);
        }

        arsort($percentages);

        return [
            'status' => 'success',
            'message' => 'Instrument usage percentages calculated successfully',
            'total_appearances' => $totalCount,
            'percentages' => $percentages
        ];
    }

    public function getInstrumentUsagePercentage($instrumentName)
    {
        $allData = $this->instruments();
        $activityCounts = [];
        $totalAppearances = 0;

        foreach ($allData as $service => $data) {
            if ($data['status'] === 'success' && !empty($data['instruments'])) {
                if (in_array($instrumentName, $data['instruments'])) {
                    $response = Http::withoutVerifying()
                        ->get($this->baseUrl . $service, [
                            'api_key' => $this->apiKey,
                            'startDate' => $this->startDate,
                            'endDate' => $this->endDate
                        ]);

                    if ($response->successful()) {
                        $rawData = $response->json();
                        foreach ($rawData as $item) {
                            if (isset($item['instruments']) && is_array($item['instruments'])) {
                                foreach ($item['instruments'] as $instrument) {
                                    if (isset($instrument['displayName']) && $instrument['displayName'] === $instrumentName) {
                                        $activityType = $this->extractActivityType($item, $service);
                                        if (!isset($activityCounts[$activityType])) {
                                            $activityCounts[$activityType] = 0;
                                        }
                                        $activityCounts[$activityType]++;
                                        $totalAppearances++;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $percentages = [];
        foreach ($activityCounts as $activity => $count) {
            $percentages[$activity] = round($count / $totalAppearances, 3);
        }

        return [
            'instrument-Activity' => [
                $instrumentName => $percentages
            ]
        ];
    }

    private function extractActivityType($item, $service)
    {
        $service = ltrim($service, '/');
        
        $idField = strtolower($service) . 'ID';
        if (isset($item[$idField])) {
            $parts = explode('-', $item[$idField]);
            if (count($parts) >= 2) {
                return end($parts);
            }
        }
        
        return $service;
    }

}

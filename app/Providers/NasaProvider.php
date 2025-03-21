<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class NasaProvider
{
    private string $baseUrl;
    private string $apiKey;
    private array $projects;

    public function __construct()
    {
        $this->baseUrl = 'https://api.nasa.gov/DONKI';
        $this->apiKey = Config::get('services.nasa.api_key');
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

    private function validateDates(?string $startDate, ?string $endDate): array
    {
        if (!$startDate) {
            $startDate = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$endDate) {
            $endDate = date('Y-m-d');
        }

        if (!strtotime($startDate) || !strtotime($endDate)) {
            throw new \InvalidArgumentException('Invalid date format. Use YYYY-MM-DD');
        }

        if (strtotime($endDate) < strtotime($startDate)) {
            throw new \InvalidArgumentException('End date must be after start date');
        }

        return [$startDate, $endDate];
    }

    public function GetProjects()
    {
        return $this->projects;
    }

    public function instruments(?string $startDate = null, ?string $endDate = null)
    {
        [$startDate, $endDate] = $this->validateDates($startDate, $endDate);
        
        $responses = Http::pool(fn ($pool) => 
            collect($this->projects)->map(fn ($service) => 
                $pool->withoutVerifying()
                    ->get($this->baseUrl . $service, [
                        'api_key' => $this->apiKey,
                        'startDate' => $startDate,
                        'endDate' => $endDate
                    ])
            )
        );

        $instruments = [];
        foreach ($responses as $index => $response) {
            $service = $this->projects[$index];
            try {
                if ($response->status() === 200) {
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
                }
            } catch (\Exception $e) {
                $instruments[$service] = [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'instruments' => []
                ];
            }
        }

        return $instruments;
    }

    public function activityid(?string $startDate = null, ?string $endDate = null) {
        [$startDate, $endDate] = $this->validateDates($startDate, $endDate);
        
        $activityId = [];
        $responses = Http::pool(fn ($pool) => 
            collect($this->projects)->map(fn ($service) => 
                $pool->withoutVerifying()
                    ->get($this->baseUrl . $service, [
                        'api_key' => $this->apiKey,
                        'startDate' => $startDate,
                        'endDate' => $endDate
                    ])
            )
        );

        foreach ($responses as $index => $response) {
            $service = $this->projects[$index];
            try {
                if ($response->status() === 200) {
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
                }
            } catch (\Exception $e) {
                $activityId[$service] = [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'activityId' => []
                ];
            }
        }

        return $activityId;
    }

    public function instrumentPercentages(?string $startDate = null, ?string $endDate = null)
    {
        [$startDate, $endDate] = $this->validateDates($startDate, $endDate);
        
        $allInstruments = $this->instruments($startDate, $endDate);
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
            'message' => 'Instruments usage percentages calculated successfully',
            'date_range' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'total_appearances' => $totalCount,
            'percentages' => $percentages
        ];
    }

    public function getInstrumentUsagePercentage($instrumentName, ?string $startDate = null, ?string $endDate = null)
    {
        [$startDate, $endDate] = $this->validateDates($startDate, $endDate);
        
        $allData = $this->instruments($startDate, $endDate);
        $relevantServices = [];
        $activityCounts = [];
        $totalAppearances = 0;

        foreach ($allData as $service => $data) {
            if ($data['status'] === 'success' && !empty($data['instruments'])) {
                if (in_array($instrumentName, $data['instruments'])) {
                    $relevantServices[] = $service;
                }
            }
        }

        if (!empty($relevantServices)) {
            $responses = Http::pool(fn ($pool) => 
                collect($relevantServices)->map(fn ($service) => 
                    $pool->withoutVerifying()
                        ->get($this->baseUrl . $service, [
                            'api_key' => $this->apiKey,
                            'startDate' => $startDate,
                            'endDate' => $endDate
                        ])
                )
            );

            foreach ($responses as $index => $response) {
                $service = $relevantServices[$index];
                if ($response->status() === 200) {
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

        $percentages = [];
        if ($totalAppearances > 0) {
            foreach ($activityCounts as $activity => $count) {
                $percentages[$activity] = round($count / $totalAppearances, 3);
            }
        }

        return [
            'status' => 'success',
            'message' => 'Instrument usage percentages calculated successfully',
            'instrument-Activity' => [
                $instrumentName => $percentages
            ],
            'date_range' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        ];
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

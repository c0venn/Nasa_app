<?php

namespace App\Services;

use App\Contracts\NasaServiceInterface;
use App\DTOs\DateRangeDTO;
use Illuminate\Support\Facades\Config;

class NasaService implements NasaServiceInterface
{
    private array $projects;
    private HttpService $httpService;

    public function __construct()
    {
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
        
        $this->httpService = new HttpService(
            'https://api.nasa.gov/DONKI',
            Config::get('services.nasa.api_key')
        );
    }

    public function getProjects(): array
    {
        return $this->projects;
    }

    public function getInstruments(?string $startDate = null, ?string $endDate = null): array
    {
        $dateRange = new DateRangeDTO($startDate, $endDate);
        $responses = $this->httpService->makeParallelRequests($this->projects, $dateRange);

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

    public function getActivityIds(?string $startDate = null, ?string $endDate = null): array
    {
        $dateRange = new DateRangeDTO($startDate, $endDate);
        $responses = $this->httpService->makeParallelRequests($this->projects, $dateRange);

        $activityIds = [];
        foreach ($responses as $index => $response) {
            $service = $this->projects[$index];
            try {
                if ($response->status() === 200) {
                    $data = $response->json();
                    if (empty($data)) {
                        $activityIds[$service] = [
                            'status' => 'no_data',
                            'message' => 'No data available for this period',
                            'activityId' => []
                        ];
                    } else {
                        $activityIds[$service] = [
                            'status' => 'success',
                            'message' => 'Data retrieved successfully',
                            'activityId' => $this->extractActivityId($data, $service)
                        ];
                    }
                } else {
                    $activityIds[$service] = [
                        'status' => 'error',
                        'message' => "Failed with status: {$response->status()}",
                        'activityId' => []
                    ];
                }
            } catch (\Exception $e) {
                $activityIds[$service] = [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'activityId' => []
                ];
            }
        }

        return $activityIds;
    }

    public function getInstrumentPercentages(?string $startDate = null, ?string $endDate = null): array
    {
        $dateRange = new DateRangeDTO($startDate, $endDate);
        $allInstruments = $this->getInstruments($dateRange->getStartDate(), $dateRange->getEndDate());
        
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
                'start_date' => $dateRange->getStartDate(),
                'end_date' => $dateRange->getEndDate()
            ],
            'total_appearances' => $totalCount,
            'percentages' => $percentages
        ];
    }

    public function getInstrumentUsagePercentage(string $instrumentName, ?string $startDate = null, ?string $endDate = null): array
    {
        $dateRange = new DateRangeDTO($startDate, $endDate);
        $allData = $this->getInstruments($dateRange->getStartDate(), $dateRange->getEndDate());
        
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
            $responses = $this->httpService->makeParallelRequests($relevantServices, $dateRange);

            foreach ($responses as $index => $response) {
                $service = $relevantServices[$index];
                if ($response->status() === 200) {
                    $rawData = $response->json();
                    foreach ($rawData as $item) {
                        if (isset($item['instruments']) && is_array($item['instruments'])) {
                            foreach ($item['instruments'] as $instrument) {
                                if (isset($instrument['displayName']) && $instrument['displayName'] === $instrumentName) {
                                    $activityId = $this->extractActivityIdFromItem($item, $service);
                                    if (!isset($activityCounts[$activityId])) {
                                        $activityCounts[$activityId] = 0;
                                    }
                                    $activityCounts[$activityId]++;
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
                'start_date' => $dateRange->getStartDate(),
                'end_date' => $dateRange->getEndDate()
            ]
        ];
    }

    private function extractInstrument($data, $service): array
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

    private function extractActivityId($data, $service): array
    {
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

    private function extractActivityIdFromItem($item, $service): string
    {
        $service = ltrim($service, '/');
        
        if (isset($item['linkedEvents']) && is_array($item['linkedEvents'])) {
            foreach ($item['linkedEvents'] as $event) {
                if (isset($event['activityID'])) {
                    $parts = explode('-', $event['activityID']);
                    if (count($parts) >= 2) {
                        return end($parts);
                    }
                }
            }
        }
        
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
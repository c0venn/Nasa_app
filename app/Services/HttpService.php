<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\DTOs\DateRangeDTO;

class HttpService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct(string $baseUrl, string $apiKey)
    {
        $this->baseUrl = $baseUrl;
        $this->apiKey = $apiKey;
    }

    public function makeParallelRequests(array $endpoints, DateRangeDTO $dateRange): array
    {
        $responses = Http::pool(fn ($pool) => 
            collect($endpoints)->map(fn ($endpoint) => 
                $pool->withoutVerifying()
                    ->get($this->baseUrl . $endpoint, [
                        'api_key' => $this->apiKey,
                        'startDate' => $dateRange->getStartDate(),
                        'endDate' => $dateRange->getEndDate()
                    ])
            )
        );

        return $responses;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
} 
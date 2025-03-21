<?php

namespace App\Contracts;

interface NasaServiceInterface
{
    public function getProjects(): array;
    public function getInstruments(?string $startDate = null, ?string $endDate = null): array;
    public function getActivityIds(?string $startDate = null, ?string $endDate = null): array;
    public function getInstrumentPercentages(?string $startDate = null, ?string $endDate = null): array;
    public function getInstrumentUsagePercentage(string $instrumentName, ?string $startDate = null, ?string $endDate = null): array;
} 
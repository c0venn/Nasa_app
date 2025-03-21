<?php

namespace App\DTOs;

class DateRangeDTO
{
    private string $startDate;
    private string $endDate;

    public function __construct(?string $startDate = null, ?string $endDate = null)
    {
        $this->validateAndSetDates($startDate, $endDate);
    }

    private function validateAndSetDates(?string $startDate, ?string $endDate): void
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

        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function getStartDate(): string
    {
        return $this->startDate;
    }

    public function getEndDate(): string
    {
        return $this->endDate;
    }
} 
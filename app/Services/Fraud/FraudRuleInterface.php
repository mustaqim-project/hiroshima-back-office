<?php

namespace App\Services\Fraud;

use Carbon\CarbonInterface;

/**
 * @req FSD-07, FSD-08, FSD-09, FSD-10, FSD-11, FSD-12, FSD-18, RPT-24
 */
interface FraudRuleInterface
{
    public function getCode(): string;

    public function getName(): string;

    public function getSeverity(): string;

    /**
     * Evaluate rule within time window. Returns list of finding arrays.
     */
    public function evaluate(CarbonInterface $from, CarbonInterface $to): array;
}

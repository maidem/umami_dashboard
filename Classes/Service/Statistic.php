<?php

declare(strict_types=1);

namespace Maidem\UmamiDashboard\Service;

final readonly class Statistic
{
    /**
     * @param array<string, array{pageviews: int, sessions: int}> $byDate
     * @param array<string, int> $countries
     */
    public function __construct(
        public int $visitors,
        public int $pageviews,
        public array $byDate,
        public array $countries,
    ) {}
}

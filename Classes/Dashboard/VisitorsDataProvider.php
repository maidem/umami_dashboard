<?php

declare(strict_types=1);

namespace Maidem\UmamiDashboard\Dashboard;

use Maidem\UmamiDashboard\Service\UmamiStatisticService;
use TYPO3\CMS\Dashboard\Widgets\NumberWithIconDataProviderInterface;

class VisitorsDataProvider implements NumberWithIconDataProviderInterface
{
    public function __construct(private readonly UmamiStatisticService $statisticService) {}

    public function getNumber(): int
    {
        return $this->statisticService->getStatistic()['visitors'] ?? 0;
    }
}

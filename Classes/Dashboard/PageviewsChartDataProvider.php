<?php

declare(strict_types=1);

namespace Maidem\UmamiDashboard\Dashboard;

use Maidem\UmamiDashboard\Service\UmamiStatisticService;
use TYPO3\CMS\Dashboard\Widgets\ChartDataProviderInterface;

class PageviewsChartDataProvider implements ChartDataProviderInterface
{
    public function __construct(private readonly UmamiStatisticService $statisticService) {}

    public function getChartData(): array
    {
        $byDate = $this->statisticService->getStatistic()['byDate'] ?? [];

        return [
            'labels' => array_keys($byDate),
            'datasets' => [
                [
                    'label' => 'Seitenaufrufe',
                    'data' => array_column($byDate, 'pageviews'),
                    'backgroundColor' => '#206bc4',
                ],
                [
                    'label' => 'Besucher',
                    'data' => array_column($byDate, 'sessions'),
                    'backgroundColor' => '#2fb344',
                ],
            ],
        ];
    }
}

<?php

declare(strict_types=1);

namespace Maidem\UmamiDashboard\Dashboard;

use Maidem\UmamiDashboard\Service\UmamiStatisticService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Dashboard\Widgets\ChartDataProviderInterface;

final class PageviewsChartDataProvider implements ChartDataProviderInterface
{
    private const LLL = 'LLL:EXT:umami_dashboard/Resources/Private/Language/locallang.xlf:';

    public function __construct(
        private readonly UmamiStatisticService $statisticService,
        private readonly LanguageServiceFactory $languageServiceFactory,
    ) {}

    /**
     * @return array{labels: list<int|string>, datasets: list<array<string, mixed>>}
     */
    public function getChartData(): array
    {
        $byDate = $this->statisticService->getStatistic()->byDate ?? [];
        $languageService = $this->languageServiceFactory->createForBackendUser();

        return [
            'labels' => array_keys($byDate),
            'datasets' => [
                [
                    'label' => $languageService->sL(self::LLL . 'chart.pageviews'),
                    'data' => array_column($byDate, 'pageviews'),
                    'backgroundColor' => '#206bc4',
                ],
                [
                    'label' => $languageService->sL(self::LLL . 'chart.visitors'),
                    'data' => array_column($byDate, 'sessions'),
                    'backgroundColor' => '#2fb344',
                ],
            ],
        ];
    }
}

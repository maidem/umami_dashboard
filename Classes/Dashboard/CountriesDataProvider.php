<?php

declare(strict_types=1);

namespace Maidem\UmamiDashboard\Dashboard;

use Maidem\UmamiDashboard\Service\UmamiStatisticService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Dashboard\Widgets\ListDataProviderInterface;

final class CountriesDataProvider implements ListDataProviderInterface
{
    private const LIMIT = 10;

    public function __construct(
        private readonly UmamiStatisticService $statisticService,
        private readonly LanguageServiceFactory $languageServiceFactory,
    ) {}

    /**
     * @return list<string>
     */
    public function getItems(): array
    {
        $countries = $this->statisticService->getStatistic()->countries ?? [];
        $language = $this->languageServiceFactory->createForBackendUser()->getLocale()?->getLanguageCode() ?? 'en';
        arsort($countries);

        $items = [];
        foreach (array_slice($countries, 0, self::LIMIT, true) as $code => $visitors) {
            $code = (string)$code;
            $items[] = sprintf('%s %s – %d', $this->flag($code), $this->name($code, $language), $visitors);
        }

        return $items;
    }

    // Regional indicator symbols: "DE" -> 🇩🇪
    private function flag(string $code): string
    {
        if (!preg_match('/^[A-Za-z]{2}$/', $code)) {
            return '🏳️';
        }
        $code = strtoupper($code);

        return mb_chr(0x1F1E6 + ord($code[0]) - 65) . mb_chr(0x1F1E6 + ord($code[1]) - 65);
    }

    private function name(string $code, string $language): string
    {
        return \Locale::getDisplayRegion('-' . $code, $language) ?: $code;
    }
}

<?php

declare(strict_types=1);

namespace Maidem\UmamiDashboard\Service;

use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\RequestFactory;

class UmamiStatisticService
{
    private const DAYS = 14;

    public function __construct(
        private readonly ExtensionConfiguration $extensionConfiguration,
        private readonly RequestFactory $requestFactory,
        private readonly FrontendInterface $cache,
    ) {}

    /**
     * @return array{visitors: int, pageviews: int, byDate: array<string, array{pageviews: int, sessions: int}>, countries: array<string, int>}|null
     */
    public function getStatistic(): ?array
    {
        $cacheIdentifier = 'umami_dashboard_statistic';
        $cached = $this->cache->get($cacheIdentifier);
        if (is_array($cached)) {
            return $cached;
        }

        $config = $this->getConfig();
        if ($config === null) {
            return null;
        }

        try {
            $token = $this->login($config);
            $range = [
                'startAt' => (new \DateTimeImmutable('-' . self::DAYS . ' days'))->getTimestamp() * 1000,
                'endAt' => time() * 1000,
            ];
            $base = $config['host'] . '/api/websites/' . rawurlencode($config['websiteId']);

            $stats = $this->get($token, $base . '/stats', $range);
            $series = $this->get($token, $base . '/pageviews', $range + ['unit' => 'day', 'timezone' => 'Europe/Berlin']);
            $countryRows = $this->get($token, $base . '/metrics', $range + ['type' => 'country']);
        } catch (\Throwable) {
            return null;
        }

        $byDate = [];
        foreach ($series['pageviews'] ?? [] as $point) {
            $byDate[substr((string)$point['x'], 0, 10)]['pageviews'] = (int)$point['y'];
        }
        foreach ($series['sessions'] ?? [] as $point) {
            $byDate[substr((string)$point['x'], 0, 10)]['sessions'] = (int)$point['y'];
        }
        ksort($byDate);

        $countries = [];
        foreach ($countryRows as $row) {
            $countries[(string)$row['x']] = (int)$row['y'];
        }

        $result = [
            // Umami >= 2 returns {value, prev}, older versions return the plain number
            'visitors' => (int)($stats['visitors']['value'] ?? $stats['visitors'] ?? 0),
            'pageviews' => (int)($stats['pageviews']['value'] ?? $stats['pageviews'] ?? 0),
            'byDate' => $byDate,
            'countries' => $countries,
        ];

        // 5min cache, avoids hammering the Umami API on every dashboard reload
        $this->cache->set($cacheIdentifier, $result, [], 300);

        return $result;
    }

    /**
     * Environment variables override the extension configuration, so the same
     * extension can run in several projects without touching settings.php.
     *
     * @return array{host: string, websiteId: string, username: string, password: string}|null
     */
    private function getConfig(): ?array
    {
        $config = $this->extensionConfiguration->get('umami_dashboard');
        $values = [
            'host' => getenv('UMAMI_HOST') ?: ($config['host'] ?? ''),
            'websiteId' => getenv('UMAMI_WEBSITE_ID') ?: ($config['websiteId'] ?? ''),
            'username' => getenv('UMAMI_USERNAME') ?: ($config['username'] ?? ''),
            'password' => getenv('UMAMI_PASSWORD') ?: ($config['password'] ?? ''),
        ];

        if (in_array('', $values, true)) {
            return null;
        }
        $values['host'] = rtrim($values['host'], '/');

        return $values;
    }

    private function login(array $config): string
    {
        $response = $this->requestFactory->request($config['host'] . '/api/auth/login', 'POST', [
            'json' => ['username' => $config['username'], 'password' => $config['password']],
            'timeout' => 5,
        ]);

        return (string)(json_decode((string)$response->getBody(), true, flags: JSON_THROW_ON_ERROR)['token'] ?? '');
    }

    private function get(string $token, string $url, array $query): array
    {
        $response = $this->requestFactory->request($url, 'GET', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
            'query' => $query,
            'timeout' => 5,
        ]);

        return json_decode((string)$response->getBody(), true, flags: JSON_THROW_ON_ERROR);
    }
}

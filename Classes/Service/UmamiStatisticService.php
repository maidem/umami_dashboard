<?php

declare(strict_types=1);

namespace Maidem\UmamiDashboard\Service;

use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\RequestFactory;

final class UmamiStatisticService
{
    private const DAYS = 14;
    private const CACHE_IDENTIFIER = 'umami_dashboard_statistic';
    private const FAILURE_MARKER = 'failed';
    private const LIFETIME_SUCCESS = 300;
    private const LIFETIME_FAILURE = 60;

    public function __construct(
        private readonly ExtensionConfiguration $extensionConfiguration,
        private readonly RequestFactory $requestFactory,
        private readonly FrontendInterface $cache,
        private readonly LoggerInterface $logger,
    ) {}

    public function getStatistic(): ?Statistic
    {
        $cached = $this->cache->get(self::CACHE_IDENTIFIER);
        if ($cached instanceof Statistic) {
            return $cached;
        }
        if ($cached === self::FAILURE_MARKER) {
            // a recent request failed, do not retry on every dashboard reload
            return null;
        }

        $config = $this->getConfig();
        if ($config === null) {
            return null;
        }

        try {
            $token = $config['apiKey'] !== '' ? $config['apiKey'] : $this->login($config);
            $range = [
                'startAt' => (new \DateTimeImmutable('-' . self::DAYS . ' days'))->getTimestamp() * 1000,
                'endAt' => time() * 1000,
            ];
            $base = $config['host'] . '/api/websites/' . rawurlencode($config['websiteId']);

            $stats = $this->get($token, $base . '/stats', $range);
            $series = $this->get($token, $base . '/pageviews', $range + ['unit' => 'day', 'timezone' => date_default_timezone_get()]);
            $countryRows = $this->get($token, $base . '/metrics', $range + ['type' => 'country']);
        } catch (\Throwable $exception) {
            $this->logger->error('Umami statistic request failed: ' . $exception->getMessage(), ['exception' => $exception]);
            $this->cache->set(self::CACHE_IDENTIFIER, self::FAILURE_MARKER, [], self::LIFETIME_FAILURE);
            return null;
        }

        $byDate = [];
        foreach (['pageviews', 'sessions'] as $metric) {
            foreach ($series[$metric] ?? [] as $point) {
                $day = substr((string)$point['x'], 0, 10);
                $byDate[$day] ??= ['pageviews' => 0, 'sessions' => 0];
                $byDate[$day][$metric] = (int)$point['y'];
            }
        }
        ksort($byDate);

        $countries = [];
        foreach ($countryRows as $row) {
            $countries[(string)$row['x']] = (int)$row['y'];
        }

        // Umami >= 2 returns {value, prev}, older versions return the plain number
        $result = new Statistic(
            (int)($stats['visitors']['value'] ?? $stats['visitors'] ?? 0),
            (int)($stats['pageviews']['value'] ?? $stats['pageviews'] ?? 0),
            $byDate,
            $countries,
        );

        $this->cache->set(self::CACHE_IDENTIFIER, $result, [], self::LIFETIME_SUCCESS);

        return $result;
    }

    /**
     * Environment variables override the extension configuration, so the same
     * extension can run in several projects without touching settings.php.
     *
     * @return array{host: string, websiteId: string, apiKey: string, username: string, password: string}|null
     */
    private function getConfig(): ?array
    {
        $config = $this->extensionConfiguration->get('umami_dashboard');
        $values = [
            'host' => getenv('UMAMI_HOST') ?: ($config['host'] ?? ''),
            'websiteId' => getenv('UMAMI_WEBSITE_ID') ?: ($config['websiteId'] ?? ''),
            'apiKey' => getenv('UMAMI_API_KEY') ?: ($config['apiKey'] ?? ''),
            'username' => getenv('UMAMI_USERNAME') ?: ($config['username'] ?? ''),
            'password' => getenv('UMAMI_PASSWORD') ?: ($config['password'] ?? ''),
        ];

        // An API key replaces the username/password login
        $hasCredentials = $values['apiKey'] !== '' || ($values['username'] !== '' && $values['password'] !== '');
        if ($values['host'] === '' || $values['websiteId'] === '' || !$hasCredentials) {
            return null;
        }
        $values['host'] = rtrim($values['host'], '/');

        return $values;
    }

    /**
     * @param array{host: string, username: string, password: string} $config
     */
    private function login(array $config): string
    {
        $response = $this->requestFactory->request($config['host'] . '/api/auth/login', 'POST', [
            'json' => ['username' => $config['username'], 'password' => $config['password']],
            'timeout' => 5,
        ]);

        return (string)(json_decode((string)$response->getBody(), true, flags: JSON_THROW_ON_ERROR)['token'] ?? '');
    }

    /**
     * @param array<string, int|string> $query
     * @return array<mixed>
     */
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

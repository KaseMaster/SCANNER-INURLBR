<?php

declare(strict_types=1);

namespace Inurlbr\Engines;

use Inurlbr\Core\AbstractEngine;
use Inurlbr\Models\Vulnerability;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Google Search Engine implementation
 */
class GoogleEngine extends AbstractEngine
{
    private Client $httpClient;
    private array $dorks = [
        'sql_injection' => [
            'inurl:index.php?id=',
            'inurl:article.php?id=',
            'inurl:product.php?id=',
            'inurl:view_items.php?id=',
            'inurl:home.php?cat='
        ],
        'lfi' => [
            'inurl:include.php?file=',
            'inurl:page.php?file=',
            'inurl:template.php?file=',
            'inurl:path='
        ],
        'xss' => [
            'inurl:search.php?q=',
            'inurl:query.php?text=',
            'inurl:search?query='
        ]
    ];

    protected function initialize(): void
    {
        $this->baseUrl = 'https://www.google.com/search';
        
        $config = [
            'timeout' => $this->timeout,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5'
            ]
        ];

        if (isset($this->config['proxy'])) {
            $config['proxy'] = $this->config['proxy'];
        }

        $this->httpClient = new Client($config);
    }

    protected function validateTarget(string $target): void
    {
        if (empty($target)) {
            throw new \InvalidArgumentException('Target cannot be empty');
        }
    }

    protected function generateQueries(string $target): array
    {
        $queries = [];
        
        // If target is already a dork, use it directly
        if (str_starts_with($target, 'inurl:') || str_starts_with($target, 'site:')) {
            return [$target];
        }

        // Generate dorks for the target domain
        foreach ($this->dorks as $category => $dorkList) {
            foreach ($dorkList as $dork) {
                $queries[] = $dork . ' ' . $target;
            }
        }

        return $queries;
    }

    protected function executeSearch(string $query): string
    {
        $retryCount = 0;
        
        while ($retryCount < $this->maxRetries) {
            try {
                $response = $this->httpClient->get($this->baseUrl, [
                    'query' => ['q' => $query]
                ]);
                
                return (string) $response->getBody();
            } catch (RequestException $e) {
                $retryCount++;
                if ($retryCount >= $this->maxRetries) {
                    throw $e;
                }
                usleep(1000000); // Wait 1 second before retry
            }
        }

        throw new \RuntimeException('Max retries exceeded');
    }

    protected function parseResults(string $rawResults): array
    {
        $urls = [];
        
        // Simple regex to extract URLs from Google search results
        // Note: This is a basic implementation. Production should use proper HTML parsing
        $pattern = '/href="(https?:\/\/[^"]+)"/i';
        preg_match_all($pattern, $rawResults, $matches);
        
        if (isset($matches[1])) {
            $urls = array_unique($matches[1]);
        }

        return $urls;
    }

    protected function checkVulnerability(string $url): ?Vulnerability
    {
        // Basic vulnerability detection based on URL patterns
        $vulnPatterns = [
            'SQL Injection' => [
                'pattern' => '/(\?|=)(id|article|product|item|page)=\d+/i',
                'severity' => 'high',
                'description' => 'Potential SQL Injection point detected'
            ],
            'LFI' => [
                'pattern' => '/(\?|=)(file|page|template|path|include)=/i',
                'severity' => 'critical',
                'description' => 'Potential Local File Inclusion detected'
            ],
            'XSS' => [
                'pattern' => '/(\?|=)(q|query|search|text)=/i',
                'severity' => 'medium',
                'description' => 'Potential XSS injection point detected'
            ]
        ];

        foreach ($vulnPatterns as $type => $info) {
            if (preg_match($info['pattern'], $url)) {
                return new Vulnerability(
                    url: $url,
                    type: $type,
                    severity: $info['severity'],
                    description: $info['description'],
                    evidence: ['url_pattern_match' => true]
                );
            }
        }

        return null;
    }
}

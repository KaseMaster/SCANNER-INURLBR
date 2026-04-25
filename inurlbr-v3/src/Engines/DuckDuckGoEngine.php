<?php

declare(strict_types=1);

namespace Inurlbr\Engines;

use Inurlbr\Models\Vulnerability;

/**
 * DuckDuckGo Search Engine Implementation
 * 
 * Enfocado en privacidad. Requiere manejo especial de User-Agent y headers.
 */
class DuckDuckGoEngine extends AbstractEngine
{
    protected string $name = 'DuckDuckGo';
    protected string $baseUrl = 'https://html.duckduckgo.com/html/';

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        // DDG es estricto con los User-Agents, forzamos uno común si no se define
        if (!isset($this->config['headers']['User-Agent'])) {
            $this->config['headers']['User-Agent'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
        }
    }

    protected function buildSearchUrl(string $dork, int $page): string
    {
        // DDG HTML endpoint acepta POST o GET con q=
        return sprintf(
            '%s?q=%s&s=%d',
            $this->baseUrl,
            urlencode($dork),
            $page * 10
        );
    }

    protected function parseResults(string $html, string $dork): array
    {
        $results = [];

        // DDG HTML usa <a class="result__a" href="...">
        if (preg_match_all('/<a\s+class="result__a"\s+href="([^"]+)"/siU', $html, $matches)) {
            foreach ($matches[1] as $url) {
                // DDG a veces envuelve URLs en su propio redirect
                if (strpos($url, 'uddg=') !== false) {
                    parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $params);
                    $url = $params['uddg'] ?? $url;
                }

                $url = htmlspecialchars_decode($url);

                $results[] = new Vulnerability(
                    url: $url,
                    dork: $dork,
                    engine: $this->name,
                    confidence: 80
                );
            }
        }

        return $results;
    }
}

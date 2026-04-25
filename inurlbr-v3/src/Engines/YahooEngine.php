<?php

declare(strict_types=1);

namespace Inurlbr\Engines;

use Inurlbr\Models\Vulnerability;

/**
 * Yahoo Search Engine Implementation
 * 
 * Yahoo utiliza actualmente los resultados de Bing, pero con un layout diferente.
 */
class YahooEngine extends AbstractEngine
{
    protected string $name = 'Yahoo';
    protected string $baseUrl = 'https://search.yahoo.com/search';

    protected function buildSearchUrl(string $dork, int $page): string
    {
        $pageParam = $page > 0 ? ($page + 1) : 1;
        return sprintf(
            '%s?p=%s&b=%d',
            $this->baseUrl,
            urlencode($dork),
            ($page * 10) + 1
        );
    }

    protected function parseResults(string $html, string $dork): array
    {
        $results = [];

        // Yahoo usa clases como .algo para los contenedores
        if (preg_match_all('/<div\s+class="algo"[^>]*>.*?<div\s+class="hd"[^>]*>.*?<a\s+href="([^"]+)"/siU', $html, $matches)) {
            foreach ($matches[1] as $url) {
                $url = htmlspecialchars_decode($url);

                if (strpos($url, 'yahoo.com') !== false && strpos($url, 'news.yahoo') === false) {
                    continue;
                }

                $results[] = new Vulnerability(
                    url: $url,
                    dork: $dork,
                    engine: $this->name,
                    confidence: 70
                );
            }
        }

        return $results;
    }
}

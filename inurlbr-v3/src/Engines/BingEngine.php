<?php

declare(strict_types=1);

namespace Inurlbr\Engines;

use Inurlbr\Models\Vulnerability;
use GuzzleHttp\Exception\RequestException;

/**
 * Bing Search Engine Implementation
 * 
 * Utiliza scraping controlado para encontrar vulnerabilidades.
 * Nota: En producción se recomienda usar la API oficial de Bing Search.
 */
class BingEngine extends AbstractEngine
{
    protected string $name = 'Bing';
    protected string $baseUrl = 'https://www.bing.com/search';
    
    /**
     * Construye la URL de búsqueda específica para Bing
     */
    protected function buildSearchUrl(string $dork, int $page): string
    {
        $start = ($page * 10) + 1; // Bing usa &first=1, &first=11, etc.
        return sprintf(
            '%s?q=%s&first=%d',
            $this->baseUrl,
            urlencode($dork),
            $start
        );
    }

    /**
     * Parsea el HTML de respuesta de Bing para extraer URLs
     */
    protected function parseResults(string $html, string $dork): array
    {
        $results = [];
        
        // Selector básico para resultados orgánicos de Bing
        if (preg_match_all('/<li\s+class="b_algo"[^>]*>.*?<h2>.*?<a\s+href="([^"]+)"/siU', $html, $matches)) {
            foreach ($matches[1] as $url) {
                $url = htmlspecialchars_decode($url);
                
                // Filtrar URLs internas de Bing
                if (strpos($url, 'bing.com') !== false && strpos($url, 'wikipedia') === false) {
                    continue;
                }

                $results[] = new Vulnerability(
                    url: $url,
                    dork: $dork,
                    engine: $this->name,
                    confidence: 75
                );
            }
        }

        return $results;
    }

    /**
     * Bing tiene límites estrictos, manejamos los errores específicamente
     */
    protected function handleRequestException(RequestException $e, string $dork): void
    {
        if ($e->getResponse() && $e->getResponse()->getStatusCode() === 429) {
            $this->logger->warning("Bing rate limit reached. Cooling down for 60s...");
            sleep(60);
        } else {
            parent::handleRequestException($e, $dork);
        }
    }
}

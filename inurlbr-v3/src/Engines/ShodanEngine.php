<?php

declare(strict_types=1);

namespace Inurlbr\Engines;

use Inurlbr\Models\Vulnerability;

/**
 * Shodan Search Engine Implementation
 * 
 * Especializado en dispositivos IoT y servicios expuestos.
 * Requiere API Key (https://www.shodan.io/api)
 */
class ShodanEngine extends AbstractEngine
{
    protected string $name = 'Shodan';
    protected string $baseUrl = 'https://api.shodan.io/shodan/host/search';

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        
        if (empty($this->config['api_key'])) {
            throw new \InvalidArgumentException('Shodan engine requires an API key. Set SHODAN_API_KEY in environment or config.');
        }
    }

    protected function buildSearchUrl(string $dork, int $page): string
    {
        return sprintf(
            '%s?query=%s&page=%d&key=%s',
            $this->baseUrl,
            urlencode($dork),
            $page + 1,
            $this->config['api_key']
        );
    }

    /**
     * Shodan devuelve JSON, no HTML
     */
    protected function parseResults(string $response, string $dork): array
    {
        $results = [];
        $data = json_decode($response, true);

        if (!isset($data['matches']) || !is_array($data['matches'])) {
            return $results;
        }

        foreach ($data['matches'] as $match) {
            $ip = $match['ip_str'] ?? null;
            $port = $match['port'] ?? null;
            $org = $match['org'] ?? 'Unknown';
            
            if (!$ip) {
                continue;
            }

            // Construimos una URL o identifier único para el host
            $url = sprintf('http://%s:%d', $ip, $port ?? 80);
            
            $results[] = new Vulnerability(
                url: $url,
                dork: $dork,
                engine: $this->name,
                confidence: 90, // Shodan es muy preciso
                metadata: [
                    'ip' => $ip,
                    'port' => $port,
                    'organization' => $org,
                    'product' => $match['product'] ?? null,
                    'version' => $match['version'] ?? null,
                ]
            );
        }

        return $results;
    }

    /**
     * Shodan tiene límites de API estrictos
     */
    protected function handleRequestException(\GuzzleHttp\Exception\RequestException $e, string $dork): void
    {
        $status = $e->getResponse()?->getStatusCode();
        
        if ($status === 401) {
            throw new \RuntimeException('Invalid Shodan API key');
        }
        
        if ($status === 429) {
            $this->logger->warning("Shodan API rate limit reached. Check your plan limits.");
        }
        
        parent::handleRequestException($e, $dork);
    }
}

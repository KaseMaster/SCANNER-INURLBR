<?php

declare(strict_types=1);

namespace Inurlbr\Validators;

use Inurlbr\Contracts\ValidatorInterface;
use Inurlbr\Models\Vulnerability;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

/**
 * Validador de Cross-Site Scripting (XSS).
 * Detecta reflexión de payloads XSS en la respuesta.
 */
class XssValidator implements ValidatorInterface
{
    private Client $client;
    private LoggerInterface $logger;
    
    private array $xssPayloads = [
        '<script>alert(1)</script>',
        '<img src=x onerror=alert(1)>',
        '\'><script>alert(1)</script>',
        '"><script>alert(1)</script>',
        '<svg onload=alert(1)>',
        'javascript:alert(1)',
        '<body onload=alert(1)>',
        '<iframe src="javascript:alert(1)">',
    ];

    public function __construct(Client $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function validate(string $url): ?Vulnerability
    {
        foreach ($this->xssPayloads as $payload) {
            $testUrl = $this->injectPayload($url, $payload);
            
            try {
                $response = $this->client->get($testUrl, [
                    'timeout' => 10,
                    'http_errors' => false,
                ]);

                $content = (string) $response->getBody();
                
                // Verificar si el payload se refleja sin sanitizar
                if ($this->detectReflection($content, $payload)) {
                    $this->logger->info("XSS detectada en: {$testUrl}");
                    
                    return new Vulnerability(
                        url: $testUrl,
                        type: 'Cross-Site Scripting (XSS)',
                        severity: 'MEDIUM',
                        evidence: $this->extractEvidence($content, $payload),
                        payload: htmlspecialchars($payload),
                        confidence: 75
                    );
                }
            } catch (RequestException $e) {
                $this->logger->warning("Error al validar XSS: " . $e->getMessage());
                continue;
            }
        }

        return null;
    }

    public function getType(): string
    {
        return 'Cross-Site Scripting (XSS)';
    }

    /**
     * Inyecta un payload XSS en la URL.
     */
    private function injectPayload(string $url, string $payload): string
    {
        if (strpos($url, '?') !== false) {
            parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $params);
            
            foreach ($params as $key => $value) {
                return str_replace(
                    "{$key}={$value}",
                    "{$key}=" . urlencode($payload),
                    $url
                );
            }
            
            return $url . '&q=' . urlencode($payload);
        }
        
        return $url . '?q=' . urlencode($payload);
    }

    /**
     * Detecta si el payload se refleja en la respuesta.
     */
    private function detectReflection(string $content, string $payload): bool
    {
        // Verificar si partes del payload están presentes sin codificar
        $patterns = [
            '/<script[^>]*>.*?alert\(.*?\).*?<\/script>/i',
            '/onerror\s*=\s*["\']?alert/i',
            '/onload\s*=\s*["\']?alert/i',
            '/<img[^>]*onerror/i',
            '/<svg[^>]*onload/i',
            '/javascript:alert/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        
        // Verificar reflexión simple (payload sin HTML entities)
        $decodedContent = html_entity_decode($content);
        if (stripos($decodedContent, 'alert(1)') !== false) {
            return true;
        }
        
        return false;
    }

    /**
     * Extrae evidencia de XSS del contenido.
     */
    private function extractEvidence(string $content, string $payload): string
    {
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            if (preg_match('/<script[^>]*>.*?alert/i', $line) ||
                preg_match('/on(error|load)\s*=\s*["\']?alert/i', $line) ||
                stripos($line, 'javascript:alert') !== false) {
                return trim(substr(strip_tags($line), 0, 200));
            }
        }
        
        return 'XSS payload reflected in response';
    }
}

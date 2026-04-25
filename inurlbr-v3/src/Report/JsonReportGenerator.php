<?php

declare(strict_types=1);

namespace Inurlbr\Report;

use Inurlbr\Contracts\ReportGeneratorInterface;
use Inurlbr\Models\Vulnerability;

/**
 * Generador de reportes en formato JSON.
 */
class JsonReportGenerator implements ReportGeneratorInterface
{
    public function generate(array $results, array $metadata = []): string
    {
        $report = [
            'scanner' => [
                'name' => 'INURLBR',
                'version' => '3.0',
                'date' => $metadata['date'] ?? date('Y-m-d H:i:s'),
            ],
            'scan_info' => [
                'dork' => $metadata['dork'] ?? null,
                'engines' => $metadata['engines'] ?? null,
                'total_urls_scanned' => $metadata['total_urls'] ?? count($results),
                'vulnerabilities_found' => count($results),
            ],
            'summary' => [
                'by_severity' => [
                    'CRITICAL' => 0,
                    'HIGH' => 0,
                    'MEDIUM' => 0,
                    'LOW' => 0,
                    'INFO' => 0,
                ],
            ],
            'vulnerabilities' => [],
        ];
        
        // Count by severity
        foreach ($results as $vuln) {
            if ($vuln instanceof Vulnerability) {
                $report['summary']['by_severity'][$vuln->severity]++;
                
                $report['vulnerabilities'][] = [
                    'id' => uniqid('vuln_', true),
                    'type' => $vuln->type,
                    'severity' => $vuln->severity,
                    'confidence' => $vuln->confidence,
                    'url' => $vuln->url,
                    'payload' => $vuln->payload,
                    'evidence' => $vuln->evidence,
                    'detected_at' => date('Y-m-d H:i:s'),
                ];
            }
        }
        
        return json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function save(string $content, string $filename, ?string $directory = null): string
    {
        $directory = $directory ?? __DIR__ . '/../../output';
        
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        $filepath = rtrim($directory, '/') . '/' . $filename . '.' . $this->getExtension();
        
        if (file_put_contents($filepath, $content) === false) {
            throw new \RuntimeException("Failed to save report to: {$filepath}");
        }
        
        return $filepath;
    }

    public function getExtension(): string
    {
        return 'json';
    }
}

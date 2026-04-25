<?php

declare(strict_types=1);

namespace Inurlbr\Core;

/**
 * Represents the result of a complete scan operation
 */
class ScanResult
{
    private array $vulnerabilities = [];
    private array $errors = [];
    private int $urlsScanned = 0;
    private float $startTime;
    private float $endTime = 0.0;
    private string $status = 'running';

    public function __construct(
        private string $target,
        private string $engineName
    ) {
        $this->startTime = microtime(true);
    }

    public function addVulnerability(Vulnerability $vulnerability): self
    {
        $this->vulnerabilities[] = $vulnerability;
        return $this;
    }

    public function addError(string $error): self
    {
        $this->errors[] = $error;
        return $this;
    }

    public function incrementUrlsScanned(): self
    {
        $this->urlsScanned++;
        return $this;
    }

    public function markComplete(): self
    {
        $this->endTime = microtime(true);
        $this->status = 'completed';
        return $this;
    }

    public function markFailed(string $error): self
    {
        $this->endTime = microtime(true);
        $this->status = 'failed';
        $this->errors[] = $error;
        return $this;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function getEngineName(): string
    {
        return $this->engineName;
    }

    public function getVulnerabilities(): array
    {
        return $this->vulnerabilities;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getUrlsScanned(): int
    {
        return $this->urlsScanned;
    }

    public function getDuration(): float
    {
        return $this->endTime - $this->startTime;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getSummary(): array
    {
        $severityCount = [
            'critical' => 0,
            'high' => 0,
            'medium' => 0,
            'low' => 0,
            'info' => 0
        ];

        foreach ($this->vulnerabilities as $vuln) {
            $severity = strtolower($vuln->getSeverity());
            if (isset($severityCount[$severity])) {
                $severityCount[$severity]++;
            }
        }

        return [
            'target' => $this->target,
            'engine' => $this->engineName,
            'status' => $this->status,
            'duration_seconds' => round($this->getDuration(), 2),
            'urls_scanned' => $this->urlsScanned,
            'vulnerabilities_found' => count($this->vulnerabilities),
            'by_severity' => $severityCount,
            'errors_count' => count($this->errors)
        ];
    }

    public function toArray(): array
    {
        return [
            'summary' => $this->getSummary(),
            'vulnerabilities' => array_map(fn($v) => $v->toArray(), $this->vulnerabilities),
            'errors' => $this->errors
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }
}

<?php

declare(strict_types=1);

namespace Inurlbr\Core;

/**
 * Abstract base class for all scanner engines.
 * Implements the Template Method pattern for consistent scanning flow.
 */
abstract class AbstractEngine
{
    protected string $name;
    protected string $baseUrl;
    protected array $config;
    protected int $timeout = 30;
    protected int $maxRetries = 3;

    public function __construct(string $name, array $config = [])
    {
        $this->name = $name;
        $this->config = $config;
        $this->initialize();
    }

    /**
     * Initialize engine-specific settings
     */
    protected function initialize(): void
    {
        // Override in child classes if needed
    }

    /**
     * Main scanning entry point
     */
    public function scan(string $target): ScanResult
    {
        $this->validateTarget($target);
        
        $result = new ScanResult($target, $this->name);
        
        try {
            $queries = $this->generateQueries($target);
            
            foreach ($queries as $query) {
                $rawResults = $this->executeSearch($query);
                $parsedResults = $this->parseResults($rawResults);
                
                foreach ($parsedResults as $url) {
                    if ($this->isValidUrl($url)) {
                        $vulnerability = $this->checkVulnerability($url);
                        if ($vulnerability !== null) {
                            $result->addVulnerability($vulnerability);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $result->addError($e->getMessage());
        }
        
        return $result;
    }

    /**
     * Validate target format
     */
    abstract protected function validateTarget(string $target): void;

    /**
     * Generate search queries for the target
     */
    abstract protected function generateQueries(string $target): array;

    /**
     * Execute search and return raw results
     */
    abstract protected function executeSearch(string $query): string;

    /**
     * Parse raw results into URLs
     */
    abstract protected function parseResults(string $rawResults): array;

    /**
     * Check if URL is valid and accessible
     */
    protected function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Check for vulnerabilities on a specific URL
     */
    abstract protected function checkVulnerability(string $url): ?Vulnerability;

    /**
     * Get engine name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set timeout
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Set max retries
     */
    public function setMaxRetries(int $maxRetries): self
    {
        $this->maxRetries = $maxRetries;
        return $this;
    }
}

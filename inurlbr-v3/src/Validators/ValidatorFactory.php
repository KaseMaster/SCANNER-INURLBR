<?php

declare(strict_types=1);

namespace Inurlbr\Validators;

use Inurlbr\Contracts\ValidatorInterface;
use InurlBr\Models\Vulnerability;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

/**
 * Factory para crear validadores.
 */
class ValidatorFactory
{
    private Client $client;
    private LoggerInterface $logger;

    public function __construct(Client $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * Crea un validator por tipo.
     *
     * @param string $type Tipo de validator (sql, lfi, xss).
     * @return ValidatorInterface
     * @throws \InvalidArgumentException Si el tipo no es válido.
     */
    public function create(string $type): ValidatorInterface
    {
        return match(strtolower($type)) {
            'sql', 'sqli', 'sql_injection' => new SqlInjectionValidator($this->client, $this->logger),
            'lfi', 'local_file_inclusion' => new LfiValidator($this->client, $this->logger),
            'xss', 'cross_site_scripting' => new XssValidator($this->client, $this->logger),
            default => throw new \InvalidArgumentException("Invalid validator type: {$type}"),
        };
    }

    /**
     * Crea múltiples validadores.
     *
     * @param array $types Array de tipos de validadores.
     * @return ValidatorInterface[]
     */
    public function createMultiple(array $types): array
    {
        $validators = [];
        
        foreach ($types as $type) {
            try {
                $validators[] = $this->create($type);
            } catch (\InvalidArgumentException $e) {
                $this->logger->warning($e->getMessage());
            }
        }
        
        return $validators;
    }

    /**
     * Retorna todos los validadores disponibles.
     *
     * @return ValidatorInterface[]
     */
    public function createAll(): array
    {
        return [
            $this->create('sql'),
            $this->create('lfi'),
            $this->create('xss'),
        ];
    }

    /**
     * Retorna la lista de tipos de validadores disponibles.
     *
     * @return array
     */
    public static function getAvailableTypes(): array
    {
        return ['sql', 'lfi', 'xss'];
    }
}

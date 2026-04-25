<?php

declare(strict_types=1);

namespace Inurlbr\Engines;

/**
 * Engine Factory - Crea instancias de motores de búsqueda
 */
class EngineFactory
{
    /**
     * @var array<string, string> Mapeo de nombres a clases
     */
    private static array $engines = [
        'google' => \Inurlbr\Engines\GoogleEngine::class,
        'bing' => \Inurlbr\Engines\BingEngine::class,
        'yahoo' => \Inurlbr\Engines\YahooEngine::class,
        'duckduckgo' => \Inurlbr\Engines\DuckDuckGoEngine::class,
        'shodan' => \Inurlbr\Engines\ShodanEngine::class,
    ];

    /**
     * Registra un nuevo motor
     */
    public static function register(string $name, string $class): void
    {
        if (!is_subclass_of($class, AbstractEngine::class)) {
            throw new \InvalidArgumentException("Class {$class} must extend AbstractEngine");
        }
        
        self::$engines[strtolower($name)] = $class;
    }

    /**
     * Crea una instancia de un motor
     * 
     * @param array<string, mixed> $config Configuración del motor
     */
    public static function create(string $name, array $config = []): AbstractEngine
    {
        $name = strtolower($name);
        
        if (!isset(self::$engines[$name])) {
            throw new \InvalidArgumentException(
                "Engine '{$name}' not found. Available: " . implode(', ', array_keys(self::$engines))
            );
        }

        $class = self::$engines[$name];
        return new $class($config);
    }

    /**
     * Lista todos los motores disponibles
     */
    public static function list(): array
    {
        return array_keys(self::$engines);
    }

    /**
     * Verifica si un motor existe
     */
    public static function has(string $name): bool
    {
        return isset(self::$engines[strtolower($name)]);
    }
}

<?php

declare(strict_types=1);

namespace Inurlbr\Tests\Unit;

use Inurlbr\Engines\EngineFactory;
use Inurlbr\Engines\GoogleEngine;
use Inurlbr\Engines\BingEngine;
use Inurlbr\Engines\YahooEngine;
use Inurlbr\Engines\DuckDuckGoEngine;
use PHPUnit\Framework\TestCase;

/**
 * Tests para el EngineFactory
 */
class EngineFactoryTest extends TestCase
{
    public function testFactoryCreatesGoogleEngine(): void
    {
        $engine = EngineFactory::create('google');
        $this->assertInstanceOf(GoogleEngine::class, $engine);
    }

    public function testFactoryCreatesBingEngine(): void
    {
        $engine = EngineFactory::create('bing');
        $this->assertInstanceOf(BingEngine::class, $engine);
    }

    public function testFactoryCreatesYahooEngine(): void
    {
        $engine = EngineFactory::create('yahoo');
        $this->assertInstanceOf(YahooEngine::class, $engine);
    }

    public function testFactoryCreatesDuckDuckGoEngine(): void
    {
        $engine = EngineFactory::create('duckduckgo');
        $this->assertInstanceOf(DuckDuckGoEngine::class, $engine);
    }

    public function testFactoryIsCaseInsensitive(): void
    {
        $engine1 = EngineFactory::create('GOOGLE');
        $engine2 = EngineFactory::create('google');
        
        $this->assertInstanceOf(GoogleEngine::class, $engine1);
        $this->assertInstanceOf(GoogleEngine::class, $engine2);
    }

    public function testFactoryThrowsExceptionForUnknownEngine(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Engine 'unknown' not found");
        
        EngineFactory::create('unknown');
    }

    public function testFactoryListsAllEngines(): void
    {
        $engines = EngineFactory::list();
        
        $this->assertContains('google', $engines);
        $this->assertContains('bing', $engines);
        $this->assertContains('yahoo', $engines);
        $this->assertContains('duckduckgo', $engines);
        $this->assertContains('shodan', $engines);
    }

    public function testFactoryHasMethod(): void
    {
        $this->assertTrue(EngineFactory::has('google'));
        $this->assertFalse(EngineFactory::has('nonexistent'));
    }

    public function testFactoryRegistersCustomEngine(): void
    {
        // Creamos un engine mock para testing
        $mockClass = new class extends \Inurlbr\Engines\AbstractEngine {
            protected string $name = 'Mock';
            protected string $baseUrl = 'http://mock.test';
            
            protected function buildSearchUrl(string $dork, int $page): string
            {
                return $this->baseUrl;
            }
            
            protected function parseResults(string $html, string $dork): array
            {
                return [];
            }
        };
        
        $className = get_class($mockClass);
        EngineFactory::register('mock', $className);
        
        $this->assertTrue(EngineFactory::has('mock'));
        $engine = EngineFactory::create('mock');
        $this->assertInstanceOf($className, $engine);
    }
}

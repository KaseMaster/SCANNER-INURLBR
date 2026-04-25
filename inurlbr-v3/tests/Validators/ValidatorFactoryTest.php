<?php

declare(strict_types=1);

namespace Inurlbr\Tests\Validators;

use PHPUnit\Framework\TestCase;
use Inurlbr\Validators\ValidatorFactory;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Tests para ValidatorFactory.
 */
class ValidatorFactoryTest extends TestCase
{
    private ValidatorFactory $factory;
    private Client $client;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->client = new Client();
        $this->logger = new NullLogger();
        $this->factory = new ValidatorFactory($this->client, $this->logger);
    }

    public function testCreateSqlValidator(): void
    {
        $validator = $this->factory->create('sql');
        
        $this->assertInstanceOf(\Inurlbr\Validators\SqlInjectionValidator::class, $validator);
        $this->assertEquals('SQL Injection', $validator->getType());
    }

    public function testCreateLfiValidator(): void
    {
        $validator = $this->factory->create('lfi');
        
        $this->assertInstanceOf(\Inurlbr\Validators\LfiValidator::class, $validator);
        $this->assertEquals('Local File Inclusion', $validator->getType());
    }

    public function testCreateXssValidator(): void
    {
        $validator = $this->factory->create('xss');
        
        $this->assertInstanceOf(\Inurlbr\Validators\XssValidator::class, $validator);
        $this->assertEquals('Cross-Site Scripting (XSS)', $validator->getType());
    }

    public function testCreateWithAliases(): void
    {
        $this->assertInstanceOf(\Inurlbr\Validators\SqlInjectionValidator::class, $this->factory->create('sqli'));
        $this->assertInstanceOf(\Inurlbr\Validators\SqlInjectionValidator::class, $this->factory->create('sql_injection'));
        $this->assertInstanceOf(\Inurlbr\Validators\LfiValidator::class, $this->factory->create('local_file_inclusion'));
        $this->assertInstanceOf(\Inurlbr\Validators\XssValidator::class, $this->factory->create('cross_site_scripting'));
    }

    public function testCreateInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->factory->create('invalid_type');
    }

    public function testCreateMultiple(): void
    {
        $validators = $this->factory->createMultiple(['sql', 'lfi', 'xss']);
        
        $this->assertCount(3, $validators);
        $this->assertInstanceOf(\Inurlbr\Validators\SqlInjectionValidator::class, $validators[0]);
        $this->assertInstanceOf(\Inurlbr\Validators\LfiValidator::class, $validators[1]);
        $this->assertInstanceOf(\Inurlbr\Validators\XssValidator::class, $validators[2]);
    }

    public function testCreateAll(): void
    {
        $validators = $this->factory->createAll();
        
        $this->assertCount(3, $validators);
    }

    public function testGetAvailableTypes(): void
    {
        $types = ValidatorFactory::getAvailableTypes();
        
        $this->assertIsArray($types);
        $this->assertCount(3, $types);
        $this->assertContains('sql', $types);
        $this->assertContains('lfi', $types);
        $this->assertContains('xss', $types);
    }
}

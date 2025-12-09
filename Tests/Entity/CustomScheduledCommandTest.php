<?php

namespace Dukecity\CommandSchedulerBundle\Tests\Entity;

use DateTime;
use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommandInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests for custom entity extension pattern.
 * Verifies that BaseScheduledCommand can be properly extended.
 */
class CustomScheduledCommandTest extends TestCase
{
    private TestCustomScheduledCommand $entity;

    protected function setUp(): void
    {
        $this->entity = new TestCustomScheduledCommand();
    }

    public function testCustomEntityImplementsInterface(): void
    {
        $this->assertInstanceOf(ScheduledCommandInterface::class, $this->entity);
    }

    public function testCustomEntityInheritsAllMethods(): void
    {
        // Test inherited setters/getters work
        $this->entity->setName('custom-command');
        $this->assertSame('custom-command', $this->entity->getName());

        $this->entity->setCommand('cache:clear');
        $this->assertSame('cache:clear', $this->entity->getCommand());

        $this->entity->setCronExpression('@daily');
        $this->assertSame('@daily', $this->entity->getCronExpression());

        $this->entity->setPriority(10);
        $this->assertSame(10, $this->entity->getPriority());

        $this->entity->setDisabled(true);
        $this->assertTrue($this->entity->isDisabled());

        $this->entity->setLocked(true);
        $this->assertTrue($this->entity->isLocked());

        $this->entity->setExecuteImmediately(true);
        $this->assertTrue($this->entity->isExecuteImmediately());

        $this->entity->setArguments('--env=prod');
        $this->assertSame('--env=prod', $this->entity->getArguments());

        $this->entity->setLogFile('custom.log');
        $this->assertSame('custom.log', $this->entity->getLogFile());

        $this->entity->setNotes('Custom notes');
        $this->assertSame('Custom notes', $this->entity->getNotes());

        $now = new DateTime();
        $this->entity->setLastExecution($now);
        $this->assertSame($now, $this->entity->getLastExecution());

        $this->entity->setLastReturnCode(0);
        $this->assertSame(0, $this->entity->getLastReturnCode());

        $this->entity->setPingBackUrl('https://example.com/ping');
        $this->assertSame('https://example.com/ping', $this->entity->getPingBackUrl());

        $this->entity->setPingBackFailedUrl('https://example.com/failed');
        $this->assertSame('https://example.com/failed', $this->entity->getPingBackFailedUrl());
    }

    public function testCustomFieldWorks(): void
    {
        $this->assertNull($this->entity->getCustomField());

        $this->entity->setCustomField('custom-value');
        $this->assertSame('custom-value', $this->entity->getCustomField());

        $this->entity->setCategory('maintenance');
        $this->assertSame('maintenance', $this->entity->getCategory());
    }

    public function testFluentInterfaceWithCustomMethods(): void
    {
        // Test that method chaining works with both base and custom methods
        $result = $this->entity
            ->setName('test')
            ->setCommand('test:cmd')
            ->setCronExpression('@hourly')
            ->setCustomField('my-custom-value')
            ->setCategory('test-category')
            ->setPriority(5)
            ->setNotes('Test notes');

        $this->assertSame($this->entity, $result);
        $this->assertSame('test', $this->entity->getName());
        $this->assertSame('test:cmd', $this->entity->getCommand());
        $this->assertSame('@hourly', $this->entity->getCronExpression());
        $this->assertSame('my-custom-value', $this->entity->getCustomField());
        $this->assertSame('test-category', $this->entity->getCategory());
        $this->assertSame(5, $this->entity->getPriority());
        $this->assertSame('Test notes', $this->entity->getNotes());
    }

    public function testGetNextRunDateWorksOnCustomEntity(): void
    {
        $this->entity
            ->setName('test')
            ->setCommand('test:cmd')
            ->setCronExpression('@daily')
            ->setDisabled(false)
            ->setLocked(false);

        $nextRunDate = $this->entity->getNextRunDate();

        $this->assertInstanceOf(DateTime::class, $nextRunDate);
        $this->assertGreaterThan(new DateTime(), $nextRunDate);
    }

    public function testCronTranslationWorksOnCustomEntity(): void
    {
        $this->entity->setCronExpression('@weekly');

        $translated = $this->entity->getCronExpressionTranslated();

        $this->assertIsString($translated);
        $this->assertNotEmpty($translated);
        $this->assertStringNotContainsString('error', strtolower($translated));
    }

    public function testToStringWorksOnCustomEntity(): void
    {
        $this->entity->setName('my-custom-command');

        $this->assertSame('my-custom-command', (string) $this->entity);
    }

    public function testDefaultValuesOnCustomEntity(): void
    {
        $entity = new TestCustomScheduledCommand();

        $this->assertNull($entity->getId());
        $this->assertSame(0, $entity->getPriority());
        $this->assertFalse($entity->isDisabled());
        $this->assertFalse($entity->isLocked());
        $this->assertFalse($entity->isExecuteImmediately());
        $this->assertInstanceOf(DateTime::class, $entity->getCreatedAt());
        $this->assertSame('', $entity->getNotes());
        $this->assertNull($entity->getCustomField());
        $this->assertNull($entity->getCategory());
    }
}

<?php

namespace Dukecity\CommandSchedulerBundle\Tests\Entity;

use DateTime;
use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommand;
use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommandInterface;
use Dukecity\CommandSchedulerBundle\Service\ScheduledCommandFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests for BaseScheduledCommand entity logic.
 */
class BaseScheduledCommandTest extends WebTestCase
{
    private ScheduledCommandInterface $entity;
    private ScheduledCommandFactory $factory;

    protected function setUp(): void
    {
        $client = self::createClient();
        $container = $client->getContainer()->get('test.service_container');
        $this->factory = $container->get(ScheduledCommandFactory::class);
        $this->entity = $this->factory->create();
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(ScheduledCommandInterface::class, $this->entity);
    }

    public function testDefaultEntityIsScheduledCommand(): void
    {
        $this->assertInstanceOf(ScheduledCommand::class, $this->entity);
    }

    public function testDefaultValues(): void
    {
        $entity = $this->factory->create();

        $this->assertNull($entity->getId());
        $this->assertSame(0, $entity->getPriority());
        $this->assertFalse($entity->isDisabled());
        $this->assertFalse($entity->isLocked());
        $this->assertFalse($entity->isExecuteImmediately());
        $this->assertInstanceOf(DateTime::class, $entity->getCreatedAt());
        $this->assertSame('', $entity->getNotes());
    }

    public function testFluentInterface(): void
    {
        $entity = $this->factory->create();

        $result = $entity
            ->setName('test-command')
            ->setCommand('cache:clear')
            ->setCronExpression('@daily')
            ->setPriority(5)
            ->setDisabled(false)
            ->setArguments('--env=test')
            ->setNotes('Test notes');

        $this->assertSame($entity, $result);
        $this->assertSame('test-command', $entity->getName());
        $this->assertSame('cache:clear', $entity->getCommand());
        $this->assertSame('@daily', $entity->getCronExpression());
        $this->assertSame(5, $entity->getPriority());
        $this->assertSame('--env=test', $entity->getArguments());
        $this->assertSame('Test notes', $entity->getNotes());
    }

    public function testAllSettersReturnStatic(): void
    {
        $entity = $this->factory->create();

        $this->assertSame($entity, $entity->setName('test'));
        $this->assertSame($entity, $entity->setCommand('cmd'));
        $this->assertSame($entity, $entity->setCronExpression('* * * * *'));
        $this->assertSame($entity, $entity->setPriority(1));
        $this->assertSame($entity, $entity->setDisabled(true));
        $this->assertSame($entity, $entity->setLocked(true));
        $this->assertSame($entity, $entity->setExecuteImmediately(true));
        $this->assertSame($entity, $entity->setArguments('args'));
        $this->assertSame($entity, $entity->setLogFile('test.log'));
        $this->assertSame($entity, $entity->setLastReturnCode(0));
        $this->assertSame($entity, $entity->setLastExecution(new DateTime()));
        $this->assertSame($entity, $entity->setCreatedAt(new DateTime()));
        $this->assertSame($entity, $entity->setPingBackUrl('https://example.com'));
        $this->assertSame($entity, $entity->setPingBackFailedUrl('https://example.com/fail'));
        $this->assertSame($entity, $entity->setNotes('notes'));
    }

    #[DataProvider('cronExpressionProvider')]
    public function testGetNextRunDateWithValidCron(string $cronExpression): void
    {
        $entity = $this->factory->create();
        $entity->setName('test')
            ->setCommand('test:cmd')
            ->setCronExpression($cronExpression)
            ->setDisabled(false)
            ->setLocked(false);

        $nextRunDate = $entity->getNextRunDate();

        $this->assertInstanceOf(DateTime::class, $nextRunDate);
        $this->assertGreaterThan(new DateTime(), $nextRunDate);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function cronExpressionProvider(): array
    {
        return [
            'every minute' => ['* * * * *'],
            'every hour' => ['0 * * * *'],
            'daily at midnight' => ['0 0 * * *'],
            'weekly on sunday' => ['0 0 * * 0'],
            'monthly' => ['0 0 1 * *'],
            'shortcut daily' => ['@daily'],
            'shortcut weekly' => ['@weekly'],
            'shortcut monthly' => ['@monthly'],
            'shortcut yearly' => ['@yearly'],
            'every 10 minutes' => ['*/10 * * * *'],
        ];
    }

    public function testGetNextRunDateWhenDisabled(): void
    {
        $entity = $this->factory->create();
        $entity->setName('test')
            ->setCommand('test:cmd')
            ->setCronExpression('@daily')
            ->setDisabled(true);

        $this->assertNull($entity->getNextRunDate());
    }

    public function testGetNextRunDateWhenLocked(): void
    {
        $entity = $this->factory->create();
        $entity->setName('test')
            ->setCommand('test:cmd')
            ->setCronExpression('@daily')
            ->setLocked(true);

        $this->assertNull($entity->getNextRunDate());
    }

    public function testGetNextRunDateWithExecuteImmediately(): void
    {
        $entity = $this->factory->create();
        $entity->setName('test')
            ->setCommand('test:cmd')
            ->setCronExpression('@daily')
            ->setExecuteImmediately(true);

        $nextRunDate = $entity->getNextRunDate(true);

        $this->assertInstanceOf(DateTime::class, $nextRunDate);
        // Should be "now" (within a few seconds)
        $now = new DateTime();
        $diff = abs($now->getTimestamp() - $nextRunDate->getTimestamp());
        $this->assertLessThan(5, $diff);
    }

    public function testGetNextRunDateWithExecuteImmediatelyIgnored(): void
    {
        $entity = $this->factory->create();
        $entity->setName('test')
            ->setCommand('test:cmd')
            ->setCronExpression('@daily')
            ->setExecuteImmediately(true);

        // When checkExecuteImmediately is false, it should use cron expression
        $nextRunDate = $entity->getNextRunDate(false);

        $this->assertInstanceOf(DateTime::class, $nextRunDate);
        // Should be in the future (not now)
        $this->assertGreaterThan(new DateTime(), $nextRunDate);
    }

    public function testGetCronExpressionTranslated(): void
    {
        $entity = $this->factory->create();
        $entity->setCronExpression('@daily');

        $translated = $entity->getCronExpressionTranslated();

        $this->assertIsString($translated);
        $this->assertNotEmpty($translated);
        $this->assertStringNotContainsString('error', strtolower($translated));
    }

    public function testGetCronExpressionTranslatedWithInvalidCron(): void
    {
        $entity = $this->factory->create();
        $entity->setCronExpression('invalid-cron');

        $translated = $entity->getCronExpressionTranslated();

        $this->assertStringContainsString('error', strtolower($translated));
    }

    public function testToString(): void
    {
        $entity = $this->factory->create();
        $entity->setName('my-command');

        $this->assertSame('my-command', (string) $entity);
    }

    public function testGetNameReturnsSetValue(): void
    {
        $entity = $this->factory->create();
        $entity->setName('test-name');

        $this->assertSame('test-name', $entity->getName());
    }
}

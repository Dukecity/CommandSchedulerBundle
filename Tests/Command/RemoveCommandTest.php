<?php

namespace Dukecity\CommandSchedulerBundle\Tests\Command;

use Dukecity\CommandSchedulerBundle\Command\RemoveCommand;
use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommand;
use Dukecity\CommandSchedulerBundle\Fixtures\ORM\LoadScheduledCommandData;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Console\Tester\CommandCompletionTester;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
#use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Application;

/**
 * Class RemoveCommandTest.
 */
class RemoveCommandTest extends AbstractCommandTest
{
    /**
     * Test scheduler:remove with given command name.
     */
    public function testRemove(): void
    {
        // DataFixtures create 4 records
        $this->loadScheduledCommandFixtures();

        // remove a command that does not exist
        $output = $this->executeCommand(RemoveCommand::class, ['name' => 'abc'], [], 1)->getDisplay();
        $this->assertStringContainsString('Could not', $output);

        // empty command-name
        $output = $this->executeCommand(RemoveCommand::class, ['name' => ''], [], 1)->getDisplay();
        $this->assertStringContainsString('Could not', $output);

        // Remove command
        $output = $this->executeCommand(RemoveCommand::class, ['name' => 'CommandTestTwo'])->getDisplay();
        $this->assertStringContainsString('successfully', $output);

        // Not in DB anymore
        $two = $this->em->getRepository(ScheduledCommand::class)->findOneBy(['name' => 'CommandTestTwo']);
        $this->assertNull($two);

        // Fails now
        $output = $this->executeCommand(RemoveCommand::class, ['name' => 'CommandTestTwo'], [], 1)->getDisplay();
        $this->assertStringContainsString('Could not', $output);
    }

    /**
     * @dataProvider provideCompletionSuggestions
     * @param array<string, mixed> $input
     * @throws \Exception
     */
    public function testComplete(array $input, array $expectedSuggestions): void
    {
        $cmd = static::getContainer()->get(RemoveCommand::class);
        $cmd->setApplication(new \Symfony\Component\Console\Application('Test'));
        $tester = new CommandCompletionTester($cmd);

        $suggestions = $tester->complete($input);

        $this->assertSame($expectedSuggestions, $suggestions);
    }

    public static function provideCompletionSuggestions(): \Generator
    {
        yield 'name' => [
            ['CommandTestT'],
            ['CommandTestOne', 'CommandTestFour', 'CommandTestFive', 'CommandTestThree']
        ];
    }

    /**
     * @return MockObject&KernelInterface
     */
    private function getKernel(): KernelInterface
    {
        $container = $this->createMock(ContainerInterface::class);

        $kernel = $this->createMock(KernelInterface::class);
        $kernel
            ->method('getContainer')
            ->willReturn($container);

        $kernel
            ->expects($this->once())
            ->method('getBundles')
            ->willReturn([]);

        return $kernel;
    }
}

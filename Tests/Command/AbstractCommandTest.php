<?php

namespace Dukecity\CommandSchedulerBundle\Tests\Command;

use Doctrine\ORM\EntityManager;
use Dukecity\CommandSchedulerBundle\Fixtures\ORM\LoadScheduledCommandData;
use InvalidArgumentException;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class AddCommandTest.
 */
abstract class AbstractCommandTest extends WebTestCase
{
    protected AbstractDatabaseTool $databaseTool;
    protected EntityManager $em;
    protected CommandTester | null $commandTester;
    protected array $infos = [
        "commands" => 5,
    ];

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        self::bootKernel();

        $this->em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->databaseTool = static::$kernel->getContainer()->get(DatabaseToolCollection::class)->get();
    }

    /**
     * This helper method abstracts the boilerplate code needed to test the
     * execution of a command.
     * @link https://symfony.com/doc/current/console.html#testing-commands
     */
    protected function executeCommand(string $commandClass, array $arguments = [], array $inputs = [], int $expectedExitCode=0): CommandTester
    {
        // this uses a special testing container that allows you to fetch private services

        if(!is_subclass_of($commandClass, Command::class))
        {throw new InvalidArgumentException("Not a command class");}

        $cmd = static::getContainer()->get($commandClass);
        $cmd->setApplication(new Application('Test'));

        /** @var Command $cmd */
        $commandTester = new CommandTester($cmd);
        $commandTester->setInputs($inputs);
        $result = $commandTester->execute($arguments, ["capture_stderr_separately"]);

        $this->assertSame($expectedExitCode, $result);

        if($result !== $expectedExitCode)
        {
            /** @noinspection ForgottenDebugOutputInspection */
            var_dump($commandTester->getErrorOutput());
        }

        return $commandTester;
    }

    protected function loadScheduledCommandFixtures(): void
    {
        $this->databaseTool->loadFixtures([LoadScheduledCommandData::class]);
    }
}

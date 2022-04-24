<?php /** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpUnhandledExceptionInspection */

/** @noinspection PhpUnhandledExceptionInspection */

namespace Dukecity\CommandSchedulerBundle\Tests\Service;

use Dukecity\CommandSchedulerBundle\Service\CommandParser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Exception\CommandNotFoundException;

class CommandParserTest extends WebTestCase
{
    private CommandParser|null $commandParser = null;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        $client = self::createClient();
        $normalContainer = $client->getContainer();
        $specialContainer = $normalContainer->get('test.service_container');

        #$normalContainer->getParameter("excluded_command_namespaces"); # scheduler
        $this->commandParser = $specialContainer->get(CommandParser::class);
    }

    public function testGetCommandDetails(): void
    {
        # Single parameter
        $commandDetails = $this->commandParser->getCommandDetails(["help"]);

        $this->assertIsArray($commandDetails);
        $this->assertArrayHasKey('help', $commandDetails);
        $this->assertSame("help", $commandDetails["help"]["name"]);

        # Multiple parameters
        $commandDetails = $this->commandParser->getCommandDetails(
            ["help", "assets:install", "cache:clear"]
        );

        $this->assertIsArray($commandDetails);
        $this->assertArrayHasKey('help', $commandDetails);
        $this->assertSame("help", $commandDetails["help"]["name"]);

        $command = $commandDetails["help"];
        $this->assertArrayHasKey('name', $command);
        $this->assertArrayHasKey('usage', $command);
        $this->assertArrayHasKey('description', $command);
        $this->assertArrayHasKey('definition', $command);
    }

    public function testGetCommandDetailsFailed(): void
    {
        $this->expectException(CommandNotFoundException::class);
        $this->commandParser->getCommandDetails(["abc:install"]);

        $this->expectException(CommandNotFoundException::class);
        $this->commandParser->getCommandDetails([]);
    }


    /**
     * Check if we get the command-list correct (Default config in config_test.yml)
     */
    public function testGetCommands(): void
    {
        $commands = $this->commandParser->getCommands();

        $this->assertIsArray($commands);
        $this->assertArrayHasKey('_global', $commands);
        $this->assertSame("assets:install", $commands["assets"]["assets:install"]);
    }

    /**
     * Check if we get the command-list correct (Default config in config_test.yml)
     */
    public function testGetAllowedCommands(): void
    {
        $commands = $this->commandParser->getAllowedCommandDetails();

        $this->assertIsArray($commands);
        $this->assertArrayHasKey('about', $commands);
        $this->assertSame("about", $commands["about"]["name"]);

        $this->assertArrayHasKey('list', $commands);
        $this->assertArrayHasKey('cache:clear', $commands);
    }


    /**
     * Check if we get the command-list correct
     */
    public function testIsNamespacingValid(): void
    {
        $this->assertTrue($this->commandParser->isNamespacingValid(["debug", "doctrine"], []));
        $this->assertTrue($this->commandParser->isNamespacingValid([], ["debug", "doctrine"]));
        $this->assertTrue($this->commandParser->isNamespacingValid([], []));
        $this->assertFalse($this->commandParser->isNamespacingValid(["debug"], ["debug", "doctrine"]));
    }


    /**
     * Check if we get the command-list correct
     */
    public function testBlacklistingGetCommands(): void
    {
        $this->commandParser->setExcludedNamespaces(["debug", "doctrine"]);
        $this->commandParser->setIncludedNamespaces([]);

        $commands = $this->commandParser->getCommands();

        $this->assertIsArray($commands);
        $this->assertArrayHasKey("cache", $commands);
        $this->assertArrayNotHasKey("debug", $commands);
        $this->assertSame("cache:clear", $commands["cache"]["cache:clear"]);
    }

    /**
     * Check if we get the command-list correct
     */
    public function testWhitelistingGetCommands(): void
    {
        $this->commandParser->setExcludedNamespaces([]);
        $this->commandParser->setIncludedNamespaces(["cache", "debug"]);

        $commands = $this->commandParser->getCommands("test");

        $this->assertIsArray($commands);
        $this->assertArrayHasKey("debug", $commands);
        $this->assertSame("debug:config", $commands["debug"]["debug:config"]);
        $this->assertSame("debug:container", $commands["debug"]["debug:container"]);
    }

    /**
     * TODO how to test? default it is always env="test" in AppKernel
     * Check if we get the command-list correct
     */
    /*
    public function testEnvironmentGetCommands()
    {
        $this->commandParser->setExcludedNamespaces([]);
        $this->commandParser->setIncludedNamespaces(["cache", "debug"]);

        $commands = $this->commandParser->getCommands("prod");

        $this->assertIsArray($commands);
        $this->assertArrayHasKey("cache", $commands);
        $this->assertArrayNotHasKey("debug", $commands);
        $this->assertSame("cache:clear", $commands["cache"]["cache:clear"]);
    }*/
}

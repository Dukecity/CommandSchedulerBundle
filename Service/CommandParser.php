<?php

namespace Dukecity\CommandSchedulerBundle\Service;

use Exception;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class CommandParser
 *
 * @author Julien Guyon <julienguyon@hotmail.com>
 */
class CommandParser
{
    /**
     * @param string[] $excludedNamespaces
     * @param string[] $includedNamespaces
     */
    public function __construct(private KernelInterface $kernel,
        private array $excludedNamespaces = [],
        private array $includedNamespaces = [])
    {
        if (!$this->isNamespacingValid($excludedNamespaces, $includedNamespaces)) {
            throw new \InvalidArgumentException('Cannot combine excludedNamespaces with includedNamespaces');
        }
    }


    /**
     * There could be only whitelisting or blacklisting
     *
     * @param string[] $excludedNamespaces
     * @param string[] $includedNamespaces
     */
    public function isNamespacingValid(array $excludedNamespaces, array $includedNamespaces): bool
    {
        return !(
                count($excludedNamespaces) > 0 &&
                count($includedNamespaces) > 0
                );
    }


    public function setExcludedNamespaces(array $namespaces = []): void
    {
        $this->excludedNamespaces = $namespaces;
    }

    public function setIncludedNamespaces(array $namespaces = []): void
    {
        $this->includedNamespaces = $namespaces;
    }

    /**
     * Get all available commands from symfony
     *
     * @throws Exception
     */
    public function getAvailableCommands(string $format="xml", string $env="prod"): string|array
    {
        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput(
            [
                'command' => 'list',
                '--format' => $format,
                '--env' => $env,
            ]
        );

        try {
            Debug::enable();
            $output = new StreamOutput(fopen('php://memory', 'wb+'));
            $application->run($input, $output);

            rewind($output->getStream());

            if($format === "xml")
            {return stream_get_contents($output->getStream());}

            if($format === "json")
            {return json_decode(
                stream_get_contents($output->getStream()),
                true,
                512,
                JSON_THROW_ON_ERROR
            );}

            throw new \InvalidArgumentException('Only xml and json are allowed');
        } catch (\Throwable) {
            throw new \RuntimeException('Listing of commands could not be read');
        }
    }

    /**
     * Execute the console command "list" and parse the output to have all available command.
     *
     * @return array<string, string[]> ["Namespace1" => ["Command1", "Command2"]]
     *
     * @throws Exception
     */
    public function getCommands(string $env="prod"): array
    {
        if (!$this->isNamespacingValid($this->excludedNamespaces, $this->includedNamespaces)) {
            throw new \InvalidArgumentException('Cannot combine excludedNamespaces with includedNamespaces');
        }

        return $this->extractCommands($this->getAvailableCommands("json", $env));
    }


    /**
     * Get Details for the commands, for the allowed Namespaces
     *
     * @return array<string, array<string, mixed>>
     * @throws Exception
     */
    public function getAllowedCommandDetails(string $env="prod"): array
    {
       # var_dump($this->getCommands($env));
       return $this->getCommandDetails($this->getCommands($env));
    }


    /**
     * Is the command-List wrapped in namespaces?
     *
     * @param array<string, array<string, string>> $commands
     * @return string[]
     */
    public function reduceNamespacedCommands(array $commands): array
    {
        if(count($commands)===0)
        {return [];}

        # is namespaced?
        if(is_array(current($commands)))
        {
            #var_dump("Command-Listing with namespaces");
            $commandsExtracted = [];

            foreach ($commands as $namespaces)
            {
                foreach ($namespaces as $cmd)
                {
                    $commandsExtracted[$cmd] = $cmd;
                }
            }

            return $commandsExtracted;
        }

        return $commands;
    }

    /**
     * @return array<string, array<string, mixed>>
     * @throws Exception
     */
    public function getCommandDetails(array $commands): array
    {
        $availableCommands = $this->getAvailableCommands("json");
        $result = [];
        #$command->getDefinition();

        # Is the command-List wrapped in namespaces?
        $commands = $this->reduceNamespacedCommands($commands);

        foreach ($availableCommands["commands"] as $command)
        {
            #var_dump($command);
            if(in_array($command["name"], $commands, true))
            {
                $result[$command["name"]] = $command;
            }
        }

        if(count($result)===0)
        {throw new CommandNotFoundException('Cannot find a command with this names');}

        return $result;
    }




    /**
     * Extract an array of available Symfony commands from the JSON output.
     *
     * @param array<string, array<int, mixed>> $commands
     * @return array<string, array<int|string, mixed>|string>
     * ["namespaces]
     *  [0]
     *     ["id"] => cache
     *     ["commands"] => ["cache:clear", "cache:warmup", ...]
     */
    private function extractCommands(array $commands): array
    {
        if (count($commands) === 0) {
            return [];
        }

        $commandsList = [];

        try {
            foreach ($commands["namespaces"] as $namespace) {
                $namespaceId = (string) $namespace["id"];

                # Blacklisting and Whitelisting
                if ((count($this->excludedNamespaces) > 0 && in_array($namespaceId, $this->excludedNamespaces, true))
                    ||
                    (count($this->includedNamespaces) > 0 && !in_array($namespaceId, $this->includedNamespaces, true))
                ) {
                    continue;
                }

                # Add Command Name to array
                foreach ($namespace["commands"] as $command) {

                    $commandsList[$namespaceId][$command] = $command;
                }
            }
        } catch (Exception) {
            // return an empty CommandList
            $commandsList = ['ERROR: please check php bin/console list --format=json' => 'error'];
        }

        return $commandsList;
    }
}

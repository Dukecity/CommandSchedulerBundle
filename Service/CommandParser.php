<?php

namespace Dukecity\CommandSchedulerBundle\Service;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Descriptor\JsonDescriptor;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Output\BufferedOutput;
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
    public function __construct(
        private KernelInterface $kernel,
        private array $excludedNamespaces = [],
        private array $includedNamespaces = [],
    ) {
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


    /**
     * @param string[] $namespaces
     */
    public function setExcludedNamespaces(array $namespaces = []): void
    {
        $this->excludedNamespaces = $namespaces;
    }

    /**
     * @param string[] $namespaces
     */
    public function setIncludedNamespaces(array $namespaces = []): void
    {
        $this->includedNamespaces = $namespaces;
    }

    /**
     * Get all available commands from symfony
     *
     * @return string[] Command names
     */
    public function getAvailableCommands(): array
    {
        return $this->kernel
            ->getContainer()
            ->get('console.command_loader')
            ->getNames();
    }

    /**
     * Execute the console command "list" and parse the output to have all available command.
     *
     * @return array<string, string[]> ["Namespace1" => ["Command1", "Command2"]]
     *
     * @throws \InvalidArgumentException
     */
    public function getCommands(): array
    {
        if (!$this->isNamespacingValid($this->excludedNamespaces, $this->includedNamespaces)) {
            throw new \InvalidArgumentException('Cannot combine excludedNamespaces with includedNamespaces');
        }

        return $this->extractCommands($this->getAvailableCommands());
    }


    /**
     * Get Details for the commands, for the allowed Namespaces
     *
     * @return array<string, array<string, mixed>>
     * @throws Exception
     */
    public function getAllowedCommandDetails(): array
    {
       return $this->getCommandDetails($this->getAvailableCommands());
    }


    /**
     * Is the command-List wrapped in namespaces?
     *
     * @param array<string, array<string, string>>|string[] $commands
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
     * @param string[] $commands
     * @return array<string, array<string, mixed>>
     * @throws Exception
     */
    public function getCommandDetails(array $commands): array
    {
        $result = [];

        $commandLoader = $this->kernel->getContainer()->get('console.command_loader');
        $jsonDescriptor = new JsonDescriptor();

        foreach ($commands as $commandName) {
            if (!$commandLoader->has($commandName)) {
                continue;
            }

            /** @var Command $command */
            $command = $commandLoader->get($commandName);

            $buffer = new BufferedOutput();
            $jsonDescriptor->describe($buffer, $command);

            $result[$commandName] = json_decode($buffer->fetch(), true);
        }

        if(count($result)===0)
        {throw new CommandNotFoundException('Cannot find a command with this names');}

        return $result;
    }




    /**
     * Extract an array of available Symfony commands from the JSON output.
     *
     * @param string[] $commands Command names
     * @return array<string, array<int|string, mixed>|string>
     * ["namespaces]
     *  [0]
     *     ["id"] => cache
     *     ["commands"] => ["cache:clear", "cache:warmup", ...]
     */
    private function extractCommands(array $commands): array
    {
        $commandsList = [];

        foreach ($commands as $commandName) {
            $namespaceId = explode(':', $commandName)[0];

            # Blacklisting and Whitelisting
            if ((count($this->excludedNamespaces) > 0 && in_array($namespaceId, $this->excludedNamespaces, true))
                ||
                (count($this->includedNamespaces) > 0 && !in_array($namespaceId, $this->includedNamespaces, true))
            ) {
                continue;
            }

            $commandsList[$namespaceId][$commandName] = $commandName;
        }

        return $commandsList;
    }
}

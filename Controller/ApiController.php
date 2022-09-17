<?php

namespace Dukecity\CommandSchedulerBundle\Controller;

use Cron\CronExpression as CronExpressionLib;
use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommand;
use Dukecity\CommandSchedulerBundle\Service\CommandParser;
use Lorisleiva\CronTranslator\CronParsingException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Lorisleiva\CronTranslator\CronTranslator;

/**
 * Class ApiController.
 *
 * @author Julien Guyon <julienguyon@hotmail.com>
 */
class ApiController extends AbstractBaseController
{
    private int $lockTimeout = 3600;
    private LoggerInterface $logger;
    private CommandParser $commandParser;

    public function setLockTimeout(int $lockTimeout): void
    {
        $this->lockTimeout = $lockTimeout;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function setCommandParser(CommandParser $commandParser): void
    {
        $this->commandParser = $commandParser;
    }

    /**
     * @param ScheduledCommand[] $commands
     * @return array<string, mixed>
     * @throws \Exception
     */
    private function getCommandsAsArray(array $commands): array
    {
        $jsonArray = [];

        foreach ($commands as $command) {
            $jsonArray[$command->getName()] = [
                'NAME' => $command->getName(),
                'COMMAND' => $command->getCommand(),
                'ARGUMENTS' => $command->getArguments(),
                'LAST_RETURN_CODE' => $command->getLastReturnCode(),
                'B_LOCKED' => $command->getLocked(),
                'DH_LAST_EXECUTION' => $command->getLastExecution(),
                'DH_NEXT_EXECUTION' => $command->getNextRunDate(),
                'LOGFILE' => $command->getLogFile(),
            ];
        }

        return $jsonArray;
    }


    /**
     * List all available (with the allowed namespaces) symfony console commands.
     * The commands are grouped by namespaces (like the regular "list" command from symfony
     */
    public function getConsoleCommands(): JsonResponse
    {
        try {
         return $this->json($this->commandParser->getCommands());
        }
        catch (\Exception $e) {
            $this->logger->error('Get Console Commands by API failed', ['message' => $e->getMessage()]);
        }

        // StatusCode 417 (error)
        return $this->json([], Response::HTTP_EXPECTATION_FAILED);
    }


    /**
     * Get Details for symfony console commands (if in allowed namespaces)
     *
     * @param string $commands all | list of commands , separated
     * @example cache:clear,assets:install
     */
    public function getConsoleCommandsDetails(string $commands="all"): JsonResponse
    {
        try {

            if($commands!=="all")
            {
                return $this->json($this->commandParser->getCommandDetails(explode(",", $commands)));
            }

            # all commands
            return $this->json($this->commandParser->getAllowedCommandDetails());
        }
        catch (\Exception $e) {
            $this->logger->error('Get Console Commands details by API failed', ['message' => $e->getMessage()]);
        }

        // StatusCode 417 (error)
        return $this->json([], Response::HTTP_EXPECTATION_FAILED);
    }

    /**
     * List all commands.
     */
    public function listAction(): JsonResponse
    {
        $commands = $this->getDoctrineManager()
            ->getRepository(ScheduledCommand::class)
            ->findAll();

        return $this->json($this->getCommandsAsArray($commands));
    }

    /**
     * External check to monitor the health of the scheduled commands.
     *
     * method checks if there are jobs which are enabled but did not return 0 on last execution or are locked.
     * if a match is found, HTTP status 417 is sent along with an array
     * if no matches found, HTTP status 200 is sent with an empty array.
     */
    public function monitorAction(): JsonResponse
    {
        $failedCommands = $this->getDoctrineManager()
            ->getRepository(ScheduledCommand::class)
            ->findFailedAndTimeoutCommands($this->lockTimeout);

        $jsonArray = $this->getCommandsAsArray($failedCommands);

        if (count($failedCommands) > 1) {
            $this->logger->debug(
                'MonitorCommand found locked or timed out commands',
                ['amount' => count($failedCommands)]
            );
        } else {
            // HTTP_OK: no failed or timeout commands
            return new JsonResponse();
        }

        $response = new JsonResponse();
        try {
            $response->setContent(json_encode($jsonArray, JSON_THROW_ON_ERROR));
        } catch (\JsonException $e) {
            $this->logger->error('MonitorCommand failed', ['message' => $e->getMessage()]);
        }

        // StatusCode 417 (error)
        return $response->setStatusCode(Response::HTTP_EXPECTATION_FAILED);
    }


    /**
     * Translate cron expression
     *
     * @return JsonResponse Status = 0 (ok)
     */
    public function translateCronExpression(string $cronExpression, string $lang = 'en'): JsonResponse
    {
        try{
            if(CronExpressionLib::isValidExpression($cronExpression))
            {
                $msg = CronTranslator::translate($cronExpression, $lang);
                return new JsonResponse(["status" => 0, "message" => $msg]);
            }

            $msg = "Not a valid Cron-Expression";
            return new JsonResponse(["status" => -1, "message" => $msg]);
        }
        catch (\Exception)
        {
            $msg = "Could not translate Cron-Expression";
        }

        return new JsonResponse(["status" => -2, "message" => $msg]);
    }
}

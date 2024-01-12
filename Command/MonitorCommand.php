<?php

/** @noinspection PhpMissingFieldTypeInspection */

namespace Dukecity\CommandSchedulerBundle\Command;

use Doctrine\Persistence\ObjectManager;
use Dukecity\CommandSchedulerBundle\Event\SchedulerCommandFailedEvent;
use Knp\Bundle\TimeBundle\DateTimeFormatter;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
//use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommand;

/**
 * Class MonitorCommand
 * This class is used for monitoring scheduled commands if they run for too long or failed to execute.
 *
 * @author  Daniel Fischer <dfischer000@gmail.com>
 */
#[AsCommand(name: 'scheduler:monitor', description: 'Monitor scheduled commands')]
class MonitorCommand extends Command
{
    private ObjectManager $em;

    //private ParameterBagInterface $params;

    /**
     * @param string[] $receiver
     */
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        ManagerRegistry $managerRegistry,
        private readonly DateTimeFormatter $dateTimeFormatter,
        string $managerName,
        private int | bool $lockTimeout,
        private array $receiver,
        private string $mailSubject,
        private bool $sendMailIfNoError = false
    ) {
        $this->em = $managerRegistry->getManager($managerName);
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Monitor scheduled commands')
            ->addOption('dump', null, InputOption::VALUE_NONE, 'Display result instead of send mail')
            ->setHelp(<<<'HELP'
The <info>%command.name%</info> is reporting failed and timedout scheduled commands:

  <info>php %command.full_name%</info>

By default the command sends the info via symfony messanger to the configured recipients.
You can just print the infos on the console via the <comment>--dump</comment> option:

  <info>php %command.full_name%</info> <comment>--dump</comment>

HELP);
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // If not in dump mode and none receiver is set, exit.
        $dumpMode = (bool) $input->getOption('dump');
        if (!$dumpMode && 0 === count($this->receiver)) {
            $output->writeln('<error>Please add receiver in configuration. Or use --dump option</error>');

            return Command::FAILURE;
        }

        // Fist, get all failed or potential timeout
        $failedCommands = $this->em->getRepository(ScheduledCommand::class)
            ->findFailedAndTimeoutCommands($this->lockTimeout);
        //->findAll(); // for notification testing

        // Commands in error
        if (count($failedCommands) > 0) {
            // if --dump option, don't send mail
            if ($dumpMode) {
                $this->dump($output, $failedCommands);
            } else {
                $this->eventDispatcher->dispatch(new SchedulerCommandFailedEvent($failedCommands));
            }
        } elseif ($dumpMode) {
            $output->writeln('<info>No errors found.</info>');
        } /*elseif ($this->params->get('sendMailIfNoError')) {
            $this->sendMails('No errors found.');
        }*/

        return Command::SUCCESS;
    }

    /**
     * Print a table of locked Commands to console.
     *
     * @param ScheduledCommand[] $failedCommands
     * @throws \Exception
     */
    private function dump(OutputInterface $output, array $failedCommands): void
    {
        $table = new Table($output);
        $table->setStyle('box');
        $table->setHeaders(['Name', 'LastReturnCode', 'Locked', 'LastExecution', 'NextExecution']);

        foreach ($failedCommands as $command) {
            $lockedInfo = match ($command->getLocked()) {
                true => '<error>LOCKED</error>',
            default => ''
            };

            $lastReturnInfo = match ($command->getLastReturnCode()) {
                '', false, null => '',
                0 => '<info>0 (success)</info>',
                // no break
            default => '<error>'.$command->getLastReturnCode().' (error)</error>'
            };


            $lastRunDate = $command->getLastExecution();
            if($lastRunDate)
            {
                $lastRunDateText = $lastRunDate->format('Y-m-d H:i').' ('
                    .$this->dateTimeFormatter->formatDiff($command->getLastExecution()).')';
            }
            else {
                $lastRunDateText = '';
            }

            $nextRunDate = $command->getNextRunDate();
            if($nextRunDate)
            {
                $nextRunDateText = $nextRunDate->format('Y-m-d H:i').' ('
                 .$this->dateTimeFormatter->formatDiff($command->getLastExecution()).')';
            }
            else {
                $nextRunDateText = '';
            }

            $table->addRow([
                $command->getName(),
                $lastReturnInfo,
                $lockedInfo,
                $lastRunDateText,
                $nextRunDateText
            ]);
        }

        $table->render();
    }
}

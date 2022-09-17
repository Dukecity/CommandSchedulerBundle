<?php /** @noinspection PhpMissingFieldTypeInspection */

namespace Dukecity\CommandSchedulerBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command is just for testing.
 */
#[AsCommand(name: 'scheduler:test', description: 'long running command', hidden: true)]
class TestCommand extends Command
{
    private SymfonyStyle $io;
    private int $runtime;
    private bool|int $returnFail;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->addArgument('runtime', InputArgument::OPTIONAL, 'Runtime in Seconds', 10)
            ->addArgument('returnFail', InputArgument::OPTIONAL, 'Fake Fail Return', false)
            ->setHidden(true)
        ;
    }

    /**
     * Initialize parameters and services used in execute function.
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->runtime = (int) ($input->getArgument('runtime') ?? 10);
        $this->returnFail = (bool) ($input->getArgument('returnFail') ?? false);

        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->info('Start the process for '.$this->runtime.' seconds');

        $i = 0;
        while ($i < $this->runtime) {
            ++$i;
            sleep(1);
            $this->io->info('Output after '.$i.' Seconds');
        }

        # fake fail?
        if ($this->returnFail)
        {
         $this->io->info('Response-Code is forced to '.Command::FAILURE);
         return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

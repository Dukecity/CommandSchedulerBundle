<?php

namespace Dukecity\CommandSchedulerBundle\Tests\Command;

use Doctrine\Persistence\Mapping\MappingException;
use Dukecity\CommandSchedulerBundle\Command\TestCommand;
use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommand;
use Dukecity\CommandSchedulerBundle\Fixtures\ORM\LoadScheduledCommandData;

/**
 * Class TestCommandTest.
 */
class TestCommandTest extends AbstractCommandTest
{
    public function testExecute(): void
    {
        // DataFixtures create 4 records
        $this->loadScheduledCommandFixtures();

        # Test Runtime
        $start = new \DateTime();
        $output = $this->executeCommand(TestCommand::class, ['runtime' => 1])->getDisplay();
        $this->assertStringContainsString('Start the process for 1 seconds', $output);
        $this->assertGreaterThanOrEqual(1,
             $start->diff(new \DateTime())->format('%s'),
             'Runtime-Parameter does not match real-time sleep'
        );

        # Test forced return Fail (1)
        $output = $this->executeCommand(TestCommand::class,
            ['runtime' => 0, 'returnFail' => true],
            [],
            1)->getDisplay();

        $this->assertStringContainsString('Response-Code is forced to 1', $output);
    }
}

<?php

namespace Dukecity\CommandSchedulerBundle\Tests\Command;

use Dukecity\CommandSchedulerBundle\Fixtures\ORM\LoadScheduledCommandData;
use Dukecity\CommandSchedulerBundle\Command\ListCommand;

/**
 * Class ListCommandTest.
 */
class ListCommandTest extends AbstractCommandTest
{
    /**
     * Test scheduler:list
     */
    public function testListCommand(): void
    {
        // DataFixtures create 4 records
        $this->loadScheduledCommandFixtures();

        $output = $this->executeCommand(ListCommand::class, [])->getDisplay();

        // all
        $this->assertStringContainsString('CommandTestOne', $output);
        $this->assertStringContainsString('CommandTestTwo', $output);
        $this->assertStringContainsString('CommandTestThree', $output);
        $this->assertStringContainsString('CommandTestFour', $output);
    }
}

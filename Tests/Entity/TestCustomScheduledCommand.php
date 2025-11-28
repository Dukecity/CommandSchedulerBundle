<?php

namespace Dukecity\CommandSchedulerBundle\Tests\Entity;

use Dukecity\CommandSchedulerBundle\Entity\BaseScheduledCommand;

/**
 * Test-only custom entity extending BaseScheduledCommand.
 * Used to verify the extensibility pattern works correctly.
 */
class TestCustomScheduledCommand extends BaseScheduledCommand
{
    private ?string $customField = null;

    private ?string $category = null;

    public function getCustomField(): ?string
    {
        return $this->customField;
    }

    public function setCustomField(?string $customField): static
    {
        $this->customField = $customField;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;

        return $this;
    }
}

<?php

namespace Dukecity\CommandSchedulerBundle\Tests\Entity;

use Doctrine\ORM\Mapping as ORM;
use Dukecity\CommandSchedulerBundle\Entity\BaseScheduledCommand;

/**
 * Test-only custom entity extending BaseScheduledCommand.
 * Used to verify the extensibility pattern works correctly.
 */
#[ORM\Entity]
#[ORM\Table(name: 'scheduled_command')]
class TestCustomScheduledCommand extends BaseScheduledCommand
{
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $customField = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
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

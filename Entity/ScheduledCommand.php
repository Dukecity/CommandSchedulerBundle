<?php

namespace Dukecity\CommandSchedulerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Dukecity\CommandSchedulerBundle\Repository\ScheduledCommandRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Default scheduled command entity.
 * For backward compatibility, this class maintains the same name and table.
 *
 * To create a custom scheduled command entity, extend BaseScheduledCommand instead.
 *
 * @author  Julien Guyon <julienguyon@hotmail.com>
 */
#[ORM\Entity(repositoryClass: ScheduledCommandRepository::class)]
#[ORM\Table(name: "scheduled_command")]
#[UniqueEntity(fields: ["name"], groups: ['new'])]
class ScheduledCommand extends BaseScheduledCommand
{
    // Inherits everything from BaseScheduledCommand
}

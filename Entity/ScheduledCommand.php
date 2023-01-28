<?php

namespace Dukecity\CommandSchedulerBundle\Entity;

use Carbon\Carbon;
use Cron\CronExpression as CronExpressionLib;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Dukecity\CommandSchedulerBundle\Repository\ScheduledCommandRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Dukecity\CommandSchedulerBundle\Validator\Constraints as AssertDukecity;

/**
 * https://www.doctrine-project.org/2021/05/24/orm2.9.html
 * @author  Julien Guyon <julienguyon@hotmail.com>
 */
#[ORM\Entity(repositoryClass: ScheduledCommandRepository::class)]
#[ORM\Table(name: "scheduled_command")]
#[UniqueEntity(fields: ["name"])]
class ScheduledCommand
{
    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue(strategy: 'AUTO')]
    private $id; # temporary, otherwise EasyAdminBundle could not create new entries
    #private ?int $id = null;

    // see https://www.doctrine-project.org/projects/doctrine-orm/en/2.9/reference/transactions-and-concurrency.html
    #[ORM\Version]
    #[ORM\Column(type: Types::INTEGER)]
    private int $version = 0;

    #[ORM\Column(name: "created_at", type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTime $createdAt = null;

    #[Assert\NotBlank]
    #[ORM\Column(type: Types::STRING, length: 150, unique: true, nullable: false)]
    private string $name;

    #[Assert\NotBlank]
    #[ORM\Column(type: Types::STRING, length: 200, nullable: false)]
    private string $command;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $arguments = null;

    /**
     * @see http://www.abunchofutils.com/utils/developer/cron-expression-helper/
     */
    #[Assert\NotBlank]
    #[AssertDukecity\CronExpression]
    #[ORM\Column(type: Types::STRING, length: 200, nullable: true)]
    private string $cronExpression;

    #[Assert\Type(DateTime::class)]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTime $lastExecution = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $lastReturnCode = null;

    /** Log's file name (without path). */
    #[ORM\Column(type: Types::STRING, length: 150, nullable: true)]
    private ?string $logFile = null;

    ##[Assert\Type(Integer::class)]
    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    private int $priority;

    /** If true, command will be execute next time regardless cron expression. */
    #[ORM\Column(type: Types::BOOLEAN, nullable: false)]
    private bool $executeImmediately = false;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false)]
    private bool $disabled = false;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false)]
    private bool $locked = false;

    /**
     * Init new ScheduledCommand.
     */
    public function __construct()
    {
        $this->createdAt = new DateTime();
        #$this->setLastExecution(new DateTime());
        $this->lastExecution = null;
        $this->setLocked(false);
        $this->priority = 0;
        $this->version = 1;
    }

    public function __toString(): string
    {
        return $this->getName();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): ScheduledCommand
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): ScheduledCommand
    {
        $this->name = $name;

        return $this;
    }

    public function getCommand(): ?string
    {
        return $this->command;
    }

    public function setCommand(string $command): ScheduledCommand
    {
        $this->command = $command;

        return $this;
    }

    public function getArguments(): ?string
    {
        return $this->arguments;
    }

    public function setArguments(?string $arguments): ScheduledCommand
    {
        $this->arguments = $arguments;

        return $this;
    }

    public function getCronExpression(): ?string
    {
        return $this->cronExpression;
    }

    public function setCronExpression(string $cronExpression): ScheduledCommand
    {
        $this->cronExpression = $cronExpression;

        return $this;
    }

    public function getLastExecution(): ?DateTime
    {
        return $this->lastExecution;
    }

    public function setLastExecution(DateTime $lastExecution): ScheduledCommand
    {
        $this->lastExecution = $lastExecution;

        return $this;
    }

    public function getLogFile(): ?string
    {
        return $this->logFile;
    }

    public function setLogFile(?string $logFile): ScheduledCommand
    {
        $this->logFile = $logFile;

        return $this;
    }

    public function getLastReturnCode(): ?int
    {
        return $this->lastReturnCode;
    }

    public function setLastReturnCode(?int $lastReturnCode): ScheduledCommand
    {
        $this->lastReturnCode = $lastReturnCode;

        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): ScheduledCommand
    {
        $this->priority = $priority;

        return $this;
    }

    public function isExecuteImmediately(): bool
    {
        return $this->executeImmediately;
    }

    public function getExecuteImmediately(): bool
    {
        return $this->executeImmediately;
    }

    public function setExecuteImmediately(bool $executeImmediately): ScheduledCommand
    {
        $this->executeImmediately = $executeImmediately;

        return $this;
    }

    public function isDisabled(): ?bool
    {
        return $this->disabled;
    }

    public function getDisabled(): ?bool
    {
        return $this->disabled;
    }

    public function setDisabled(bool $disabled): ScheduledCommand
    {
        $this->disabled = $disabled;

        return $this;
    }

    public function isLocked(): ?bool
    {
        return $this->locked;
    }

    public function getLocked(): ?bool
    {
        return $this->locked;
    }

    public function setLocked(bool $locked): ScheduledCommand
    {
        $this->locked = $locked;

        return $this;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): ScheduledCommand
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function getNextRunDate(bool $checkExecuteImmediately = true): ?DateTime
    {
        if($this->getDisabled() || $this->getLocked())
        {return null;}

        if ($checkExecuteImmediately && $this->getExecuteImmediately()) {
            return new DateTime();
        }

        return (new CronExpressionLib($this->getCronExpression()))->getNextRunDate();
    }

    /**
     * Get a human readable format of the next run of the scheduled command
     */
    public function getNextRunDateForHumans(): ?string
    {
        try{
            return Carbon::instance($this->getNextRunDate())->diffForHumans();
        }
        catch (\Exception)
        {}

        return null;
    }
}

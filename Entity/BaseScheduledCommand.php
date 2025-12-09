<?php

namespace Dukecity\CommandSchedulerBundle\Entity;

use Cron\CronExpression as CronExpressionLib;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Lorisleiva\CronTranslator\CronTranslator;
use Symfony\Component\Validator\Constraints as Assert;
use Dukecity\CommandSchedulerBundle\Validator\Constraints as AssertDukecity;

/**
 * Base class for scheduled command entities.
 * Extend this class to create custom scheduled command entities with additional properties.
 *
 * @author  Julien Guyon <julienguyon@hotmail.com>
 */
#[ORM\MappedSuperclass]
abstract class BaseScheduledCommand implements ScheduledCommandInterface
{
    #[ORM\Id, ORM\Column(type: Types::INTEGER), ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id; # temporary, otherwise EasyAdminBundle could not create new entries
    #protected ?int $id = null;

    // see https://www.doctrine-project.org/projects/doctrine-orm/en/2.9/reference/transactions-and-concurrency.html
    #[ORM\Version]
    #[ORM\Column(type: Types::INTEGER)]
    protected int $version = 0;

    #[ORM\Column(name: "created_at", type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?DateTime $createdAt = null;

    #[Assert\NotBlank]
    #[ORM\Column(type: Types::STRING, length: 150, unique: true, nullable: false)]
    protected string $name;

    #[Assert\NotBlank]
    #[ORM\Column(type: Types::STRING, length: 200, nullable: false)]
    protected string $command;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $arguments = null;

    /**
     * @see http://www.abunchofutils.com/utils/developer/cron-expression-helper/
     */
    #[Assert\NotBlank]
    #[AssertDukecity\CronExpression]
    #[ORM\Column(type: Types::STRING, length: 200, nullable: true)]
    protected string $cronExpression = "";

    #[Assert\Type(DateTime::class)]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?DateTime $lastExecution = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected ?int $lastReturnCode = null;

    /** Log's file name (without path). */
    // #[Assert\NoSuspiciousCharacters] available in Symfony 6.3 only
    #[Assert\NotEqualTo('.log')]
    #[Assert\Regex('/[\w_\-À-ÿ].*(\.log){1}/')] // https://regex101.com/r/Pxkn66/2
    #[ORM\Column(type: Types::STRING, length: 150, nullable: true)]
    protected ?string $logFile = null;

    ##[Assert\Type(Integer::class)]
    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    protected int $priority = 0;

    /** If true, command will be execute next time regardless cron expression. */
    #[ORM\Column(type: Types::BOOLEAN, nullable: false)]
    protected bool $executeImmediately = false;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false)]
    protected bool $disabled = false;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false)]
    protected bool $locked = false;

    #[Assert\Url(requireTld: true)]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    protected ?string $pingBackUrl = null;

    #[Assert\Url(requireTld: true)]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    protected ?string $pingBackFailedUrl = null;

    #[ORM\Column(type: Types::TEXT, options: ['default' => ''])]
    protected string $notes = '';

    /**
     * Init new ScheduledCommand.
     */
    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->version = 1;
    }

    public function __toString(): string
    {
        return $this->getName() ?? '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCommand(): ?string
    {
        return $this->command;
    }

    public function setCommand(string $command): static
    {
        $this->command = $command;

        return $this;
    }

    public function getArguments(): ?string
    {
        return $this->arguments;
    }

    public function setArguments(?string $arguments): static
    {
        $this->arguments = $arguments;

        return $this;
    }

    public function getCronExpression(): ?string
    {
        return $this->cronExpression;
    }

    public function setCronExpression(string $cronExpression): static
    {
        $this->cronExpression = $cronExpression;

        return $this;
    }

    public function getLastExecution(): ?DateTime
    {
        return $this->lastExecution;
    }

    public function setLastExecution(DateTime $lastExecution): static
    {
        $this->lastExecution = $lastExecution;

        return $this;
    }

    public function getLogFile(): ?string
    {
        return $this->logFile;
    }

    public function setLogFile(?string $logFile): static
    {
        $this->logFile = $logFile;

        return $this;
    }

    public function getLastReturnCode(): ?int
    {
        return $this->lastReturnCode;
    }

    public function setLastReturnCode(?int $lastReturnCode): static
    {
        $this->lastReturnCode = $lastReturnCode;

        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): static
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

    public function setExecuteImmediately(bool $executeImmediately): static
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

    public function setDisabled(bool $disabled): static
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

    public function setLocked(bool $locked): static
    {
        $this->locked = $locked;

        return $this;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getPingBackUrl(): ?string
    {
        return $this->pingBackUrl;
    }

    public function setPingBackUrl(?string $pingBackUrl): static
    {
        $this->pingBackUrl = $pingBackUrl;

        return $this;
    }

    public function getPingBackFailedUrl(): ?string
    {
        return $this->pingBackFailedUrl;
    }

    public function setPingBackFailedUrl(?string $pingBackFailedUrl): static
    {
        $this->pingBackFailedUrl = $pingBackFailedUrl;

        return $this;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function setNotes(string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function getNextRunDate(bool $checkExecuteImmediately = true): ?DateTime
    {
        if ($this->getDisabled() || $this->getLocked()) {
            return null;
        }

        if ($checkExecuteImmediately && $this->getExecuteImmediately()) {
            return new DateTime();
        }

        return (new CronExpressionLib($this->getCronExpression()))->getNextRunDate();
    }

    public function getCronExpressionTranslated(): string
    {
        try {
            return CronTranslator::translate($this->getCronExpression());
        } catch (\Exception $e) {
            return 'error: could not translate cron expression';
        }
    }
}

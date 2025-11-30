<?php

namespace Dukecity\CommandSchedulerBundle\Entity;

use DateTime;

/**
 * Interface for scheduled command entities.
 *
 * This interface is used for type-hinting throughout the bundle.
 * To create a custom scheduled command entity, extend BaseScheduledCommand
 * instead of implementing this interface directly. The base class provides
 * ORM mappings, validation constraints, and helper methods.
 */
interface ScheduledCommandInterface
{
    public function getId(): ?int;

    public function setId(int $id): static;

    public function getName(): ?string;

    public function setName(string $name): static;

    public function getCommand(): ?string;

    public function setCommand(string $command): static;

    public function getArguments(): ?string;

    public function setArguments(?string $arguments): static;

    public function getCronExpression(): ?string;

    public function setCronExpression(string $cronExpression): static;

    public function getLastExecution(): ?DateTime;

    public function setLastExecution(DateTime $lastExecution): static;

    public function getLogFile(): ?string;

    public function setLogFile(?string $logFile): static;

    public function getLastReturnCode(): ?int;

    public function setLastReturnCode(?int $lastReturnCode): static;

    public function getPriority(): int;

    public function setPriority(int $priority): static;

    public function isExecuteImmediately(): bool;

    public function getExecuteImmediately(): bool;

    public function setExecuteImmediately(bool $executeImmediately): static;

    public function isDisabled(): ?bool;

    public function getDisabled(): ?bool;

    public function setDisabled(bool $disabled): static;

    public function isLocked(): ?bool;

    public function getLocked(): ?bool;

    public function setLocked(bool $locked): static;

    public function getCreatedAt(): ?DateTime;

    public function setCreatedAt(DateTime $createdAt): static;

    public function getPingBackUrl(): ?string;

    public function setPingBackUrl(?string $pingBackUrl): static;

    public function getPingBackFailedUrl(): ?string;

    public function setPingBackFailedUrl(?string $pingBackFailedUrl): static;

    public function getNotes(): string;

    public function setNotes(string $notes): static;

    public function getNextRunDate(bool $checkExecuteImmediately = true): ?DateTime;

    public function getCronExpressionTranslated(): string;

    public function __toString(): string;
}

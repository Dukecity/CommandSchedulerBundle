<?php

namespace Dukecity\CommandSchedulerBundle\Notification;

use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Notification\ChatNotificationInterface;
use Symfony\Component\Notifier\Notification\EmailNotificationInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;
use Symfony\Component\Notifier\Recipient\Recipient;
use Symfony\Component\Notifier\Recipient\RecipientInterface;

class CronMonitorNotification extends Notification implements EmailNotificationInterface, ChatNotificationInterface
{
    private array $scheduledCommands;

    public function __construct(array $scheduledCommands, private string $subject)
    {
        $this->scheduledCommands = $scheduledCommands;

        parent::__construct(sprintf($subject, gethostname(), date('Y-m-d H:i:s')));
    }

    public function getImportance(): string
    {
        return self::IMPORTANCE_MEDIUM;
    }

    public function asChatMessage(RecipientInterface $recipient, string $transport = null): ?ChatMessage
    {
        $arrFailedCommandNames = [];
        foreach ($this->scheduledCommands as $cmd) {
            $arrFailedCommandNames[] = $cmd->getName();
        }

        return new ChatMessage('[CronMonitoring] The following commands need to be checked. 
            '.implode(', ', $arrFailedCommandNames));
    }

    public function getContent(): string
    {
        $message = '';
        foreach ($this->scheduledCommands as $command) {
            $message .= sprintf(
                "%s: returncode %s, locked: %s, last execution: %s\n\n",
                $command->getName(),
                $command->getLastReturnCode(),
                $command->getLocked(),
                $command->getLastExecution()->format('Y-m-d H:i')
            );
        }

        return "CronMonitoring: The following commands need to be checked.\n\n".$message;
    }

    public function asEmailMessage(
        Recipient | EmailRecipientInterface $recipient,
        string $transport = null
    ): ?EmailMessage {
        return EmailMessage::fromNotification($this, $recipient);
    }
}

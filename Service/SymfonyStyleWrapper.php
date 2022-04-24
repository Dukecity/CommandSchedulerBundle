<?php

namespace Dukecity\CommandSchedulerBundle\Service;

use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Wrapper for info() is missing in symfony 4
 */
class SymfonyStyleWrapper extends SymfonyStyle
{
    public function info($message): void
    {
        if(method_exists(SymfonyStyle::class, "info"))
        {
            # Symfony 5
            parent::info($message);
        }
        else
        {
            # Symfony 4
            $this->comment($message);
        }
    }
}

<?php

namespace Dukecity\CommandSchedulerBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
#[\Attribute] class CronExpression extends Constraint
{
    /**
     * Constraint error message.
     */
    public string $message = 'The string "{{ string }}" is not a valid cron expression.';
}

<?php

namespace Dukecity\CommandSchedulerBundle\Tests\Constraints;

use Dukecity\CommandSchedulerBundle\Validator\Constraints\CronExpression;
use Dukecity\CommandSchedulerBundle\Validator\Constraints\CronExpressionValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * Class CronExpressionValidatorTest.
 */
class CronExpressionValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): CronExpressionValidator
    {
        return new CronExpressionValidator();
    }

    #[DataProvider('getValidValues')]
    public function testValidValues(string $value): void
    {
        $this->validator->validate($value, new CronExpression(['message' => '']));

        $this->assertNoViolation();
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function getValidValues(): array
    {
        return [
            ['* * * * *'],
            ['@daily'],
            ['@yearly'],
            ['*/10 * * * *'],
        ];
    }

    #[DataProvider('getInvalidValues')]
    public function testInvalidValues(string $value): void
    {
        $constraint = new CronExpression(['message' => 'myMessage']);

        $this->validator->validate($value, $constraint);

        $this->buildViolation('myMessage') # works in ci
        #$this->buildViolation('The string "{{ string }}" is not a valid cron expression.') # works on local mac runs (difference?)
            ->setParameter('{{ string }}', $value)
            ->assertRaised();
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function getInvalidValues(): array
    {
        return [
            ['*/10 * * *'],
            //['*/5 * * * ?'],
            ['sometimes'],
            ['never'],
            ['*****'],
            ['* * * * * * *'],
            ['* * * * * *'],
        ];
    }
}

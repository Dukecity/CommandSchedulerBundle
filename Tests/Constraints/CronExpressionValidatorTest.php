<?php

namespace Dukecity\CommandSchedulerBundle\Tests\Constraints;

use Dukecity\CommandSchedulerBundle\Validator\Constraints\CronExpression;
use Dukecity\CommandSchedulerBundle\Validator\Constraints\CronExpressionValidator;
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

    /**
     * @dataProvider getValidValues
     * @param string $value
     */
    public function testValidValues(string $value): void
    {
        $this->validator->validate($value, new CronExpression(['message' => '']));

        $this->assertNoViolation();
    }

    public static function getValidValues(): array
    {
        return [
            ['* * * * *'],
            ['@daily'],
            ['@yearly'],
            ['*/10 * * * *'],
        ];
    }

    /**
     * @dataProvider getInvalidValues
     * @param string $value
     */
    public function testInvalidValues(string $value): void
    {
        $constraint = new CronExpression(['message' => 'myMessage']);

        $this->validator->validate($value, $constraint);

        $this->buildViolation('myMessage')
            ->setParameter('{{ string }}', $value)
            ->assertRaised();
    }

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

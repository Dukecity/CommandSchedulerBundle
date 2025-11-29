<?php

namespace App\Tests\DependencyInjection;

use Dukecity\CommandSchedulerBundle\DependencyInjection\DukecityCommandSchedulerExtension;
use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommand;
use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommandInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

class DukecityCommandSchedulerExtensionTest extends TestCase
{
    /**
     * @param array  $config
     * @param array  $expected
     */
    #[DataProvider('provideConfiguration')]
    public function testConfiguration(string $rootNode, array $config, array $expected): void
    {
        $builder = new ContainerBuilder();

        $ext = new DukecityCommandSchedulerExtension();

        $ext->load($config, $builder);

        foreach ($expected[$rootNode] as $key => $value) {
            $this->assertEquals($value, $builder->getParameter($rootNode.'.'.$key));
        }
    }

    /**
     * Check if config files are correct.
     *
     * @return array
     */
    public static function provideConfiguration(): array
    {
        $rootNode = 'dukecity_command_scheduler';

        $dir = __DIR__.'/configuration_set/';

        $configFiles = glob($dir.'config_*.yml');
        $resultFiles = glob($dir.'result_*.yml');

        sort($configFiles);
        sort($resultFiles);

        $tests = [];

        foreach ($configFiles as $k => $file) {
            $config = Yaml::parse(file_get_contents($file));
            $expected = Yaml::parse(file_get_contents($resultFiles[$k]));
            $tests[] = [$rootNode, $config, $expected];
        }

        return $tests;
    }

    public function testScheduledCommandClassDefaultValue(): void
    {
        $builder = new ContainerBuilder();
        $ext = new DukecityCommandSchedulerExtension();

        // Load with minimal config (no scheduled_command_class specified)
        $ext->load([['doctrine_manager' => 'default']], $builder);

        $this->assertTrue($builder->hasParameter('dukecity_command_scheduler.scheduled_command_class'));
        $this->assertSame(
            ScheduledCommand::class,
            $builder->getParameter('dukecity_command_scheduler.scheduled_command_class')
        );
    }

    public function testScheduledCommandClassCustomValue(): void
    {
        $builder = new ContainerBuilder();
        $ext = new DukecityCommandSchedulerExtension();

        $customClass = 'App\Entity\MyCustomScheduledCommand';
        $ext->load([['scheduled_command_class' => $customClass, 'doctrine_manager' => 'default']], $builder);

        $this->assertSame(
            $customClass,
            $builder->getParameter('dukecity_command_scheduler.scheduled_command_class')
        );
    }

    public function testResolveTargetEntityConfigured(): void
    {
        $builder = new ContainerBuilder();
        $ext = new DukecityCommandSchedulerExtension();

        $ext->load([['doctrine_manager' => 'default']], $builder);

        $this->assertTrue($builder->hasParameter('doctrine.orm.resolve_target_entities'));

        $resolveTargetEntities = $builder->getParameter('doctrine.orm.resolve_target_entities');
        $this->assertIsArray($resolveTargetEntities);
        $this->assertArrayHasKey(ScheduledCommandInterface::class, $resolveTargetEntities);
        $this->assertSame(
            ScheduledCommand::class,
            $resolveTargetEntities[ScheduledCommandInterface::class]
        );
    }

    public function testResolveTargetEntityWithCustomClass(): void
    {
        $builder = new ContainerBuilder();
        $ext = new DukecityCommandSchedulerExtension();

        $customClass = 'App\Entity\CustomCommand';
        $ext->load([['scheduled_command_class' => $customClass, 'doctrine_manager' => 'default']], $builder);

        $resolveTargetEntities = $builder->getParameter('doctrine.orm.resolve_target_entities');
        $this->assertSame(
            $customClass,
            $resolveTargetEntities[ScheduledCommandInterface::class]
        );
    }

    public function testUseDefaultEntityParameterTrueWhenDefaultClass(): void
    {
        $builder = new ContainerBuilder();
        $ext = new DukecityCommandSchedulerExtension();

        // Load with default entity class (or no class specified)
        $ext->load([['doctrine_manager' => 'default']], $builder);

        $this->assertTrue($builder->hasParameter('dukecity_command_scheduler.use_default_entity'));
        $this->assertTrue($builder->getParameter('dukecity_command_scheduler.use_default_entity'));
    }

    public function testUseDefaultEntityParameterFalseWhenCustomClass(): void
    {
        $builder = new ContainerBuilder();
        $ext = new DukecityCommandSchedulerExtension();

        $customClass = 'App\Entity\CustomScheduledCommand';
        $ext->load([['scheduled_command_class' => $customClass, 'doctrine_manager' => 'default']], $builder);

        $this->assertTrue($builder->hasParameter('dukecity_command_scheduler.use_default_entity'));
        $this->assertFalse($builder->getParameter('dukecity_command_scheduler.use_default_entity'));
    }
}

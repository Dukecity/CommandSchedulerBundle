<?php

namespace App\Tests\DependencyInjection;

use Dukecity\CommandSchedulerBundle\DependencyInjection\DukecityCommandSchedulerExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

class DukecityCommandSchedulerExtensionTest extends TestCase
{
    /**
     * @dataProvider provideConfiguration
     *
     * @param string $rootNode
     * @param array  $config
     * @param array  $expected
     */
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
    public function provideConfiguration(): array
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
}

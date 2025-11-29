<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace Dukecity\CommandSchedulerBundle\DependencyInjection;

use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommand;
use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommandInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Extension\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}.
 *
 * @see https://symfony.com/doc/current/bundles/configuration.html
 */
class DukecityCommandSchedulerExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        // Default
        $config = $this->processConfiguration(new Configuration(), $configs);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.php');

        foreach ($config as $key => $value) {
            $container->setParameter('dukecity_command_scheduler.'.$key, $value);
        }

        // Set parameter to control whether bundle's default entity mapping is enabled
        // This is used by DoctrineOrmMappingsPass in the bundle's build() method
        $useDefaultEntity = $config['scheduled_command_class'] === ScheduledCommand::class;
        $container->setParameter('dukecity_command_scheduler.use_default_entity', $useDefaultEntity);

        // Configure ResolveTargetEntity to map interface to configured entity class
        $this->configureResolveTargetEntity($container, $config['scheduled_command_class']);
    }

    /**
     * Configure Doctrine's ResolveTargetEntity listener to map the interface to the concrete entity class.
     */
    private function configureResolveTargetEntity(ContainerBuilder $container, string $entityClass): void
    {
        $resolveTargetEntities = [];
        if ($container->hasParameter('doctrine.orm.resolve_target_entities')) {
            $resolveTargetEntities = $container->getParameter('doctrine.orm.resolve_target_entities');
        }

        $resolveTargetEntities[ScheduledCommandInterface::class] = $entityClass;
        $container->setParameter('doctrine.orm.resolve_target_entities', $resolveTargetEntities);
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias(): string
    {
        return 'dukecity_command_scheduler';
    }
}

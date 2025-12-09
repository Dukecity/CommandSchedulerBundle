<?php

namespace Dukecity\CommandSchedulerBundle\Tests\Integration;

use App\Tests\App\AppKernelCustomEntity;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommandInterface;
use Dukecity\CommandSchedulerBundle\Service\ScheduledCommandFactory;
use Dukecity\CommandSchedulerBundle\Tests\Entity\TestCustomScheduledCommand;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Integration tests for custom entity extensibility.
 * These tests verify that a custom entity class works through the full stack
 * when configured via scheduled_command_class.
 */
class CustomEntityIntegrationTest extends WebTestCase
{
    private KernelBrowser $client;
    private AbstractDatabaseTool $databaseTool;
    private EntityManagerInterface $em;
    private static bool $schemaCreated = false;

    protected static function getKernelClass(): string
    {
        return AppKernelCustomEntity::class;
    }

    public static function setUpBeforeClass(): void
    {
        // Ensure we start with a fresh database for custom entity tests
        $dbPath = __DIR__.'/../../build/test_custom_entity.db';
        if (file_exists($dbPath)) {
            unlink($dbPath);
        }
        self::$schemaCreated = false;
    }

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->followRedirects();

        $container = $this->client->getContainer()->get('test.service_container');
        $this->databaseTool = $container->get(DatabaseToolCollection::class)->get();
        $this->em = $container->get('doctrine')->getManager();

        // Create schema manually on first run to ensure custom columns exist
        if (!self::$schemaCreated) {
            $schemaTool = new SchemaTool($this->em);
            $metadata = $this->em->getMetadataFactory()->getAllMetadata();
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
            self::$schemaCreated = true;
        } else {
            // Clear data for subsequent tests
            $this->em->createQuery('DELETE FROM '.TestCustomScheduledCommand::class)->execute();
        }
    }

    /**
     * Test that the configured entity class parameter is correct.
     */
    public function testScheduledCommandClassIsConfigured(): void
    {
        $container = $this->client->getContainer();
        $configuredClass = $container->getParameter('dukecity_command_scheduler.scheduled_command_class');

        $this->assertSame(TestCustomScheduledCommand::class, $configuredClass);
    }

    /**
     * Test that the factory creates the custom entity type.
     */
    public function testFactoryCreatesCustomEntity(): void
    {
        $container = $this->client->getContainer()->get('test.service_container');
        $factory = $container->get(ScheduledCommandFactory::class);

        $entity = $factory->create();

        $this->assertInstanceOf(TestCustomScheduledCommand::class, $entity);
        $this->assertInstanceOf(ScheduledCommandInterface::class, $entity);
    }

    /**
     * Test that custom entity persists to database via repository.
     */
    public function testCustomEntityPersistsViaRepository(): void
    {
        $container = $this->client->getContainer()->get('test.service_container');
        $factory = $container->get(ScheduledCommandFactory::class);

        /** @var TestCustomScheduledCommand $entity */
        $entity = $factory->create();
        $entity->setName('test-persist')
            ->setCommand('about')
            ->setCronExpression('@daily')
            ->setCustomField('custom-value')
            ->setCategory('test-category');

        $this->em->persist($entity);
        $this->em->flush();
        $entityId = $entity->getId();

        // Clear entity manager to force reload from database
        $this->em->clear();

        $repository = $this->em->getRepository(TestCustomScheduledCommand::class);
        $loadedEntity = $repository->find($entityId);

        $this->assertInstanceOf(TestCustomScheduledCommand::class, $loadedEntity);
        $this->assertSame('test-persist', $loadedEntity->getName());
        $this->assertSame('about', $loadedEntity->getCommand());
        $this->assertSame('@daily', $loadedEntity->getCronExpression());
        $this->assertSame('custom-value', $loadedEntity->getCustomField());
        $this->assertSame('test-category', $loadedEntity->getCategory());
    }

    /**
     * Test that custom fields are correctly saved and loaded.
     */
    public function testCustomFieldsSaveAndLoad(): void
    {
        $container = $this->client->getContainer()->get('test.service_container');
        $factory = $container->get(ScheduledCommandFactory::class);

        /** @var TestCustomScheduledCommand $entity */
        $entity = $factory->create();
        $entity->setName('custom-fields-test')
            ->setCommand('debug:container')
            ->setCronExpression('* * * * *')
            ->setCustomField('my-custom-value')
            ->setCategory('my-category');

        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        // Query by custom field
        $repository = $this->em->getRepository(TestCustomScheduledCommand::class);
        $found = $repository->findOneBy(['customField' => 'my-custom-value']);

        $this->assertNotNull($found);
        $this->assertSame('custom-fields-test', $found->getName());
        $this->assertSame('my-category', $found->getCategory());
    }

    /**
     * Test that ResolveTargetEntity configuration is correctly set up.
     */
    public function testResolveTargetEntityIsConfigured(): void
    {
        $container = $this->client->getContainer()->get('test.service_container');
        $factory = $container->get(ScheduledCommandFactory::class);

        /** @var TestCustomScheduledCommand $entity */
        $entity = $factory->create();
        $entity->setName('resolve-target-test')
            ->setCommand('about')
            ->setCronExpression('@daily');

        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        // Verify the resolve_target_entities parameter is configured correctly
        $resolveTargetEntities = $this->client->getContainer()->getParameter('doctrine.orm.resolve_target_entities');
        $this->assertArrayHasKey(ScheduledCommandInterface::class, $resolveTargetEntities);
        $this->assertSame(TestCustomScheduledCommand::class, $resolveTargetEntities[ScheduledCommandInterface::class]);

        // Verify repository returns custom entity instances
        $repository = $this->em->getRepository(TestCustomScheduledCommand::class);
        $entities = $repository->findAll();

        $this->assertNotEmpty($entities);
        $this->assertInstanceOf(TestCustomScheduledCommand::class, $entities[0]);
        $this->assertInstanceOf(ScheduledCommandInterface::class, $entities[0]);
    }

    /**
     * Test that custom entity works with HTTP controller - create new command.
     */
    public function testCustomEntityWorksWithControllerCreate(): void
    {
        $crawler = $this->client->request('GET', '/command-scheduler/detail/edit');

        // Debug: check response
        $this->assertResponseIsSuccessful();

        // Check if form exists
        $this->assertGreaterThan(0, $crawler->filter('form')->count(), 'Form should exist on page');

        $buttonCrawlerNode = $crawler->selectButton('command_scheduler_detail_save');
        $form = $buttonCrawlerNode->form();

        $form->setValues([
            'command_scheduler_detail[name]' => 'http-create-test',
            'command_scheduler_detail[command]' => 'about',
            'command_scheduler_detail[arguments]' => '',
            'command_scheduler_detail[cronExpression]' => '@daily',
            'command_scheduler_detail[logFile]' => '',
            'command_scheduler_detail[priority]' => '0',
        ]);

        $crawler = $this->client->submit($form);

        // Verify redirect to list with new entry
        $this->assertEquals(
            1,
            $crawler->filterXPath('//a[contains(@href, "/command-scheduler/action/toggle/")]')->count()
        );

        // Verify entity is correct type in database
        $this->em->clear();
        $repository = $this->em->getRepository(TestCustomScheduledCommand::class);
        $entity = $repository->findOneBy(['name' => 'http-create-test']);

        $this->assertInstanceOf(TestCustomScheduledCommand::class, $entity);
        $this->assertSame('http-create-test', $entity->getName());
        $this->assertSame('about', $entity->getCommand());
    }

    /**
     * Test that editing existing custom entity works via HTTP.
     */
    public function testCustomEntityWorksWithControllerEdit(): void
    {
        // First create an entity directly
        $container = $this->client->getContainer()->get('test.service_container');
        $factory = $container->get(ScheduledCommandFactory::class);

        /** @var TestCustomScheduledCommand $entity */
        $entity = $factory->create();
        $entity->setName('http-edit-test')
            ->setCommand('about')
            ->setCronExpression('@daily')
            ->setCustomField('before-edit');

        $this->em->persist($entity);
        $this->em->flush();
        $entityId = $entity->getId();
        $this->em->clear();

        // Edit via HTTP
        $crawler = $this->client->request('GET', '/command-scheduler/detail/edit/'.$entityId);
        $this->assertResponseIsSuccessful();

        $buttonCrawlerNode = $crawler->selectButton('command_scheduler_detail_save');
        $form = $buttonCrawlerNode->form();

        $form->get('command_scheduler_detail[arguments]')->setValue('--edited');
        $this->client->submit($form);

        // Verify changes persisted
        $this->em->clear();
        $repository = $this->em->getRepository(TestCustomScheduledCommand::class);
        $updatedEntity = $repository->find($entityId);

        $this->assertInstanceOf(TestCustomScheduledCommand::class, $updatedEntity);
        $this->assertSame('--edited', $updatedEntity->getArguments());
        // Custom field should be preserved
        $this->assertSame('before-edit', $updatedEntity->getCustomField());
    }
}

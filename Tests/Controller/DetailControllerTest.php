<?php /** @noinspection PhpCSValidationInspection */

namespace Dukecity\CommandSchedulerBundle\Tests\Controller;

use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommand;
use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommandInterface;
use Dukecity\CommandSchedulerBundle\Fixtures\ORM\LoadScheduledCommandData;
use Dukecity\CommandSchedulerBundle\Service\ScheduledCommandFactory;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class DetailControllerTest.
 */
class DetailControllerTest extends WebTestCase
{
    protected AbstractDatabaseTool $databaseTool;
    private KernelBrowser $client;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->followRedirects();

        $this->databaseTool = $this->client->getContainer()->get(DatabaseToolCollection::class)->get();
    }
    
    /**
     * Test "Create a new command" button.
     */
    public function testInitNewScheduledCommand(): void
    {
        $crawler = $this->client->request('GET', '/command-scheduler/detail/edit');
        $this->assertEquals(1, $crawler->filter('button[id="command_scheduler_detail_save"]')->count());
    }

    /**
     * Test "Edit a command" action.
     */
    public function testInitEditScheduledCommand(): void
    {
        // DataFixtures create 4 records
        $this->databaseTool->loadFixtures([LoadScheduledCommandData::class]);

        $crawler = $this->client->request('GET', '/command-scheduler/detail/edit/1');

        $this->assertEquals(1, $crawler->filterXPath('//button[@id="command_scheduler_detail_save"]')->count());

        $buttonCrawlerNode = $crawler->selectButton('Save');
        $form = $buttonCrawlerNode->form();
        $fixtureSet = [
            'command_scheduler_detail[name]' => 'CommandTestOne',
            'command_scheduler_detail[command]' => 'debug:container',
            'command_scheduler_detail[arguments]' => '--help',
            'command_scheduler_detail[cronExpression]' => '@daily',
            'command_scheduler_detail[logFile]' => 'one.log',
            'command_scheduler_detail[priority]' => '100',
            'command_scheduler_detail[save]' => '',
            'command_scheduler_detail[pingBackUrl]' => '',
            'command_scheduler_detail[pingBackFailedUrl]' => '',
            'command_scheduler_detail[notes]' => ''
        ];

        $this->assertEquals($fixtureSet, $form->getValues());
    }

    /**
     * Test new scheduling creation.
     */
    public function testNewSave(): void
    {
        $this->databaseTool->loadFixtures([]);

        $crawler = $this->client->request('GET', '/command-scheduler/detail/edit');
        $buttonCrawlerNode = $crawler->selectButton('Save');
        $form = $buttonCrawlerNode->form();

        $form->setValues([
            'command_scheduler_detail[name]' => 'wtc',
            'command_scheduler_detail[command]' => 'about',
            'command_scheduler_detail[arguments]' => '--help',
            'command_scheduler_detail[cronExpression]' => '@daily',
            'command_scheduler_detail[logFile]' => 'wtc.log',
            'command_scheduler_detail[priority]' => '5',
        ]);
        $crawler = $this->client->submit($form);

        $this->assertEquals(1, $crawler->filterXPath('//a[contains(@href, "/command-scheduler/action/toggle/")]')->count());
        $this->assertEquals('wtc', trim($crawler->filter('td')->eq(1)->text()));
    }

    /**
     * Test "Edit and save a scheduling".
     */
    public function testEditSave(): void
    {
        // DataFixtures create 4 records
        $this->databaseTool->loadFixtures([LoadScheduledCommandData::class]);

        $crawler = $this->client->request('GET', '/command-scheduler/detail/edit/1');
        $buttonCrawlerNode = $crawler->selectButton('Save');
        $form = $buttonCrawlerNode->form();

        $form->get('command_scheduler_detail[name]')->setValue('edited one');
        $form->get('command_scheduler_detail[cronExpression]')->setValue('* * * * *');
        $crawler = $this->client->submit($form);

        $this->assertEquals(5, $crawler->filterXPath('//a[contains(@href, "/command-scheduler/action/toggle/")]')->count());
        $this->assertEquals('edited one', trim($crawler->filter('td')->eq(1)->text()));
    }

    /**
     * Test that factory service is available in controller.
     */
    public function testFactoryServiceIsAvailable(): void
    {
        $container = $this->client->getContainer()->get('test.service_container');
        $factory = $container->get(ScheduledCommandFactory::class);

        $this->assertInstanceOf(ScheduledCommandFactory::class, $factory);
        $this->assertInstanceOf(ScheduledCommandInterface::class, $factory->create());
    }

    /**
     * Test that creating a new command uses the factory and returns correct entity type.
     */
    public function testNewCommandUsesFactory(): void
    {
        $this->databaseTool->loadFixtures([]);

        $crawler = $this->client->request('GET', '/command-scheduler/detail/edit');
        $buttonCrawlerNode = $crawler->selectButton('Save');
        $form = $buttonCrawlerNode->form();

        $form->setValues([
            'command_scheduler_detail[name]' => 'factory-test',
            'command_scheduler_detail[command]' => 'about',
            'command_scheduler_detail[arguments]' => '',
            'command_scheduler_detail[cronExpression]' => '@daily',
            'command_scheduler_detail[logFile]' => '',
            'command_scheduler_detail[priority]' => '0',
        ]);
        $crawler = $this->client->submit($form);

        // Verify redirect to list with new entry
        $this->assertEquals(1, $crawler->filterXPath('//a[contains(@href, "/command-scheduler/action/toggle/")]')->count());

        // Verify the entity was created and is of correct type
        $container = $this->client->getContainer()->get('test.service_container');
        $em = $container->get('doctrine')->getManager();
        $entity = $em->getRepository(ScheduledCommand::class)->findOneBy(['name' => 'factory-test']);

        $this->assertInstanceOf(ScheduledCommandInterface::class, $entity);
        $this->assertInstanceOf(ScheduledCommand::class, $entity);
        $this->assertSame('factory-test', $entity->getName());
        $this->assertSame('about', $entity->getCommand());
    }

    /**
     * Test that editing an existing command works with interface-typed entity.
     */
    public function testEditExistingCommandWorksWithInterface(): void
    {
        $this->databaseTool->loadFixtures([LoadScheduledCommandData::class]);

        // First, verify entity is loaded via interface
        $container = $this->client->getContainer()->get('test.service_container');
        $em = $container->get('doctrine')->getManager();
        $entity = $em->getRepository(ScheduledCommand::class)->find(1);

        $this->assertInstanceOf(ScheduledCommandInterface::class, $entity);

        // Edit the command
        $crawler = $this->client->request('GET', '/command-scheduler/detail/edit/1');
        $buttonCrawlerNode = $crawler->selectButton('Save');
        $form = $buttonCrawlerNode->form();

        $form->get('command_scheduler_detail[arguments]')->setValue('--updated');
        $this->client->submit($form);

        // Verify the change was persisted
        $em->clear();
        $updatedEntity = $em->getRepository(ScheduledCommand::class)->find(1);

        $this->assertInstanceOf(ScheduledCommandInterface::class, $updatedEntity);
        $this->assertSame('--updated', $updatedEntity->getArguments());
    }
}

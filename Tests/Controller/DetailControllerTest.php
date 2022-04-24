<?php /** @noinspection PhpCSValidationInspection */

namespace Dukecity\CommandSchedulerBundle\Tests\Controller;

use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommand;
use Dukecity\CommandSchedulerBundle\Fixtures\ORM\LoadScheduledCommandData;
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
}

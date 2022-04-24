<?php

namespace Dukecity\CommandSchedulerBundle\Tests\Controller;

use Doctrine\ORM\EntityManager;
use Dukecity\CommandSchedulerBundle\Fixtures\ORM\LoadScheduledCommandData;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class ListControllerTest.
 */
class ListControllerTest extends WebTestCase
{
    protected AbstractDatabaseTool $databaseTool;
    private KernelBrowser $client;
    private EntityManager $em;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->followRedirects();

        $this->em = static::$kernel->getContainer()->get('doctrine')->getManager();
        $this->databaseTool = $this->client->getContainer()->get(DatabaseToolCollection::class)->get();
    }

    /**
     * Test list display.
     */
    public function testIndex(): void
    {
        // DataFixtures create 4 records
        $this->databaseTool->loadFixtures([LoadScheduledCommandData::class]);

        $crawler = $this->client->request('GET', '/command-scheduler/list');
        $this->assertEquals(5, $crawler->filter('a[href^="/command-scheduler/action/toggle/"]')->count());
    }

    /**
     * Test permanent deletion on command.
     */
    public function testRemove(): void
    {
        // DataFixtures create 4 records
        $this->databaseTool->loadFixtures([LoadScheduledCommandData::class]);

        //toggle off
        $crawler = $this->client->request('GET', '/command-scheduler/action/remove/1');

        $this->assertEquals(4, $crawler->filter('a[href^="/command-scheduler/action/toggle/"]')->count());
    }

    /**
     * Test On/Off toggle on list.
     */
    public function testToggle(): void
    {
        // DataFixtures create 4 records
        $this->databaseTool->loadFixtures([LoadScheduledCommandData::class]);

        //toggle off
        $crawler = $this->client->request('GET', '/command-scheduler/action/toggle/1');
        $this->assertEquals(1, $crawler->filter(
            'a[href="/command-scheduler/action/toggle/1"] > i[class="bi bi-power text-danger"]')
            ->count());

        //toggle on
        $crawler = $this->client->request('GET', '/command-scheduler/action/toggle/1');
        $this->assertEquals(0, $crawler->filter('a[href="/command-scheduler/action/toggle/1"] > i[class="bi bi-power text-danger"]')->count());
    }

    /**
     * Test Execute now button on list.
     */
    public function testExecute(): void
    {
        // DataFixtures create 4 records
        $this->databaseTool->loadFixtures([LoadScheduledCommandData::class]);

        //call execute now button
        $crawler = $this->client->request('GET', '/command-scheduler/action/execute/1');

        #
        $this->assertStringContainsString('Command -CommandTestOne- will be executed during the next', $this->client->getResponse()->getContent());

        //$this->assertEquals(1, $crawler->filter('a[href="/command-scheduler/action/execute/1"] > span[class="text-muted glyphicon glyphicon-play"]')->count());
        //$this->assertEquals(1, $crawler->filterXPath('//div[contains("Command will be executed during the next")')->count());
    }

    /**
     * Test unlock button on list.
     */
    public function testUnlock(): void
    {
        // DataFixtures create 4 records
        $this->databaseTool->loadFixtures([LoadScheduledCommandData::class]);

        // One command is locked in fixture (2)
        $crawler = $this->client->request('GET', '/command-scheduler/list');
        $this->assertEquals(1, $crawler->filter('a[href="/command-scheduler/action/unlock/2"]')->count());

        $crawler = $this->client->request('GET', '/command-scheduler/action/unlock/2');
        $this->assertEquals(0, $crawler->filter('a[href="/command-scheduler/action/unlock/2"]')->count());
    }
}

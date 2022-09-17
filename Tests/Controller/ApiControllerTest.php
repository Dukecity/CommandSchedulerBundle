<?php

namespace Dukecity\CommandSchedulerBundle\Tests\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Exception\ORMException;
use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommand;
use Dukecity\CommandSchedulerBundle\Fixtures\ORM\LoadScheduledCommandData;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ApiControllerTest extends WebTestCase
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
        
        $this->em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->databaseTool = $this->client->getContainer()->get(DatabaseToolCollection::class)->get();
    }

    /**
     * Test list all command URL with should return json.
     */
    public function testConsoleCommands(): void
    {
        // List all available console commands
        $this->client->request('GET', '/command-scheduler/api/console_commands');
        self::assertResponseIsSuccessful();

        $jsonResponse = $this->client->getResponse()->getContent();
        $jsonArray = json_decode($jsonResponse, true, 512, JSON_THROW_ON_ERROR);

        $this->assertGreaterThanOrEqual(1, count($jsonArray));
        $this->assertArrayHasKey('_global', $jsonArray);
        $this->assertSame("assets:install", $jsonArray["assets"]["assets:install"]);
        $this->assertSame("debug:autowiring", $jsonArray["debug"]["debug:autowiring"]);
    }

    /**
     * Test list all command URL with should return json.
     */
    public function testConsoleCommandsDetailsAll(): void
    {
        // List all available console commands
        $this->client->request('GET', '/command-scheduler/api/console_commands_details');
        self::assertResponseIsSuccessful();

        $jsonResponse = $this->client->getResponse()->getContent();
        $commands = json_decode($jsonResponse, true, 512, JSON_THROW_ON_ERROR);

        $this->assertIsArray($commands);
        $this->assertArrayHasKey('about', $commands);
        $this->assertSame("about", $commands["about"]["name"]);

        $this->assertArrayHasKey('list', $commands);
        $this->assertArrayHasKey('cache:clear', $commands);
    }

    /**
     * Test list all command URL with should return json.
     */
    public function testConsoleCommandsDetails(): void
    {
        // List all available console commands
        $this->client->request('GET', '/command-scheduler/api/console_commands_details/about,list,cache:clear,asserts:install');
        self::assertResponseIsSuccessful();

        $jsonResponse = $this->client->getResponse()->getContent();
        $commands = json_decode($jsonResponse, true, 512, JSON_THROW_ON_ERROR);

        $this->assertIsArray($commands);
        $this->assertArrayHasKey('about', $commands);
        $this->assertSame("about", $commands["about"]["name"]);

        $this->assertArrayHasKey('list', $commands);
        $this->assertArrayHasKey('cache:clear', $commands);
    }

    /**
     * Test list all command URL with should return json.
     */
    public function testList(): void
    {
        // DataFixtures create 4 records
        $this->databaseTool->loadFixtures([LoadScheduledCommandData::class]);

        // List 4 Commands
        $this->client->request('GET', '/command-scheduler/api/list');
        self::assertResponseIsSuccessful();

        $jsonResponse = $this->client->getResponse()->getContent();
        $jsonArray = json_decode($jsonResponse, true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(5, $jsonArray);
        $this->assertSame('CommandTestOne', $jsonArray['CommandTestOne']['NAME']);
    }

    /**
     * Test monitoring URL with json.
     */
    public function testMonitorWithErrors(): void
    {
        // DataFixtures create 4 records
        $this->databaseTool->loadFixtures([LoadScheduledCommandData::class]);

        // One command is locked in fixture (2), another have a -1 return code as lastReturn (4)
        $this->client->request('GET', '/command-scheduler/monitor');
        self::assertResponseStatusCodeSame(Response::HTTP_EXPECTATION_FAILED);

        // We expect 2 commands
        $jsonResponse = $this->client->getResponse()->getContent();
        $jsonArray = json_decode($jsonResponse, true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(2, $jsonArray);
    }

    /**
     * Test monitoring URL with json.
     */
    public function testMonitorWithoutErrors(): void
    {
        // DataFixtures create 4 records
        $this->databaseTool->loadFixtures([LoadScheduledCommandData::class]);

        $two = $this->em->getRepository(ScheduledCommand::class)->find(2);
        $four = $this->em->getRepository(ScheduledCommand::class)->find(4);
        $two->setLocked(false);
        $four->setLastReturnCode(0);

        try {
            $this->em->flush();
        } catch (OptimisticLockException | ORMException $e) {
        }

        // One command is locked in fixture (2), another have a -1 return code as lastReturn (4)
        $this->client->request('GET', '/command-scheduler/monitor');
        self::assertResponseIsSuccessful();

        $jsonResponse = $this->client->getResponse()->getContent();
        $jsonArray = json_decode($jsonResponse, true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(0, $jsonArray);
    }

    /**
     * Test translations
     */
    public function testTranslateCronExpression(): void
    {
        $this->client->request('GET', '/command-scheduler/api/trans_cron_expression/* * * * */en');
        self::assertResponseIsSuccessful();

        $jsonResponse = $this->client->getResponse()->getContent();
        $jsonArray = json_decode($jsonResponse, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(0, $jsonArray["status"]);
        $this->assertSame("Every minute", $jsonArray["message"]);
    }
}
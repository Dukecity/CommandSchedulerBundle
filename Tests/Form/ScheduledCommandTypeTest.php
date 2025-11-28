<?php

namespace Dukecity\CommandSchedulerBundle\Tests\Form;

use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommand;
use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommandInterface;
use Dukecity\CommandSchedulerBundle\Form\Type\ScheduledCommandType;
use Dukecity\CommandSchedulerBundle\Service\ScheduledCommandFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * Tests for ScheduledCommandType form.
 */
class ScheduledCommandTypeTest extends WebTestCase
{
    private FormFactoryInterface $formFactory;
    private ScheduledCommandFactory $entityFactory;

    protected function setUp(): void
    {
        $client = self::createClient();
        $container = $client->getContainer()->get('test.service_container');
        $this->formFactory = $container->get('form.factory');
        $this->entityFactory = $container->get(ScheduledCommandFactory::class);
    }

    public function testFormTypeIsRegistered(): void
    {
        $entity = $this->entityFactory->create();
        $form = $this->formFactory->create(ScheduledCommandType::class, $entity);

        $this->assertNotNull($form);
    }

    public function testFormHasCorrectFields(): void
    {
        $entity = $this->entityFactory->create();
        $form = $this->formFactory->create(ScheduledCommandType::class, $entity);

        $this->assertTrue($form->has('command'));
        $this->assertTrue($form->has('arguments'));
        $this->assertTrue($form->has('cronExpression'));
        $this->assertTrue($form->has('logFile'));
        $this->assertTrue($form->has('priority'));
        $this->assertTrue($form->has('executeImmediately'));
        $this->assertTrue($form->has('disabled'));
        $this->assertTrue($form->has('pingBackUrl'));
        $this->assertTrue($form->has('pingBackFailedUrl'));
        $this->assertTrue($form->has('notes'));
        $this->assertTrue($form->has('save'));
    }

    public function testFormWorksWithDefaultEntity(): void
    {
        $entity = $this->entityFactory->create();

        // Verify it's the default ScheduledCommand
        $this->assertInstanceOf(ScheduledCommand::class, $entity);

        $form = $this->formFactory->create(ScheduledCommandType::class, $entity);
        $formData = $form->getData();

        $this->assertInstanceOf(ScheduledCommandInterface::class, $formData);
        $this->assertSame($entity, $formData);
    }

    public function testFormHasCorrectDataClass(): void
    {
        $entity = $this->entityFactory->create();
        $form = $this->formFactory->create(ScheduledCommandType::class, $entity);

        $config = $form->getConfig();
        $dataClass = $config->getOption('data_class');

        $this->assertSame(ScheduledCommand::class, $dataClass);
    }

    public function testFormSubmitWithValidData(): void
    {
        $entity = $this->entityFactory->create();
        $entity->setName('test-command');

        $form = $this->formFactory->create(ScheduledCommandType::class, $entity);

        $formData = [
            'name' => 'test-command',
            'command' => 'cache:clear',
            'arguments' => '--env=prod',
            'cronExpression' => '@daily',
            'logFile' => 'test.log',
            'priority' => 5,
            'executeImmediately' => false,
            'disabled' => false,
            'pingBackUrl' => '',
            'pingBackFailedUrl' => '',
            'notes' => 'Test notes',
        ];

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        /** @var ScheduledCommandInterface $submittedEntity */
        $submittedEntity = $form->getData();
        $this->assertSame('test-command', $submittedEntity->getName());
        $this->assertSame('cache:clear', $submittedEntity->getCommand());
        $this->assertSame('--env=prod', $submittedEntity->getArguments());
        $this->assertSame('@daily', $submittedEntity->getCronExpression());
        $this->assertSame('test.log', $submittedEntity->getLogFile());
        $this->assertSame(5, $submittedEntity->getPriority());
        $this->assertFalse($submittedEntity->isExecuteImmediately());
        $this->assertFalse($submittedEntity->isDisabled());
        $this->assertSame('Test notes', $submittedEntity->getNotes());
    }

    public function testFormTranslationDomain(): void
    {
        $entity = $this->entityFactory->create();
        $form = $this->formFactory->create(ScheduledCommandType::class, $entity);

        $config = $form->getConfig();
        $translationDomain = $config->getOption('translation_domain');

        $this->assertSame('DukecityCommandScheduler', $translationDomain);
    }
}

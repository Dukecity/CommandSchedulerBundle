<?php

namespace App\Controller\Admin;

use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommand;
use Dukecity\CommandSchedulerBundle\Form\Type\CommandChoiceType;
use Dukecity\CommandSchedulerBundle\Service\CommandParser;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @link https://symfony.com/doc/current/bundles/EasyAdminBundle/actions.html
 * @link https://github.com/Dukecity/CommandSchedulerBundle/wiki/Integrations
 * @Security("is_granted('ROLE_ADMIN')")
 */
class ScheduledCommandCrudController extends AbstractCrudController
{
    public function __construct(private CommandParser $commandParser)
    {
    }

    public static function getEntityFqcn(): string
    {
        return ScheduledCommand::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('ScheduledCommand')
            ->setEntityLabelInPlural('ScheduledCommands')
            ->setSearchFields(['name', 'command']);
    }

    public function configureFields(string $pageName): iterable
    {
        # translation_domain: 'DukecityCommandScheduler'
        #$translationDomain = $context->getI18n()->setTranslationDomain();
        $id = IdField::new('id', 'ID')->hideOnForm()->setSortable(false)->hideOnIndex();
        $name = TextField::new('name');

        # EasyAdmin3 could not handle multidimensional Arrays ;(
        $command = ChoiceField::new('command')
            #->setChoices($this->commandParser->getCommands())
            ->setChoices($this->commandParser->reduceNamespacedCommands($this->commandParser->getCommands()))
            ->setFormType(CommandChoiceType::class)
        ;

        $arguments = TextField::new('arguments');
        $cronExpression = TextField::new('cronExpression');

        $logFile = TextField::new('logFile');
        $priority = IntegerField::new('priority');
        $lastExecution = DateTimeField::new('lastExecution');
        $lastReturnCode = IntegerField::new('lastReturnCode');
        $disabled = BooleanField::new('disabled');
        $locked = BooleanField::new('locked');
        $executeImmediately = BooleanField::new('executeImmediately');
        #$description = TextareaField::new('description');
        #$createdAt = DateTimeField::new('createdAt');

        # LISTING
        if (Crud::PAGE_INDEX === $pageName) {
            return [
                $id,
                $name,
                $disabled,
                $command,
                $arguments,
                $cronExpression,
                $priority,
                $lastExecution->setFormat('short', 'short'),
                $lastReturnCode,
                $locked
            ];
        }

        # CREATE/EDIT
        return [
            FormField::addPanel('Basic information'),
            $id,
            $name,
            $command,
            $arguments,
            $cronExpression,
            $priority,
            $logFile,
            $disabled,
            $executeImmediately
        ];
    }
}

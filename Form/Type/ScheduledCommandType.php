<?php

namespace Dukecity\CommandSchedulerBundle\Form\Type;

use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommand;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ScheduledCommandType.
 *
 * @author  Julien Guyon <julienguyon@hotmail.com>
 */
class ScheduledCommandType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void
        {
            /** @var ScheduledCommand $scheduledCommand */
            $scheduledCommand = $event->getData();
            $form = $event->getForm();
            $options = [
                'label' => 'detail.name',
                'required' => true,
            ];

            if (! is_null($scheduledCommand->getId())) {
                $options['attr'] = ['readonly' => 'readonly'];
            }

            $form->add('name',
                TextType::class,
                $options);
        });

        $builder->add(
            'command',
            CommandChoiceType::class,
            [
                'label' => 'detail.command',
                'required' => true,
            ]
        );

        $builder->add(
            'arguments',
            TextType::class,
            [
                'label' => 'detail.arguments',
                'required' => false,
            ]
        );

        $builder->add(
            'cronExpression',
            TextType::class,
            [
                'label' => 'detail.cronExpression',
                'required' => true,
            ]
        );

        $builder->add(
            'logFile',
            TextType::class,
            [
                'label' => 'detail.logFile',
                'required' => false,
                'help' => 'File will be stored to the root "log" folder and append ".log" if not provided.'
            ],
        );

        $builder->add(
            'priority',
            IntegerType::class,
            [
                'label' => 'detail.priority',
                'empty_data' => 0,
                'required' => false,
            ]
        );

        $builder->add(
            'executeImmediately',
            CheckboxType::class,
            [
                'label' => 'detail.executeImmediately',
                'required' => false,
            ]
        );

        $builder->add(
            'disabled',
            CheckboxType::class,
            [
                'label' => 'detail.disabled',
                'required' => false,
            ]
        );

        $builder->add(
            'pingBackUrl',
            UrlType::class,
            [
                'label' => 'detail.pingBackUrl',
                'required' => false,
            ]
        );

        $builder->add(
            'pingBackFailedUrl',
            UrlType::class,
            [
                'label' => 'detail.pingBackFailedUrl',
                'required' => false,
            ]
        );

        $builder->add(
            'notes',
            TextareaType::class,
            [
                'label' => 'detail.notes',
                'required' => false,
                'empty_data' => ''
            ]
        );

        $builder->add(
            'save',
            SubmitType::class,
            [
                'label' => 'action.save',
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => ScheduledCommand::class,
                'wrapper_attr' => 'default_wrapper',
                'translation_domain' => 'DukecityCommandScheduler',
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'command_scheduler_detail';
    }
}

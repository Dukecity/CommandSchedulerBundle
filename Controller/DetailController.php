<?php

namespace Dukecity\CommandSchedulerBundle\Controller;

use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommand;
use Dukecity\CommandSchedulerBundle\Form\Type\ScheduledCommandType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DetailController.
 *
 * @author  Julien Guyon <julienguyon@hotmail.com>
 */
class DetailController extends AbstractBaseController
{
    /**
     * Handle display of new/existing ScheduledCommand object.
     */
    public function edit(Request $request, ?int $id = null): Response
    {
        $validationGroups = [];
        $scheduledCommand = $id ? $this->getDoctrineManager()->getRepository(ScheduledCommand::class)->find($id) : null;
        if (!$scheduledCommand) {
            $scheduledCommand = new ScheduledCommand();
            $validationGroups[] = 'new';
        }

        $form = $this->createForm(ScheduledCommandType::class, $scheduledCommand, [
            'validation_groups' => $validationGroups
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            // check if we have an xml-read error for commands
            if ('error' === $scheduledCommand->getCommand()) {
                $this->addFlash('error', 'ERROR: please check php bin/console list --format=xml');

                return $this->redirectToRoute('dukecity_command_scheduler_list');
            }

            $em = $this->getDoctrineManager();
            $em->persist($scheduledCommand);
            $em->flush();

            // Add a flash message and do a redirect to the list
            $this->addFlash('success', $this->translator->trans('flash.success', [], 'DukecityCommandScheduler'));

            return $this->redirectToRoute('dukecity_command_scheduler_list');
        }

        return $this->render(
            '@DukecityCommandScheduler/Detail/index.html.twig',
            ['scheduledCommandForm' => $form->createView()]
        );
    }
}

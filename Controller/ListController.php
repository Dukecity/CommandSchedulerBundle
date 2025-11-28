<?php

namespace Dukecity\CommandSchedulerBundle\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ListController.
 *
 * @author Julien Guyon <julienguyon@hotmail.com>
 */
class ListController extends AbstractBaseController
{
    private int $lockTimeout = 3600;
    private LoggerInterface $logger;
    private string $scheduledCommandClass = '';

    public function setLockTimeout(int $lockTimeout): void
    {
        $this->lockTimeout = $lockTimeout;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function setScheduledCommandClass(string $scheduledCommandClass): void
    {
        $this->scheduledCommandClass = $scheduledCommandClass;
    }

    public function indexAction(): Response
    {
        $scheduledCommands = $this->getDoctrineManager()->getRepository(
            $this->scheduledCommandClass
        )->findAll();
        #)->findAllSortedByNextRuntime();

        return $this->render(
            '@DukecityCommandScheduler/List/index.html.twig',
            ['scheduledCommands' => $scheduledCommands]
        );
    }

    public function removeAction($id): RedirectResponse
    {
        $entityManager = $this->getDoctrineManager();
        $scheduledCommand = $entityManager->getRepository($this->scheduledCommandClass)->find($id);
        $entityManager->remove($scheduledCommand);
        $entityManager->flush();

        // Add a flash message and do a redirect to the list
        $this->addFlash('success', $this->translator->trans('flash.deleted', [], 'DukecityCommandScheduler'));

        return $this->redirectToRoute('dukecity_command_scheduler_list');
    }

    /**
     * Toggle enabled/disabled.
     */
    public function toggleAction($id): RedirectResponse
    {
        $scheduledCommand = $this->getDoctrineManager()->getRepository($this->scheduledCommandClass)->find($id);
        $scheduledCommand->setDisabled(!$scheduledCommand->isDisabled());
        $this->getDoctrineManager()->flush();

        return $this->redirectToRoute('dukecity_command_scheduler_list');
    }

    public function executeAction($id, Request $request): RedirectResponse
    {
        $scheduledCommand = $this->getDoctrineManager()->getRepository($this->scheduledCommandClass)->find($id);
        $scheduledCommand->setExecuteImmediately(true);
        $this->getDoctrineManager()->flush();

        // Add a flash message and do a redirect to the list
        $this->addFlash('success', $this->translator->trans('flash.execute', ["%name%" => $scheduledCommand->getName()], 'DukecityCommandScheduler'));

        if ($request->query->has('referer')) {
            return $this->redirect($request->getSchemeAndHttpHost().urldecode($request->query->get('referer')));
        }

        return $this->redirectToRoute('dukecity_command_scheduler_list');
    }

    public function unlockAction($id, Request $request): RedirectResponse
    {
        $scheduledCommand = $this->getDoctrineManager()->getRepository($this->scheduledCommandClass)->find($id);
        $scheduledCommand->setLocked(false);
        $this->getDoctrineManager()->flush();

        // Add a flash message and do a redirect to the list
        $this->addFlash('success', $this->translator->trans('flash.unlocked', [], 'DukecityCommandScheduler'));

        if ($request->query->has('referer')) {
            return $this->redirect($request->getSchemeAndHttpHost().urldecode($request->query->get('referer')));
        }

        return $this->redirectToRoute('dukecity_command_scheduler_list');
    }
}

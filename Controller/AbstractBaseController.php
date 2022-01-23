<?php

namespace Dukecity\CommandSchedulerBundle\Controller;

use Doctrine\Persistence\ObjectManager;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface as ContractsTranslatorInterface;

/**
 * Class BaseController.
 *
 * @author Julien Guyon <julienguyon@hotmail.com>
 */
abstract class AbstractBaseController extends AbstractController
{
    private string $managerName;
    private ManagerRegistry $managerRegistry;

    public function setManagerRegistry(ManagerRegistry $managerRegistry): void
    {
        $this->managerRegistry = $managerRegistry;
    }

    protected ContractsTranslatorInterface $translator;

    public function setManagerName(string $managerName): void
    {
        $this->managerName = $managerName;
    }

    public function getManagerName(): string
    {
        return $this->managerName;
    }

    public function setTranslator(ContractsTranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    protected function getDoctrineManager(): ObjectManager
    {
        return $this->managerRegistry->getManager($this->managerName);
    }
}

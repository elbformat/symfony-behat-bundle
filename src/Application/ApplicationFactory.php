<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Application;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Factories makes stuff unit-testable.
 *
 * @author Hannes Giesenow <hannes.giesenow@elbformat.de>
 */
class ApplicationFactory
{
    protected KernelInterface $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public function create(): Application
    {
        $application = new Application($this->kernel);
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);

        return $application;
    }
}

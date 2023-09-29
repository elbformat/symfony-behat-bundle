<?php

namespace Elbformat\SymfonyBehatBundle\DependencyInjection;

use Elbformat\SymfonyBehatBundle\Logger\TestLogger;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Use TestLogger for logging
 *
 * @author Hannes Giesenow <hannes.giesenow@elbformat.de>
 */
class TestLoggerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $container->setAlias('logger', TestLogger::class);
    }
}

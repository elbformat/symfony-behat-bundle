<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle;

use Elbformat\SymfonyBehatBundle\DependencyInjection\MonologCompilerPass;
use Elbformat\SymfonyBehatBundle\DependencyInjection\SwiftmailerCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Hannes Giesenow <hannes.giesenow@elbformat.de>
 */
final class ElbformatSymfonyBehatBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new MonologCompilerPass());
        $container->addCompilerPass(new SwiftmailerCompilerPass());
    }
}

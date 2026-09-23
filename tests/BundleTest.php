<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Tests;

use Elbformat\SymfonyBehatBundle\DependencyInjection\TestLoggerCompilerPass;
use Elbformat\SymfonyBehatBundle\ElbformatSymfonyBehatBundle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[CoversClass(ElbformatSymfonyBehatBundle::class)]
class BundleTest extends TestCase
{
    public function testBuild(): void
    {
        $bundle = new ElbformatSymfonyBehatBundle();
        $container = $this->createMock(ContainerBuilder::class);
        $container->expects($this->exactly(1))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(TestLoggerCompilerPass::class))
        ;
        $bundle->build($container);
    }
}

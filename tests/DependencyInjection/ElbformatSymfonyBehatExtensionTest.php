<?php

namespace DependencyInjection;

use Elbformat\SymfonyBehatBundle\Context\BrowserContext;
use Elbformat\SymfonyBehatBundle\Context\CommandContext;
use Elbformat\SymfonyBehatBundle\Context\MonologContext;
use Elbformat\SymfonyBehatBundle\DependencyInjection\ElbformatSymfonyBehatExtension;
use Elbformat\SymfonyBehatBundle\ElbformatSymfonyBehatBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ElbformatSymfonyBehatExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $ext = new ElbformatSymfonyBehatExtension();
        $containerBuilder = $this->createMock(ContainerBuilder::class);

        $containerBuilder->expects($this->atLeast(7))
            ->method('setDefinition')
        ->withConsecutive(
            [],
            [],
            [],
            [],
            [BrowserContext::class,$this->callback(function (Definition $def) {
                return BrowserContext::class === $def->getClass();
            })],
            [CommandContext::class,$this->callback(function (Definition $def) {
                return CommandContext::class === $def->getClass();
            })],
            [MonologContext::class,$this->callback(function (Definition $def) {
                return MonologContext::class === $def->getClass();
            })]
        );

        $ext->load([], $containerBuilder);
    }
}

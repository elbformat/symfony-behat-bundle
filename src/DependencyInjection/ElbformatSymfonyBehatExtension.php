<?php

namespace Elbformat\SymfonyBehatBundle\DependencyInjection;

use Elbformat\SymfonyBehatBundle\Context\CommandContext;
use Elbformat\SymfonyBehatBundle\Context\DateContext;
use Elbformat\SymfonyBehatBundle\Context\MailerContext;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @author Hannes Giesenow <hannes.giesenow@elbformat.de>
 */
class ElbformatSymfonyBehatExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        if (class_exists('Symfony\\Bundle\\FrameworkBundle\\Console\\Application')) {
            $commandContext = new Definition(CommandContext::class);
            $commandContext->setAutoconfigured(true);
            $commandContext->setAutowired(true);
            $container->setDefinition(CommandContext::class, $commandContext);
        }

        if (class_exists('Symfony\\Component\\Mailer\\Mailer')) {
            $mailerContext = new Definition(MailerContext::class);
            $mailerContext->setAutoconfigured(true);
            $mailerContext->setAutowired(true);
            $mailerContext->setArgument('$projectDir', '%kernel.project_dir%');
            $container->setDefinition(MailerContext::class, $mailerContext);
        }

        if (class_exists('SlopeIt\\ClockMock\\ClockMock')) {
            $dateContext = new Definition(DateContext::class);
            $container->setDefinition(DateContext::class, $dateContext);
        }
    }
}

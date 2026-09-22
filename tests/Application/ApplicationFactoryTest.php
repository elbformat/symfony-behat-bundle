<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Tests\Application;

use Elbformat\SymfonyBehatBundle\Application\ApplicationFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpKernel\Kernel;

#[CoversClass(ApplicationFactory::class)]
class ApplicationFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $kernel = $this->createMock(Kernel::class);
        $factory = new ApplicationFactory($kernel);
        $app = $factory->create();
        $this->assertInstanceOf(Application::class, $app);
    }
}

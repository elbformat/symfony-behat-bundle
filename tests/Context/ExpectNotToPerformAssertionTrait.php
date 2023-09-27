<?php

namespace Elbformat\SymfonyBehatBundle\Tests\Context;

trait ExpectNotToPerformAssertionTrait
{
    public function expectNotToPerformAssertions(): void
    {
        // Absolutely NOT RECOMMENDED, but needed workaround for code coverage
        //parent::expectNotToPerformAssertions();
        $this->assertTrue(true);
    }
}

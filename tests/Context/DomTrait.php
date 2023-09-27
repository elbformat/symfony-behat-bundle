<?php

namespace Elbformat\SymfonyBehatBundle\Tests\Context;

use Elbformat\SymfonyBehatBundle\Browser\State;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait DomTrait
{
    protected ?State $state = null;

    protected function setDom(string $dom): void
    {
        $this->state->update(Request::create('/'), new Response($dom, 200));
    }
}

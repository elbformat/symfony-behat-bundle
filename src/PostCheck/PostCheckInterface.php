<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\PostCheck;

interface PostCheckInterface
{
    // The check passes, when true is returned
    public function __invoke(mixed $data, object $object): bool;
}

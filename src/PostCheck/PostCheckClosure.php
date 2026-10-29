<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\PostCheck;

use Webmozart\Assert\Assert;

class PostCheckClosure implements PostCheckInterface
{
    /**
     * @param callable(mixed $data):bool $closure
     */
    public function __construct(
        protected mixed $closure,
    ) {
        Assert::isCallable($this->closure);
    }

    public function __invoke(mixed $data): bool
    {
        return ($this->closure)($data);
    }
}

<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Tests\fixtures\Enum;

enum MyBackedEnum: string
{
    case case1 = 'case1';
    case case2 = 'case2';
}

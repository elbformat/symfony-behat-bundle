<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Context;

use Behat\Behat\Context\Context;
use Behat\Hook\BeforeScenario;
use Behat\Step\Given;
use SlopeIt\ClockMock\ClockMock;

/**
 * Use ClockMock (https://github.com/slope-it/clock-mock) with uopz to mock the current date.
 * ! Make sure the ext-uopz is installed and activated !
 *
 * @author Hannes Giesenow <hannes.giesenow@elbformat.de>
 */
class DateContext implements Context
{
    #[BeforeScenario]
    public function reset(): void
    {
        ClockMock::reset();
    }

    #[Given('the current date is :date')]
    public function theCurrentDateIs(string $date): void
    {
        ClockMock::freeze(new \DateTime($date));
    }
}

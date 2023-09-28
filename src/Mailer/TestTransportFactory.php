<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Mailer;

use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

class TestTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): TransportInterface
    {
        if ('test' === $dsn->getScheme()) {
            return new TestTransport();
        }

        throw new UnsupportedSchemeException($dsn, 'test', $this->getSupportedSchemes());
    }

    protected function getSupportedSchemes(): array
    {
        return ['test'];
    }
}

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
        if ('file' === $dsn->getScheme()) {
            return new TestTransport($dsn->getOption('folder'));
        }

        throw new UnsupportedSchemeException($dsn, 'file', $this->getSupportedSchemes());
    }

    protected function getSupportedSchemes(): array
    {
        return ['file'];
    }
}

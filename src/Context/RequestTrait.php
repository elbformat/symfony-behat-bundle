<?php

namespace Elbformat\SymfonyBehatBundle\Context;

use Elbformat\SymfonyBehatBundle\Browser\State;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

trait RequestTrait
{
    protected KernelInterface $kernel;
    protected State $state;

    protected function doRequest(Request $request): void
    {
        // Reboot kernel
        $this->kernel->shutdown();
        $response = $this->kernel->handle($request);
        $this->state->update($request, $response);
    }

    /** @param array<string,string> $server */
    protected function buildRequest(string $uri, string $method = 'GET', array $server = [], ?string $content = null, array $parameters = [], array $files = []): Request
    {
        $server['SCRIPT_FILENAME'] = $server['SCRIPT_FILENAME'] ?? 'index.php';

        /** @psalm-suppress MixedArgument */
        return Request::create($uri, $method, $parameters, $this->state->getCookies(), $this->convertFileInformation($files) ?? [], $server, $content);
    }

    // Copied from FileBag and modified to enable test mode
    /** @psalm-suppress all */
    protected function convertFileInformation($file)
    {
        /** @psalm-suppress MixedArgument */
        $keys = array_keys($file);
        sort($keys);

        $fileKeys = ['error', 'name', 'size', 'tmp_name', 'type'];
        if ($fileKeys === $keys) {
            if (\UPLOAD_ERR_NO_FILE === $file['error']) {
                $file = null;
            } else {
                /** @psalm-suppress MixedArgument */
                $file = new UploadedFile($file['tmp_name'], $file['name'], $file['type'], $file['error'], true);
            }
        } else {
            $file = array_map(fn ($v): mixed => $v instanceof UploadedFile || \is_array($v) ? $this->convertFileInformation($v) : $v, $file);
            if (array_keys($keys) === $keys) {
                $file = array_filter($file);
            }
        }

        return $file;
    }
}

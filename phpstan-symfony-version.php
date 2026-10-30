<?php
use Composer\InstalledVersions;

$config = ['parameters' => ['ignoreErrors' => []]];

$version = InstalledVersions::getVersion('symfony/property-info');

// Method getTypes() is undefined in PropertyTypeExtractorInterface
if (version_compare($version, '7.3', '>=')) {
    $config['parameters']['ignoreErrors'][] = [
        'identifier' => 'cast.string',
        'message' => '#Cannot cast mixed to string.#',
        'path' => __DIR__.'/src/Helper/PropertyTypeExtractor.php',
        'count' => 1,
    ];
}

// Method getType() is undefined in PropertyTypeExtractorInterface
if (version_compare($version, '7.3', '<')) {
    $config['parameters']['ignoreErrors'][] = [
        'identifier' => 'cast.string',
        'message' => '#Cannot cast mixed to string.#',
        'path' => __DIR__.'/src/Helper/PropertyTypeExtractor.php',
        'count' => 1,
    ];
    $config['parameters']['ignoreErrors'][] = [
        'identifier' => 'argument.type',
        'message' => '#Parameter \#1 \$object_or_class of function method_exists expects object\|string, mixed given.#',
        'path' => __DIR__.'/src/Helper/PropertyTypeExtractor.php',
        'count' => 1,
    ];
    $config['parameters']['ignoreErrors'][] = [
        'identifier' => 'method.nonObject',
        'message' => '#Cannot call method getWrappedType\(\) on class-string\|object.#',
        'path' => __DIR__.'/src/Helper/PropertyTypeExtractor.php',
        'count' => 1,
    ];
}

return $config;
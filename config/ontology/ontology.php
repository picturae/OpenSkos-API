<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return function (ContainerConfigurator $configurator) use ($container) {
    $group = basename(__DIR__);

    $files = array_map(function ($fullpath) use ($group) {
        $name = @array_shift(explode('.', basename($fullpath)));

        return ["%${group}.${name}%", $fullpath];
    }, glob(__DIR__.'/*.yaml'));

    // Import all vocabulary files
    foreach ($files as $descriptor) {
        $configurator->import($descriptor[1]);
    }

    // Merge all vocabulary names
    $names = array_map(function ($descriptor) {
        return $descriptor[0];
    }, $files);

    $container->setParameter('ontology', $names);
};

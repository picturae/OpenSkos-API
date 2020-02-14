#!/usr/bin/env php
<?php

$prefix      = 'php7';
$command     = 'apk add %s-%s';
$cwd         = getcwd();
$known       = [];
$descriptors = [
    ['pipe', 'r'],
    ['pipe', 'w'],
    ['file', '/tmp/error-output.txt', 'a'],
];

$app = json_decode(file_get_contents($argv[1]??(__DIR__.'/../composer.json')));
$dep = json_decode(file_get_contents($argv[2]??(__DIR__.'/../composer.lock')));
array_push($dep->packages, $app);

foreach($dep->packages as $package) {
    $dependencies = array_merge(
        (array) ($package->require ?? []),
        (array) ($package->{'require-dev'} ?? [])
    );
    foreach($dependencies as $name => $version) {
        if (substr($name, 0, 4) !== 'ext-') continue;

        if (isset($known[$name])) {
            continue;
        } else {
            $known[$name] = true;
        }

        $pipes = [];
        $cmd   = sprintf($command,$prefix,substr($name,4));
        echo ' ---> ', $cmd, PHP_EOL;
        $process = proc_open($cmd, $descriptors, $pipes, $cwd, $_ENV);
        while(!feof($pipes[1])) {
            printf('      ' . fgets($pipes[1]));
        }
        echo PHP_EOL;
        fclose($pipes[0]);
        fclose($pipes[1]);
        proc_close($process);

    }
}

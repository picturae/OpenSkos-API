<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude(['var', 'vendor'])
    ->in(__DIR__)
;

return PhpCsFixer\Config::create()
    ->setRules([
        '@PSR2' => true,
        'strict_param' => true,
        'array_syntax' => ['syntax' => 'short'],
        '@Symfony' => true,
    ])
    ->setFinder($finder)
;
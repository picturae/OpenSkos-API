<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude(['var', 'vendor'])
    ->in(__DIR__)
;

return PhpCsFixer\Config::create()
    ->setRules([
        '@PSR2' => true,
        '@Symfony' => true,
        'strict_param' => true,
        'array_syntax' => ['syntax' => 'short'],
        'single_line_throw' => false,
        'binary_operator_spaces' => [
            'default' => 'align',
        ],
    ])
    ->setFinder($finder)
;

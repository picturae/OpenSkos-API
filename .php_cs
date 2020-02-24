<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude([
        'data/docker/composer-ext-install.php',
        'var',
        'vendor',
    ])
    ->in(__DIR__)
;

return PhpCsFixer\Config::create()
    ->setRules([
        '@PSR2' => true,
        '@Symfony' => true,
        'no_trailing_whitespace' => true,
        'no_trailing_whitespace_in_comment' => true,
        'phpdoc_scalar' => true,
        'strict_param' => true,
        'array_syntax' => ['syntax' => 'short'],
        'single_line_throw' => false,
        'binary_operator_spaces' => [
            'default' => 'align',
        ],
    ])
    ->setFinder($finder)
;

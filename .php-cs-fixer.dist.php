<?php

/**
 * @see https://cs.symfony.com/doc/rules/index.html
 */
$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests')
    ->in(__DIR__.'/migrations')
;

$config = new PhpCsFixer\Config('Biblioteca');
$config->setLineEnding(PHP_EOL);
$config->setFinder($finder);
$config->setRiskyAllowed(true);
$config->setRules([
    '@Symfony' => true,
    'increment_style' => false,
    'logical_operators' => true,
    'no_superfluous_phpdoc_tags' => false,
    'phpdoc_align' => [
        'align' => 'left',
    ],
    'phpdoc_separation' => false,
    'phpdoc_summary' => false,
    'visibility_required' => [
        'elements' => ['property', 'method', 'const'],
    ],
    'yoda_style' => false,
]);

return $config;

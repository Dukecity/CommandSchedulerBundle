<?php

$finder = PhpCsFixer\Finder::create()
    ->notPath('vendor')
    ->in(__DIR__)
    ->name('*.php')
    ->exclude('build')
    ->exclude('.github')
    ->exclude('Resources/**')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        #'@PSR2' => true,
        'no_superfluous_phpdoc_tags' => false,
        'array_syntax' => ['syntax' => 'short'],
        #'lineLimit' => 200,
        #'absoluteLineLimit' => 0,
    ])
    ->setFinder($finder)
    ;

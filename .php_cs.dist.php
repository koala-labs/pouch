<?php

/**
 * Suggested code style guidelines
 *
 * - https://github.com/FriendsOfPHP/PHP-CS-Fixer
 * - https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
 */

$finder = PhpCsFixer\Finder::create()
    ->exclude('templates')
    ->exclude('bootstrap')
    ->exclude('public')
    ->exclude('private')
    ->exclude('resources')
    ->exclude('vendor')
    ->exclude('database/seeds/configs')
    ->in(__DIR__);

return (new PhpCsFixer\Config())->setRules([
    '@PSR12' => true,
    'binary_operator_spaces' => [
        'default' => 'align_single_space_minimal',
    ],
])->setFinder($finder);

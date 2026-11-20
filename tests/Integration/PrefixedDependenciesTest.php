<?php

/**
 * Exercises vendor-prefixed/ with only its own autoloader, like in production
 */

test('the bundled plugin-update-checker loads', function () {
    $root = dirname(__DIR__, 2);
    $script = <<<PHP
        require '$root/vendor-prefixed/autoload.php';
        require '$root/vendor-prefixed/yahnis-elsts/plugin-update-checker/plugin-update-checker.php';
        PHP;
    $script .= <<<'PHP'
        var_dump(class_exists(YahnisElsts\PluginUpdateChecker\v5\PucFactory::class));
        PHP;

    $output = (string) shell_exec(sprintf('php -r %s 2>&1', escapeshellarg($script)));

    expect($output)->toContain('bool(true)');
});

<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Finder\Finder;

$file = __DIR__ . '/../build/workshop-manager.phar';

if (file_exists($file)) {
    unlink($file);
}

$phar = new Phar('workshop-manager.phar', 0, 'workshop-manager.phar');
$phar->setSignatureAlgorithm(\Phar::SHA1);
$phar->startBuffering();

$finderSort = function ($a, $b) {
    return strcmp(strtr($a->getRealPath(), '\\', '/'), strtr($b->getRealPath(), '\\', '/'));
};

$finder = new Finder;
$finder
    ->files()
    ->ignoreVCS(true)
    ->name('*.php')
    ->notPath(__DIR__ . '/../test')
    ->in(__DIR__ . '/../app')
    ->in(__DIR__ . '/../src')
    ->in(__DIR__ . '/../vendor')
    ->sort($finderSort);

foreach ($finder as $file) {
    $path = strtr(str_replace(dirname(__DIR__) . DIRECTORY_SEPARATOR, '', $file->getRealPath()), '\\', '/');
    $phar->addFromString($phar, file_get_contents($file));
}

$content = file_get_contents(__DIR__ . '/../bin/workshop-manager');
$content = preg_replace('{^#!/usr/bin/env php\s*}', '', $content);
$phar->addFromString('bin/workshop-manager', $content);
$phar->setStub(<<<'EOF'

#!/usr/bin/env php
<?php

require 'phar://workshop-manager.phar/bin/workshop-manager';

__HALT_COMPILER();
EOF
);

$phar->stopBuffering();
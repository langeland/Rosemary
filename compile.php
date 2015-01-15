#!/usr/bin/env php
<?php
// create with alias "project.phar"
$phar = new Phar('../rosemary.phar', 0, 'rosemary.phar');

$phar->buildFromDirectory(__DIR__ . '/');
$phar->setStub("<?php Phar::mapPhar(); include 'phar://rosemary.phar/application.php'; __HALT_COMPILER(); ?>");


chmod('../rosemary.phar', 0755);
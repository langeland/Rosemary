#!/usr/bin/env php
<?php
// application.php

if (file_exists(__DIR__ . '/Libraries/autoload.php')) {
	require __DIR__ . '/Libraries/autoload.php';
} else {
	echo 'Missing autoload.php, update by the composer.' . PHP_EOL;
	exit(2);
}

define('ROOT_DIR', __DIR__);

$application = new \Symfony\Component\Console\Application('Rosemary', '0.4-dev');
//$application->add(new Rosemary\Command\CreateCommand());
$application->add(new Rosemary\Command\InstallCommand());
$application->add(new Rosemary\Command\InstallEmptyCommand());
$application->add(new Rosemary\Command\SynchronizeCommand());
$application->add(new Rosemary\Command\ListSeedsCommand());
$application->add(new Rosemary\Command\UpdateSeedsCommand());
$application->add(new Rosemary\Command\DeleteCommand());
$application->run();

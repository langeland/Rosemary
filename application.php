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

if (is_dir(__DIR__ . '/.git')) {
	exec('git --git-dir=' . __DIR__ . '/.git rev-parse --verify HEAD 2> /dev/null', $output);
	$version = substr($output[0], 0, 10);
} else {
	$version = '0.4-dev';
}

$application = new \Symfony\Component\Console\Application('Rosemary', $version);
$application->add(new Rosemary\Command\InstallCommand());
//$application->add(new Rosemary\Command\InstallEmptyCommand());
$application->add(new Rosemary\Command\SynchronizeCommand());
$application->add(new Rosemary\Command\ListSeedsCommand());
$application->add(new Rosemary\Command\UpdateSeedsCommand());
$application->add(new Rosemary\Command\DeleteCommand());
$application->add(new Rosemary\Command\TestCommand());
$application->run();

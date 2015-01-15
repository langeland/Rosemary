#!/usr/bin/env php
<?php
// application.php

require __DIR__.'/Libraries/autoload.php';

$application = new \Symfony\Component\Console\Application('Rosemary', '0.1-dev');
$application->add(new Rosemary\Command\CreateCommand());
$application->add(new Rosemary\Command\SynchronizeCommand());
$application->add(new Rosemary\Command\DeleteCommand());
$application->run();

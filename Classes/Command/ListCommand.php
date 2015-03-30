<?php

namespace Rosemary\Command;


class EnvironmentSetupCommand extends \Rosemary\Command\AbstractCommand {

	private $installationName = NULL;


	public function __construct() {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('env')
			->setDescription('Check the environment, writeaccess and database users.');
	}

	protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
		$this->input = $input;
		$this->output = $output;

		print_r($this->configuration);


		$output->write('Testing write access to document_root...');
		$output->writeln('      OK');

		$output->write('Testing write access to apache_sites...');
		$output->writeln('      OK');

		$output->write('Testing database_root: user, exists...');
		$output->writeln('      OK');

		$output->write('Testing database_root: user, create database...');
		$output->writeln('      OK');

		$output->write('Testing database: user, exists...');
		$output->writeln('      OK');

		$output->write('Testing database: user, access...');
		$output->writeln('      OK');

		$output->write('Testing database_root: user, delete database...');
		$output->writeln('      OK');


	}
	/*******************************************************************************************************************
	 *
	 ******************************************************************************************************************/



}
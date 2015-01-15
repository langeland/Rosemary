<?php

namespace Rosemary\Command;


class SynchronizeCommand extends \Rosemary\Command\AbstractCommand {

	private $installationName = NULL;


	public function __construct() {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('sync')
			->setDescription('Synchronize data from moc-files to local project')
			->addArgument('name', \Symfony\Component\Console\Input\InputArgument::REQUIRED, 'Set the name of the installation');
	}

	protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
		$this->input = $input;
		$this->output = $output;
	}
	/*******************************************************************************************************************
	 *
	 ******************************************************************************************************************/



}
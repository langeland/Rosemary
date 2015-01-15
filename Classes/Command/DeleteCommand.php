<?php

namespace Rosemary\Command;


class DeleteCommand extends \Rosemary\Command\AbstractCommand {

	private $installationName = NULL;


	public function __construct() {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('delete')
			->setDescription('Delete an existing project')
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
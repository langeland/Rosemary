<?php

namespace Rosemary\Command;

class InstallEmptyCommand extends \Rosemary\Command\AbstractCommand {

	public function __construct() {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('install-empty')
			->setDescription('Create blank project. No code, just a vhost')
			->setHelp(file_get_contents(ROOT_DIR . '/Resources/InstallCommandHelp.text'));
	}

	protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
		$this->input = $input;
		$this->output = $output;

		print_r($this->input->getArguments());
		print_r($this->input->getOptions());

	}

}

<?php

namespace Rosemary\Command;

use Rosemary\Utility\General;

class ListCommand extends \Rosemary\Command\AbstractCommand {

	protected function configure() {
		$this
			->setName('list')
			->setDescription('List all site aliases');
	}

	protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
		$this->input = $input;
		$this->output = $output;

		$this->outputLine('Available aliases');
		foreach (General::getAlises() as $alias => $conf) {
			$this->outputLine(str_pad(' - ' . $alias, 18, ' ', STR_PAD_RIGHT) . $conf['description']);
		}

	}
	/*******************************************************************************************************************
	 *
	 ******************************************************************************************************************/



}
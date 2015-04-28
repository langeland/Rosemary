<?php

namespace Rosemary\Command;

use Rosemary\Utility\General;

class ListSeedsCommand extends \Rosemary\Command\AbstractCommand {

	protected function configure() {
		$this
			->setName('list-seeds')
			->setDescription('List all site seeds');
	}

	protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
		$this->input = $input;
		$this->output = $output;

		$this->outputLine('Available seeds');
		foreach (General::getSeeds() as $seed => $conf) {
			$this->outputLine(str_pad(' - ' . $seed, 18, ' ', STR_PAD_RIGHT) . $conf['description']);
		}
	}

	/*******************************************************************************************************************
	 *
	 ******************************************************************************************************************/



}
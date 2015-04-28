<?php

namespace Rosemary\Command;

use Rosemary\Utility\General;

class UpdateSeedsCommand extends \Rosemary\Command\AbstractCommand {

	protected function configure() {
		$this
			->setName('update-seeds')
			->setDescription('Update list of available seeds');
	}

	protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
		$this->input = $input;
		$this->output = $output;

		$this->outputLine('Updating seeds');

		if(!is_dir($_SERVER['HOME'] . '/.rosemary')){
			if (!mkdir($_SERVER['HOME'] . '/.rosemary', 0777)) {
				$this->outputLine('Failed to create folders... ' . $_SERVER['HOME'] . '/.rosemary');
				$this->quit(1);
			}
		}

		$source = 'rsync@moc-files:/volume1/developer/Seeds.yaml';
		$destination = $_SERVER['HOME'] . '/.rosemary/Seeds.yaml';
		$cmd = sprintf('scp %s %s', $source, $destination);
		$this->outputLine($cmd);
		$this->runCommand($cmd);
	}

	/*******************************************************************************************************************
	 *
	 ******************************************************************************************************************/



}
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

		$this->output->writeln('Updating seeds');

		if(!is_dir($_SERVER['HOME'] . '/.rosemary')){
			if (!mkdir($_SERVER['HOME'] . '/.rosemary', 0777)) {
				$this->output->writeln('Failed to create folders... ' . $_SERVER['HOME'] . '/.rosemary');
				die(1);
			}
		}

		$source = 'rsync@moc-files:/volume1/developer/Seeds2.yaml';
		$destination = $_SERVER['HOME'] . '/.rosemary/Seeds2.yaml';
		$cmd = sprintf('scp %s %s', $source, $destination);
		$this->output->writeln($cmd);
		\Rosemary\Utility\General::runCommand($this->output, $cmd);
	}

	/*******************************************************************************************************************
	 *
	 ******************************************************************************************************************/



}
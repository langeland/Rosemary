<?php

namespace Rosemary\Command;

use Rosemary\Utility\General;

class UpdateAliasesCommand extends \Rosemary\Command\AbstractCommand {

	protected function configure() {
		$this
			->setName('update-aliases')
			->setDescription('Update list of available aliases');
	}

	protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
		$this->input = $input;
		$this->output = $output;

		$this->outputLine('Output aliases');
		$source = 'rsync@moc-files:/volume1/developer/SiteAliases.yaml';
		$destination = ROOT_DIR . '/Configuration/SiteAliases.yaml';
		$cmd = sprintf('scp %s %s', $source, $destination);
		$this->outputLine($cmd);
		$this->runCommand($cmd);
	}

	/*******************************************************************************************************************
	 *
	 ******************************************************************************************************************/



}
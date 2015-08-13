<?php

namespace Rosemary\Command;

class TestCommand extends \Rosemary\Command\AbstractCommand {

	public function __construct() {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('test')
			->setDescription('N/A')
			->setHelp('N/A');
	}

	protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
		$this->input = $input;
		$this->output = $output;

		print_r($this->input->getArguments());
		print_r($this->input->getOptions());

		try {
			$db = \Rosemary\Utility\General::getDatabaseConfiguration('mocnet');
			print_r($db);
//			$output->writeln($db);
		} catch (\Rosemary\Exception\NoDatabaseNameException $e) {
			$output->writeln('No database name');
		} catch (\Exception $e) {
			$output->writeln('Exception');
		}

	}

}

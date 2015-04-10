<?php
namespace Rosemary\Command;

use Rosemary\Utility\General;


class AliasCommand extends \Rosemary\Command\AbstractCommand {

	public function __construct() {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('alias')
			->setDescription('Show list of available alises');
	}

	protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
		$this->input = $input;
		$this->output = $output;

		$aliases = General::getAlises();
		foreach ($aliases as $alias => $conf) {
			$this->outputLine(str_pad($alias, 18, ' ', STR_PAD_RIGHT) . $conf['description']);
		}
	}

}

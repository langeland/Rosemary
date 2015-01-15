<?php

namespace Rosemary\Command;

class AbstractCommand extends \Symfony\Component\Console\Command\Command {

	protected $configuration = array();

	/**
	 * @var \Symfony\Component\Console\Input\InputInterface
	 */
	protected $input;

	/**
	 * @var \Symfony\Component\Console\Output\OutputInterface
	 */
	protected $output;



	public function __construct() {
		parent::__construct();
		$this->configuration = \Symfony\Component\Yaml\Yaml::parse(file_get_contents(__DIR__ . '/../../Configuration/Rosemary.yaml'));
	}

	public function output($text, array $arguments = array()) {
		if ($arguments !== array()) {
			$text = vsprintf($text, $arguments);
		}
		$this->output->write($text);
	}

	public function outputLine($text = '', array $arguments = array()) {
		return $this->output($text . PHP_EOL, $arguments);
	}

	public function quit($exitCode = 0) {
		die($exitCode);
	}

}
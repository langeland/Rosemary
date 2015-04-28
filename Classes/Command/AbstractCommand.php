<?php

namespace Rosemary\Command;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;


class AbstractCommand extends \Symfony\Component\Console\Command\Command {

	protected $configuration = array();

	protected $logfile = NULL;

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
		if (is_file($_SERVER['HOME'] . '/.rosemary/config.yaml')) {
			$configurationHome = \Symfony\Component\Yaml\Yaml::parse(file_get_contents($_SERVER['HOME'] . '/.rosemary/config.yaml'));
			$this->configuration = $this->array_merge_recursive_distinct($this->configuration, $configurationHome);
		}
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

	private function array_merge_recursive_distinct(array &$array1, &$array2 = null) {
		$merged = $array1;

		if (is_array($array2)) {
			foreach ($array2 as $key => $val) {
				if (is_array($array2[$key])) {
					if(array_key_exists($key, $merged) && is_array($merged[$key])){
						$$merged[$key] = $this->array_merge_recursive_distinct($merged[$key], $array2[$key]);
					} else {
						$merged[$key] = $array2[$key];
					}
				} else {
					$merged[$key] = $val;
				}
			}
		}

		return $merged;
	}

	protected function runCommand($command, $description = NULL) {

		if ($description) {
			$this->outputLine('  - ' . $description);
		}

		$process = new Process($command);
		$process->setTimeout(3600);
		try {
			$process->mustRun();
			$output = PHP_EOL . '*****************************************************************' . PHP_EOL;
			if ($description) {
				$output .= '**  ' . $description . PHP_EOL;
			}
			$output .= '** ' . 'Command: ' . $command . PHP_EOL;
			$output .= '*****************************************************************' . PHP_EOL . PHP_EOL;

			$output .= $process->getOutput() . PHP_EOL;
			if ($this->logfile) {
				file_put_contents($this->logfile, $output, FILE_APPEND);
			}
		} catch (ProcessFailedException $e) {
			$this->outputLine(' !!! Command failed. Aborting');
			$this->outputLine($e->getMessage());
			die(1);
		}
	}

}
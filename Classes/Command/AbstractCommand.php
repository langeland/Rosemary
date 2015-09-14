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

	public function __construct($name = null) {
		parent::__construct($name);
		$this->configuration = $this->configuration = \Rosemary\Utility\General::getConfiguration();

		if (!defined('LOG_FILE')) {
			if ($this->configuration['locations']['log_dir']) {
				if (array_key_exists(1, $_SERVER["argv"])) {
					$command = $_SERVER["argv"][1];
					define('LOG_FILE', $this->configuration['locations']['log_dir'] . '/' . 'rosemary-' . $command . '-' . date('d-m-Y-H-i-s') . '.log');
				} else {
					define('LOG_FILE', $this->configuration['locations']['log_dir'] . '/' . 'rosemary-' . date('d-m-Y-H-i-s') . '.log');
				}
			}
		}
	}

	/**
	 *
	 */
	protected function configure() {
		$reflect = new \ReflectionClass($this);
		$this->setHelp(
			wordwrap(
				file_get_contents(ROOT_DIR . '/Resources/Help' . ucfirst($reflect->getShortName()) . '.text'),
				80
			)
		);
	}

}
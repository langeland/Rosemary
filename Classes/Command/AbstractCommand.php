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
				// TODO: Add command name to logfile name. Issue #17605
				define('LOG_FILE', $this->configuration['locations']['log_dir'] . '/' . 'rosemary-' . date('d-m-Y-H-i-s') . '.log');
			}
		}
	}

}
<?php

namespace Rosemary\Command;

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

	public function __construct($name = null) {
		parent::__construct($name);

		if (!defined('LOG_FILE')) {
			$configuration = \Rosemary\Utility\General::getConfiguration();
			if ($configuration['locations']['log_dir']) {
				define('LOG_FILE', $configuration['locations']['log_dir'] . '/' . 'rosemary-' . date('d-m-Y-H-i-s') . '.log');
			}
		}
	}

}
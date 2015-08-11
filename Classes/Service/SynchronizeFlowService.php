<?php

namespace Rosemary\Service;

class SynchronizeFlowService {

	/**
	 * @var \Symfony\Component\Console\Input\InputInterface
	 */
	protected $input;

	/**
	 * @var \Symfony\Component\Console\Output\OutputInterface
	 */
	protected $output;

	protected $configuration = array();

	protected $seed = array();

	protected $installationName = '';

	function __construct(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
		$this->input = $input;
		$this->output = $output;
		$this->configuration = \Rosemary\Utility\General::getConfiguration();
	}

	public function synchronize($seed, $installationName) {
		$this->output->writeln('<info>Synchronizing..</info>');
		$this->seed = $seed;
		// TODO: Add check that installation exists
		$this->installationName = strtolower($installationName);

		$this->task_syncronizeFiles();
		$this->task_syncronizeDatabase();
		$this->task_importDatabase();

	}

	private function task_syncronizeFiles() {
		if (!is_dir($this->configuration['locations']['document_root'] . '/' . $this->installationName . '/flow/Data/Persistent/Resources')) {
			$this->output->writeln('  - Creating Resources directory', \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_VERBOSE);
			mkdir($this->configuration['locations']['document_root'] . '/' . $this->installationName . '/flow/Data/Persistent/Resources', 0777, TRUE);
		}

		$cmd = vsprintf('rsync -av --delete %sflow/Data/Persistent/Resources/ %s/%s/flow/Data/Persistent/Resources/', array(
			$this->seed['datasource'],
			$this->configuration['locations']['document_root'],
			$this->installationName
		));

		\Rosemary\Utility\General::runCommand($this->output, $cmd, 'Synchronize Resources');
	}

	private function task_syncronizeDatabase() {
		if (!is_dir($this->configuration['locations']['document_root'] . '/' . $this->installationName . '/db-dumps')) {
			$this->output->writeln('  - Creating database sync directory', \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_VERBOSE);
			mkdir($this->configuration['locations']['document_root'] . '/' . $this->installationName . '/db-dumps', 0777, TRUE);
		}

		$cmd = vsprintf('rsync -av --delete %sdb-dumps/ %s/%s/db-dumps/', array(
			$this->seed['datasource'],
			$this->configuration['locations']['document_root'],
			$this->installationName
		));

		\Rosemary\Utility\General::runCommand($this->output, $cmd, 'Synchronize databasedump');
	}

	private function task_importDatabase() {
		$databaseName = \Rosemary\Utility\General::getDatabasename($this->installationName);

		$cmd = vsprintf(
			'mysql -h %s -u %s %s -e "DROP DATABASE IF EXISTS %s; CREATE DATABASE %s DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci;"',
			array(
				$this->configuration['database_root']['host'],
				$this->configuration['database_root']['username'],
				($this->configuration['database_root']['password'] != '') ? '-p' . $this->configuration['database_root']['password'] : '',
				$databaseName,
				$databaseName
			)
		);

		\Rosemary\Utility\General::runCommand($this->output, $cmd, 'Dropping and recreating database');

		$cmd = vsprintf(
			'mysql -h %s -u %s %s %s < %s/%s/db-dumps/database.sql',
			array(
				$this->configuration['database_root']['host'],
				$this->configuration['database_root']['username'],
				($this->configuration['database_root']['password'] != '') ? '-p' . $this->configuration['database_root']['password'] : '',
				$databaseName,
				$this->configuration['locations']['document_root'],
				$this->installationName
			)
		);

		\Rosemary\Utility\General::runCommand($this->output, $cmd, 'Loading databasedump');

	}
}
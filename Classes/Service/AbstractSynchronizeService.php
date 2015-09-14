<?php

namespace Rosemary\Service;

abstract class AbstractSynchronizeService {

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
		$this->task_syncronizeDatabaseDump();
		$this->task_importDatabase();
		$this->task_executePostSynchronizeCommands();
	}

	protected abstract function task_syncronizeFiles();

	protected function task_syncronizeDatabaseDump() {
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

		// Merge dumps
		$concatenateDumpsCmd = vsprintf('cat %s/%s/db-dumps/*.sql > %s/%s/db-dumps/database_merged.sql', array(
			$this->configuration['locations']['document_root'],
			$this->installationName,
			$this->configuration['locations']['document_root'],
			$this->installationName
		));

		\Rosemary\Utility\General::runCommand($this->output, $concatenateDumpsCmd, 'Merging database dumps');
	}

	protected function task_removeMergedDump() {
		// Remove merged dump
		$removeMergedDumpsCmd = vsprintf('rm %s/%s/db-dumps/database_merged.sql', array(
			$this->configuration['locations']['document_root'],
			$this->installationName
		));

		\Rosemary\Utility\General::runCommand($this->output, $removeMergedDumpsCmd, 'Removing merged dump');
	}

	protected function task_importDatabase() {

		$cmd = vsprintf(
			'mysql -h %s -u %s %s -e "DROP DATABASE IF EXISTS %s; CREATE DATABASE %s DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci;"',
			array(
				$this->configuration['database_root']['host'],
				$this->configuration['database_root']['username'],
				($this->configuration['database_root']['password'] != '') ? '-p' . $this->configuration['database_root']['password'] : '',
				$this->installationName,
				$this->installationName
			)
		);

		\Rosemary\Utility\General::runCommand($this->output, $cmd, 'Dropping and recreating database');

		$cmd = vsprintf(
			'mysql -h %s -u %s %s %s < %s/%s/db-dumps/database_merged.sql',
			array(
				$this->configuration['database_root']['host'],
				$this->configuration['database_root']['username'],
				($this->configuration['database_root']['password'] != '') ? '-p' . $this->configuration['database_root']['password'] : '',
				$this->installationName,
				$this->configuration['locations']['document_root'],
				$this->installationName
			)
		);

		\Rosemary\Utility\General::runCommand($this->output, $cmd, 'Loading databasedump');

		$this->task_removeMergedDump();
	}

	protected function task_executePostSynchronizeCommands() {

			chdir($this->configuration['locations']['document_root'] . '/' . $this->installationName);

			if (isset($this->seed['post-sync-cmd'])) {
				foreach ($this->seed['post-sync-cmd'] as $postSynchronizeCommand) {
					$postSynchronizeCommandTemplate = new \Rosemary\Service\TemplateService($postSynchronizeCommand);

					$postSynchronizeCommandTemplate->setVar('installationName', $this->installationName);
					$postSynchronizeCommandTemplate->setVar('seedConfiguration', json_encode($this->seed));

					$finalPostSynchronizeCommand = $postSynchronizeCommandTemplate->render();

					try {
						$this->output->writeln('Running post synchronize command: ' . $finalPostSynchronizeCommand);
						\Rosemary\Utility\General::runCommand($this->output, $finalPostSynchronizeCommand);
					} catch (Exception $e) {
						$this->output->writeln('FAIELD: Post synchronize command: ' . $finalPostSynchronizeCommand);
					}
				}
			}
	}



}


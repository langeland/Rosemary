<?php

namespace Rosemary\Command;

class SynchronizeCommand extends \Rosemary\Command\AbstractCommand {

	private $installationName = NULL;

	private $datasource = NULL;

	public function __construct() {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('sync')
			->setDescription('Synchronize data from moc-files to local project')
			->setHelp(file_get_contents(ROOT_DIR . '/Resources/SynchronizeCommandHelp.text'))
			->addArgument('name', \Symfony\Component\Console\Input\InputArgument::REQUIRED, 'Set the name of the installation')
			->addArgument('datasource', \Symfony\Component\Console\Input\InputArgument::REQUIRED, 'Set the remode tata source name');
	}

	protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
		$this->input = $input;
		$this->output = $output;

		try {
			$this->validateArgumentName($input->getArgument('name'));
			$this->validateArgumentDatasource($input->getArgument('datasource'));
		} catch (\Exception $e) {
			die('Error on validate arguments: ' . $e->getMessage());
		}

		try {
			$this->task_syncronizeFiles();
			$this->task_syncronizeDatabase();
			$this->task_importDatabase();
		} catch (\Exception $e) {
			die('It all stops here: ' . $e->getMessage());
		}
	}


	/*******************************************************************************************************************
	 *
	 ******************************************************************************************************************/

	/**
	 * @param $name
	 * @return bool
	 * @throws \Exception
	 */
	protected function validateArgumentName($name) {
		$this->installationName = $name;
		if (is_dir($this->configuration['locations']['document_root'] . strtolower($this->installationName))) {
			return TRUE;
		} else {
			throw new \Exception('Installation not found. (' . $this->configuration['locations']['document_root'] . '/' . strtolower($this->installationName) . ')');
		}
	}

	/**
	 * @param $datasource
	 * @return bool
	 */
	protected function validateArgumentDatasource($datasource) {
		$this->datasource = $datasource;
		return TRUE;
	}

	/*******************************************************************************************************************
	 *
	 ******************************************************************************************************************/

	private function task_syncronizeFiles() {
		$cmd = vsprintf('rsync -av --delete rsync@moc-files:/volume1/developer/%s/flow/Data/Persistent/Resources/ %s/%s/flow/Data/Persistent/Resources/', array(
			$this->datasource,
			$this->configuration['locations']['document_root'],
			$this->installationName
		));

		system($cmd, $exitCode);
		if ($exitCode !== 0) {
			throw new \Exception('rsync faild: (' . $cmd . ')');
		}
	}

	private function task_syncronizeDatabase() {

		$cmd = vsprintf('rsync -av --delete rsync@moc-files:/volume1/developer/%s/db-dumps/ %s/%s/db-dumps/', array(
			$this->datasource,
			$this->configuration['locations']['document_root'],
			$this->installationName
		));

		system($cmd, $exitCode);
		if ($exitCode !== 0) {
			throw new \Exception('rsync faild: (' . $cmd . ')');
		}

	}

	private function task_importDatabase() {
		$cmd = vsprintf(
			'mysql -h %s -u %s %s -e "DROP DATABASE IF EXISTS %s; CREATE DATABASE %s DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci;"',
			array(
				$this->configuration['database_root']['host'],
				$this->configuration['database_root']['username'],
				($this->configuration['database_root']['password'] != '') ? '-p' . $this->configuration['database_root']['password'] : '',
				sprintf($this->configuration['database']['database'], strtolower($this->installationName)),
				sprintf($this->configuration['database']['database'], strtolower($this->installationName))
			)
		);

		system($cmd, $exitCode);
		if ($exitCode !== 0) {
			throw new \Exception('mysql faild: (' . $cmd . ')');
		}

		$cmd = vsprintf(
			'mysql -h %s -u %s %s < %s/%s/db-dumps/database.sql',
			array(
				$this->configuration['database_root']['host'],
				$this->configuration['database_root']['username'],
				($this->configuration['database_root']['password'] != '') ? '-p' . $this->configuration['database_root']['password'] : '',
				$this->configuration['locations']['document_root'],
				$this->installationName
			)
		);

		system($cmd, $exitCode);
		if ($exitCode !== 0) {
			throw new \Exception('mysql faild: (' . $cmd . ')');
		}

	}

}
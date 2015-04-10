<?php

namespace Rosemary\Command;

use Rosemary\Utility\General;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class SynchronizeCommand extends \Rosemary\Command\AbstractCommand {

	private $installationName = NULL;

	private $datasource = NULL;

	private $logfile = NULL;

	private $siteConf = NULL;

	/**
	 * @var string
	 */
	private $destinationDatabaseName = NULL;

	public function __construct() {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('sync')
			->setDescription('Synchronize data from moc-files to local project')
			->setHelp(file_get_contents(ROOT_DIR . '/Resources/SynchronizeCommandHelp.text'))
			->addArgument('name', \Symfony\Component\Console\Input\InputArgument::REQUIRED, 'Set the name of the installation')
			->addArgument('alias', \Symfony\Component\Console\Input\InputArgument::REQUIRED, 'Set the site alias. See available aliases with rosemary alias');
	}

	protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
		$this->input = $input;
		$this->output = $output;


		try {
			$this->validateArgumentName($input->getArgument('name'));
			$this->validateArgumentAlias($input->getArgument('alias'));
		} catch (\Exception $e) {
			die('Error on validate arguments: ' . $e->getMessage());
		}

		$this->determineDestinationDatabasename();
		$this->logfile = $this->configuration['locations']['log_dir'] . '/' . 'rosemary-sync-' . date('d-m-Y-H-i-s') . '.log';



		$this->outputLine('Starting import of data for ' . $this->siteConf['description']);
		$this->outputLine(' Logging to ' . $this->logfile);
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
		if (is_dir($this->configuration['locations']['document_root'] . '/' . strtolower($this->installationName))) {
			return TRUE;
		} else {
			throw new \Exception('Installation not found. (' . $this->configuration['locations']['document_root'] . '/' . strtolower($this->installationName) . ')');
		}
	}

	/**
	 * @param $alias
	 * @return bool
	 */
	protected function validateArgumentAlias($alias) {
		$siteAliases = General::getAlises();
		foreach($siteAliases as $alias => $conf) {
			if ($alias === $alias) {
				$this->siteConf = $conf;
				$this->datasource = rtrim($this->siteConf['datasource'], '/');
				return;
			}
		}
		throw new \Exception(sprintf('Unable to sync for unknown site alias %s', $alias));
		return TRUE;
	}

	/**
	 * @return void
	 */
	protected function determineDestinationDatabasename() {
		$settingsFile = $this->configuration['locations']['document_root'] . '/' . $this->installationName . '/' . $this->configuration['locations']['flow_dir'] . '/Configuration/Development/Settings.yaml';
		if (file_exists($settingsFile) === FALSE) {
			die('Settings file' . $settingsFile . 'not found' . PHP_EOL);
		}

		try {
			$yaml = Yaml::parse(file_get_contents($settingsFile));
			$this->destinationDatabaseName = $yaml['TYPO3']['Flow']['persistence']['backendOptions']['dbname'];
		} catch (ParseException $e) {
			die('Unable to parse yam file ' . $settingsFile);
		}
	}

	/*******************************************************************************************************************
	 *
	 ******************************************************************************************************************/

	private function task_syncronizeFiles() {
		if (!is_dir($this->configuration['locations']['document_root'] . '/' . strtolower($this->installationName) . '/flow/Data/Persistent/Resources')) {
			mkdir($this->configuration['locations']['document_root'] . '/' . strtolower($this->installationName) . '/flow/Data/Persistent/Resources', 0777, TRUE);
		}

		$cmd = vsprintf('rsync -av --delete %s/flow/Data/Persistent/Resources/ %s/%s/flow/Data/Persistent/Resources/', array(
			$this->datasource,
			$this->configuration['locations']['document_root'],
			$this->installationName
		));

		$this->outputLine(' - Synchronize Resources');
		$output = array(PHP_EOL . '*****************************************************************' . PHP_EOL . 'Command: ' . $cmd . PHP_EOL . '*****************************************************************' . PHP_EOL);
		exec($cmd, $output, $exitCode);
		if ($exitCode !== 0) {
			throw new \Exception('rsync faild: (' . $cmd . ')');
		}
		file_put_contents($this->logfile, implode(PHP_EOL, $output), FILE_APPEND);
	}

	private function task_syncronizeDatabase() {
		if (!is_dir($this->configuration['locations']['document_root'] . '/' . strtolower($this->installationName) . '/db-dumps')) {
			mkdir($this->configuration['locations']['document_root'] . '/' . strtolower($this->installationName) . '/db-dumps', 0777, TRUE);
		}

		$cmd = vsprintf('rsync -av --delete %s/db-dumps/ %s/%s/db-dumps/', array(
			$this->datasource,
			$this->configuration['locations']['document_root'],
			$this->installationName
		));
		$this->outputLine(' - Synchronize databasedump');
		$output = array(PHP_EOL . '*****************************************************************' . PHP_EOL . 'Command: ' . $cmd . PHP_EOL . '*****************************************************************' . PHP_EOL);
		exec($cmd, $output, $exitCode);
		if ($exitCode !== 0) {
			throw new \Exception('rsync faild: (' . $cmd . ')');
		}
		file_put_contents($this->logfile, implode(PHP_EOL, $output), FILE_APPEND);
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
		$this->outputLine(' - Dropping and recreating database');
		$output = array(PHP_EOL . '*****************************************************************' . PHP_EOL . 'Command: ' . $cmd . PHP_EOL . '*****************************************************************' . PHP_EOL);
		exec($cmd, $output, $exitCode);
		if ($exitCode !== 0) {
			throw new \Exception('mysql faild: (' . $cmd . ')');
		}
		file_put_contents($this->logfile, implode(PHP_EOL, $output), FILE_APPEND);

		$this->outputLine(' - Loading databasedump');
		$cmd = vsprintf(
			'mysql -h %s -u %s %s %s < %s/%s/db-dumps/database.sql',
			array(
				$this->configuration['database_root']['host'],
				$this->configuration['database_root']['username'],
				($this->configuration['database_root']['password'] != '') ? '-p' . $this->configuration['database_root']['password'] : '',
				sprintf($this->configuration['database']['database'], strtolower($this->installationName)),
				$this->configuration['locations']['document_root'],
				$this->installationName
			)
		);
		$output = array('Command: ' . $cmd);
		exec($cmd, $output, $exitCode);
		if ($exitCode !== 0) {
			throw new \Exception('mysql faild: (' . $cmd . ')');
		}
		$output = array(PHP_EOL . '*****************************************************************' . PHP_EOL . 'Command: ' . $cmd . PHP_EOL . '*****************************************************************' . PHP_EOL);
		file_put_contents($this->logfile, implode(PHP_EOL, $output), FILE_APPEND);
	}

}
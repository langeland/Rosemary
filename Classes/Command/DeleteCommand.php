<?php

namespace Rosemary\Command;

class DeleteCommand extends \Rosemary\Command\AbstractCommand {

	private $installationName = NULL;

	private $mainFunction = 'Main';

	public function __construct() {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('delete')
			->setDescription('Delete an existing project')
			->addArgument('name', \Symfony\Component\Console\Input\InputArgument::OPTIONAL, 'Set the name of the installation');
	}

	protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
		$this->input = $input;
		$this->output = $output;

		try {
			$this->validateArgumentName($input->getArgument('name'));
		} catch (Exception $e) {
			die('Error on validate arguments: ' . $e->getMessage());
		}

		call_user_func(array($this, 'execute' . $this->mainFunction));

	}

	private function executeMain() {

		try {
			$this->task_deleteVhost();
			$this->task_deleteDatabase();
			$this->task_deleteDirectories();
		} catch (\Exception $e) {
			die('It all stops here: ' . $e->getMessage());
		}

	}

	private function executeList() {

		$this->outputLine('Availeble sites:');
		$this->outputLine();

		$sites = array_diff(scandir($this->configuration['locations']['document_root']), array('..', '.'));

		foreach ($sites as $site) {
			$this->outputLine($site);
		}


	}

	/*******************************************************************************************************************
	 *
	 ******************************************************************************************************************/

	/**
	 * @param $name
	 * @return bool
	 */
	protected function validateArgumentName($name) {

		if ($name === '' || $name === NULL) {
			$this->mainFunction = 'List';
		} else {
			$this->installationName = $name;
		}

		return TRUE;
	}

	/*******************************************************************************************************************
	 *
	 ******************************************************************************************************************/

	private function task_deleteVhost() {
		$this->outputLine(
			'Unloading apache virtual host: %s',
			array(
				$this->installationName
			)
		);

		$command = vsprintf(
			'a2dissite 20-%s',
			array(
				$this->installationName
			)
		);
		$this->outputLine('  <comment>%s</comment>', array($command));
		system($command);

		$this->outputLine(
			'Deleting apache virtual host: %s',
			array(
				$this->installationName
			)
		);

		$command = vsprintf(
			'rm %s',
			array(
				$this->configuration['locations']['apache_sites'] . '/20-' . strtolower($this->installationName) . '.conf'
			)
		);
		$this->outputLine('  <comment>%s</comment>', array($command));
		system($command);


	}

	private function task_deleteDatabase() {
		$this->outputLine(
			'Deleting database: %s',
			array(
				$this->installationName
			)
		);

		$command = vsprintf(
			'mysql -h %s -u %s %s -e "DROP DATABASE IF EXISTS %s;"',
			array(
				$this->configuration['database_root']['host'],
				$this->configuration['database_root']['username'],
				($this->configuration['database_root']['password'] != '') ? '-p' . $this->configuration['database_root']['password'] : '',
				strtolower($this->installationName),
			)
		);
		$this->outputLine('  <comment>%s</comment>', array($command));
		system($command);

	}

	private function task_deleteDirectories() {
		$baseDir = $this->configuration['locations']['document_root'] . '/' . strtolower($this->installationName);
		$this->outputLine(
			'Deleting fileStructure: %s',
			array(
				$baseDir
			)
		);

		$command = vsprintf(
			'rm -fR %s',
			array(
				$baseDir
			)
		);
		$this->outputLine('  <comment>%s</comment>', array($command));
		system($command);

	}

}
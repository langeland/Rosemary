<?php

namespace Rosemary\Command;

class SynchronizeCommand extends \Rosemary\Command\AbstractCommand {

	private $installationName = NULL;

	public function __construct() {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('sync')
			->setDescription('Synchronize data from moc-files to local project')
			->addArgument('datasource', \Symfony\Component\Console\Input\InputArgument::REQUIRED, 'Set the remode tata source name')
			->addArgument('name', \Symfony\Component\Console\Input\InputArgument::REQUIRED, 'Set the name of the installation');
	}

	protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
		$this->input = $input;
		$this->output = $output;

		try {
			$this->validateArgumentName($input->getArgument('name'));
		} catch (Exception $e) {
			die('Error on validate arguments');
		}

		try {
			$this->task_syncronizeFiles();
//			$this->task_cloneSource();
//			$this->task_createDatabase();
//			$this->task_updateSettings();
//			$this->task_setfilepermissions();
//			$this->task_createVhost();
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
	 */
	protected function validateArgumentName($name) {
		$this->installationName = $name;
		return TRUE;
	}

	/*******************************************************************************************************************
	 *
	 ******************************************************************************************************************/

	private function task_syncronizeFiles() {
		$remoteDataLocation = 'fvmsync@moc-files:/volume1/developer/fvm_dk/';
		//DATADIRECTORY_PATH=fvmsync@moc-files:/volume1/developer/fvm_dk/

		$cmd = 'rsync -av --delete ' . $remoteDataLocation . ' /home/sites/folkekirken/flow/Data/Persistent/Resources/';
	}

}
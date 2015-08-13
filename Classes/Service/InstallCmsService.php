<?php

namespace Rosemary\Service;

class InstallCmsService {

	/**
	 * @var \Symfony\Component\Console\Input\InputInterface
	 */
	protected $input;

	/**
	 * @var \Symfony\Component\Console\Output\OutputInterface
	 */
	protected $output;

	protected $configuration;

	function __construct(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
		$this->input = $input;
		$this->output = $output;
		$this->configuration = \Rosemary\Utility\General::getConfiguration();
	}

	public function install($installationConfiguration) {
		$this->installationConfiguration = $installationConfiguration;
		try {
			$this->output->writeln('InstallCmsService');


			// Creating directory structure
			$this->task_createDirectories();

			//  Cloning TYPO3 Source
			$this->task_cloneTypo3Source();

//			// Cloning versioned folder
//			$this->task_cloneVersioned();
//
//			// Creating VirtualHost
//			$this->task_createVhost();
//			$this->task_installVhostAndRestartApache();
//
//			// Create MYSQL database
//			$this->task_createDatabase();
//
//			// Syncing datafiles into $SITEPATH
//			// Updating source symlink
//			// Updating LocalConfiguration.php
//			// Linking typo3 src
//			// Creating typo3temp directory
//
//			$this->task_executePostCreateCommands();


		} catch (\Exception $e) {
			die('It all stops here: ' . $e->getMessage());
		}
	}


	/*******************************************************************************************************************
	 *
	 ******************************************************************************************************************/

	private function task_createDirectories() {
		$installDirectory = $this->configuration['locations']['document_root'] . '/' . $this->installationConfiguration['name'] . '/';

		$this->output->writeln(vsprintf('Creating directory structure at: %s', array($installDirectory)));

		if (!mkdir($installDirectory, 0777)) {
			throw new \Exception('Failed to create folder: ' . $installDirectory);
		}

		if (!mkdir($installDirectory . 'docs/', 0777, TRUE)) {
			throw new \Exception('Failed to create folder: ' . $installDirectory . 'docs/');
		}

		if (!mkdir($installDirectory . 'typo3_sources/', 0777, TRUE)) {
			throw new \Exception('Failed to create folder: ' . $installDirectory . 'typo3_sources/');
		}

		if (!mkdir($installDirectory . 'logs/', 0777, TRUE)) {
			throw new \Exception('Failed to create folder: ' . $installDirectory . 'logs/');
		}

		if (!mkdir($installDirectory . 'sync/', 0777, TRUE)) {
			throw new \Exception('Failed to create folder: ' . $installDirectory . 'sync/');
		}
	}

	private function task_cloneTypo3Source() {
//		git clone git://git.typo3.org/Packages/TYPO3.CMS.git typo3_src-git
//		git checkout TYPO3_6-2
//		git pull

		$installDirectory = $this->configuration['locations']['document_root'] . '/' . $this->installationConfiguration['name'] . '/';
		$command = vsprintf(
			'git clone %s %s',
			array(
				'git://git.typo3.org/Packages/TYPO3.CMS.git',
				$installDirectory . 'typo3_sources/typo3_src-git'
			)
		);
		\Rosemary\Utility\General::runCommand($this->output, $command, 'Cloning TYPO3 Source');
		chdir($installDirectory . '/typo3_sources/typo3_src-git/');

		$command = vsprintf(
			'git checkout %s',
			array($this->installationConfiguration['seed']['version'])
		);
		\Rosemary\Utility\General::runCommand($this->output, $command, 'Checkout version: ' . $this->installationConfiguration['seed']['version']);

		$command = vsprintf(
			'git pull',
			array()
		);
		\Rosemary\Utility\General::runCommand($this->output, $command);


	}


}
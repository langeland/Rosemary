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
		try {
			$this->output->writeln('InstallCmsService');


			// Creating directory structure
			$this->task_createDirectories();

			//  Cloning TYPO3 Source
			$this->task_cloneTypo3Source();

			// Cloning versioned folder
			$this->task_cloneVersioned();

			// Creating VirtualHost
			$this->task_createVhost();
			$this->task_installVhostAndRestartApache();

			// Create MYSQL database
			$this->task_createDatabase();

			// Syncing datafiles into $SITEPATH
			// Updating source symlink
			// Updating LocalConfiguration.php
			// Linking typo3 src
			// Creating typo3temp directory

			$this->task_executePostCreateCommands();


		} catch (\Exception $e) {
			die('It all stops here: ' . $e->getMessage());
		}
	}





}
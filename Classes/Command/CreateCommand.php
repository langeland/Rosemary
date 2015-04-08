<?php

namespace Rosemary\Command;

class CreateCommand extends \Rosemary\Command\AbstractCommand {

	private $installationName = NULL;

	private $installationType = '';

	private $installationSource = '';

	public function __construct() {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('create')
			->setDescription('Create blank project')
			->setHelp(file_get_contents(ROOT_DIR . '/Resources/CreateCommandHelp.text'))
			->addArgument('name', \Symfony\Component\Console\Input\InputArgument::REQUIRED, 'Set the name of the installation')
			->addArgument('source', \Symfony\Component\Console\Input\InputArgument::REQUIRED, 'Set the source fof the installation. Can be a packagist package "vendor/package" or a git reposirory "git@github.com:user/vendor-package.git"');
	}

	protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
		$this->input = $input;
		$this->output = $output;

		try {
			$this->validateArgumentSource($input->getArgument('source'));
			$this->validateArgumentName($input->getArgument('name'));
		} catch (\Exception $e) {
			die('Error on validate arguments: ' . $e->getMessage());
		}

		try {
			$this->task_createDirectories();
			$this->task_cloneSource();
			$this->task_createDatabase();
			$this->task_updateSettings();
			$this->task_setfilepermissions();
			$this->task_createVhost();
		} catch (\Exception $e) {
			die('It all stops here: ' . $e->getMessage());
		}
	}
	/*******************************************************************************************************************
	 *
	 ******************************************************************************************************************/

	/**
	 * @param $source
	 * @return bool
	 */
	protected function validateArgumentSource($source) {
		$this->installationSource = $source;

		if (preg_match("*^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+?$*", $source)) {
			$this->installationType = 'composer';
			return TRUE;
		} elseif (preg_match('*([A-Za-z0-9]+@|http(|s)\:\/\/)([A-Za-z0-9.]+)(:|/)([A-Za-z0-9\/]+)(\.git)?*', $source)) {
			$this->installationType = 'git';
			return TRUE;
		} else {
			throw new \Exception('Sourse is not a valid git repository or a valid Packagist package');
			return FALSE;
		}

	}

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

	/**
	 *
	 */
	private function task_createDirectories() {
		$this->output->writeln(vsprintf('Creating directory structure at: %s', array($this->configuration['locations']['document_root'] . '/' . strtolower($this->installationName))));

		$baseDir = $this->configuration['locations']['document_root'] . '/' . strtolower($this->installationName) . '/';

		if (!mkdir($baseDir, 0777)) {
			$this->outputLine('Failed to create folders...');
			$this->quit(1);
		}

		if (!mkdir($baseDir . 'logs/', 0777, TRUE)) {
			$this->outputLine('Failed to create folders...');
			$this->quit(1);
		}

		if (!mkdir($baseDir . 'sync/', 0777, TRUE)) {
			$this->outputLine('Failed to create folders...');
			$this->quit(1);
		}

		#if (!mkdir($baseDir . 'flow/Data/Logs/', 0777, TRUE)) {
		#	die('Failed to create folders...');
		#}
	}

	private function task_cloneSource() {
		$this->outputLine('Cloning base package');
		if ($this->installationType == 'composer') {
			$this->outputLine('  Running composer: php /path/to/composer.phar create-project %s %s', array(
				$this->installationSource,
				$this->configuration['locations']['document_root'] . '/' . $this->installationName
			));

			chdir($this->configuration['locations']['document_root'] . '/' . strtolower($this->installationName) . '/');
			system(vsprintf(
				'composer --verbose --no-progress --no-interaction --keep-vcs create-project %s flow',
				array(
					$this->installationSource
				)
			));

		} else {
			$this->outputLine('  Running git: git clone %s flow', array(
				$this->installationSource,
			));

			chdir($this->configuration['locations']['document_root'] . '/' . strtolower($this->installationName) . '/');
			system(vsprintf(
				'git clone %s flow',
				array(
					$this->installationSource
				)
			));

			chdir($this->configuration['locations']['document_root'] . '/' . strtolower($this->installationName) . '/flow/');
			system(vsprintf(
				'composer --verbose --no-progress --no-interaction install',
				array()
			));
		}
	}

	private function task_createDatabase() {

		$this->outputLine('Create database: %s', array(
			strtolower($this->installationName)
		));

		$this->outputLine('  Running: mysql -h %s -u %s %s -e "CREATE DATABASE %s DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci;"', array(
			$this->configuration['database_root']['host'],
			$this->configuration['database_root']['username'],
			($this->configuration['database_root']['password'] != '') ? '-p' . $this->configuration['database_root']['password'] : '',
			strtolower($this->installationName)
		));

		system(vsprintf(
			'mysql -h %s -u %s %s -e "DROP DATABASE IF EXISTS %s; CREATE DATABASE %s DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci;"',
			array(
				$this->configuration['database_root']['host'],
				$this->configuration['database_root']['username'],
				($this->configuration['database_root']['password'] != '') ? '-p' . $this->configuration['database_root']['password'] : '',
				strtolower($this->installationName),
				sprintf($this->configuration['database']['database'], strtolower($this->installationName))
			)
		));
	}

	private function task_updateSettings() {
		$this->outputLine('Updating Configuration/Settings.yaml');

		$settingsYamlTemplate = new \Rosemary\Service\Template(\Rosemary\Utility\General::getResourcePathAndName('SettingsYaml.template'));
		$settingsYamlTemplate->setVar('host', $this->configuration['database']['host']);
		$settingsYamlTemplate->setVar('user', $this->configuration['database']['username']);
		$settingsYamlTemplate->setVar('password', $this->configuration['database']['password']);
		$settingsYamlTemplate->setVar('dbname', sprintf($this->configuration['database']['database'], strtolower($this->installationName)));
		$fileContent = $settingsYamlTemplate->render();

		file_put_contents($this->configuration['locations']['document_root'] . '/' . $this->installationName . '/' . $this->configuration['locations']['flow_dir'] . '/Configuration/Settings.yaml', $fileContent);
	}

	private function task_setfilepermissions() {
		$this->outputLine('Adjust file permissions for CLI and web server access');
		$this->outputLine(
			'Running: sudo ./flow flow:core:setfilepermissions %s %s %s',
			array(
				$this->configuration['permissions']['owner'],
				$this->configuration['permissions']['group'],
				$this->configuration['permissions']['group']
			)
		);

		chdir($this->configuration['locations']['document_root'] . '/' . $this->installationName . '/' . $this->configuration['locations']['flow_dir']);
		system(vsprintf(
			'sudo ./flow flow:core:setfilepermissions %s %s %s',
			array(
				$this->configuration['permissions']['owner'],
				$this->configuration['permissions']['group'],
				$this->configuration['permissions']['group']
			)
		));
	}

	private function task_createVhost() {
		$this->outputLine('Creating virtual host: "%s"', array($this->configuration['locations']['apache_sites'] . '/20-' . strtolower($this->installationName) . '.conf'));

		$virtualHostTemplate = new \Rosemary\Service\Template(\Rosemary\Utility\General::getResourcePathAndName('VirtualHost.template'));
		$virtualHostTemplate->setVar('installationName', strtolower($this->installationName));
		$virtualHostTemplate->setVar('documentRoot', $this->configuration['locations']['document_root']);
		$virtualHostTemplate->setVar('flowDir', $this->configuration['locations']['flow_dir']);
		$fileContent = $virtualHostTemplate->render();

		$file = tempnam ('/tmp', 'rosemary');

		file_put_contents($file, $fileContent);

		system(vsprintf(
			'sudo mv %s %s',
			array(
				$file,
				$this->configuration['locations']['apache_sites'] . '/20-' . strtolower($this->installationName) . '.conf',
			)
		));
	}

}
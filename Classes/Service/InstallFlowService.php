<?php

namespace Rosemary\Service;

class InstallFlowService {

	/**
	 * @var \Symfony\Component\Console\Input\InputInterface
	 */
	protected $input;

	/**
	 * @var \Symfony\Component\Console\Output\OutputInterface
	 */
	protected $output;

	protected $configuration;

	protected $installationConfiguration = array();

	function __construct(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
		$this->input = $input;
		$this->output = $output;
		$this->configuration = \Rosemary\Utility\General::getConfiguration();
	}

	public function install($installationConfiguration) {
		$this->installationConfiguration = $installationConfiguration;

		try {
			$this->task_createDirectories();
			$this->task_cloneSource();
			$this->task_createDatabase();
			$this->task_updateSettings();
			$this->task_setfilepermissions();
			$this->task_createVhost();
			$this->task_installVhostAndRestartApache();
//			$this->task_executePostCreateCommands();
		} catch (\Exception $e) {
			die('It all stops here: ' . $e->getMessage());
		}
	}



	/*******************************************************************************************************************
	 *
	 ******************************************************************************************************************/

	/**
	 *
	 */
	private function task_createDirectories() {
		$installDirectory = $this->configuration['locations']['document_root'] . '/' . $this->installationConfiguration['name'] . '/';

		$this->output->writeln(vsprintf('Creating directory structure at: %s', array($installDirectory)));

		if (!mkdir($installDirectory, 0777)) {
			throw new \Exception('Failed to create folderr: ' . $installDirectory);
		}

		if (!mkdir($installDirectory . 'logs/', 0777, TRUE)) {
			throw new \Exception('Failed to create folderr: ' . $installDirectory . 'logs/');
		}

		if (!mkdir($installDirectory . 'sync/', 0777, TRUE)) {
			throw new \Exception('Failed to create folderr: ' . $installDirectory . 'sync/');
		}
	}

	private function task_cloneSource() {
		$installDirectory = $this->configuration['locations']['document_root'] . '/' . $this->installationConfiguration['name'] . '/';

		if ($this->installationConfiguration['installer'] == 'composer') {
			$this->output->writeln('Installing package with composer');
			chdir($installDirectory);
			$command = vsprintf(
				'composer --verbose --no-progress --no-interaction --keep-vcs create-project %s flow',
				array($this->installationConfiguration['source'])
			);
			\Rosemary\Utility\General::runCommand($command);
		} else {
			$this->output->writeln('Cloning source with git');
			chdir($installDirectory);
			$command = vsprintf(
				'git clone %s flow',
				array($this->installationConfiguration['source'])
			);

			\Rosemary\Utility\General::runCommand($command);

			chdir($installDirectory . '/flow/');

			$command = vsprintf(
				'composer --verbose --no-progress --no-interaction install',
				array()
			);
			\Rosemary\Utility\General::runCommand($command);
		}
	}

	/**
	 * @return array
	 */
	protected function getDestinationDatabaseConfig() {
		$settingsFile = $this->configuration['locations']['document_root'] . '/' . $this->installationConfiguration['name'] . '/flow/Configuration/Development/Settings.yaml';
		if (file_exists($settingsFile) === FALSE) {
			$this->output->writeln('  No Development/Settings.yaml found, falling back to sitename: ' . $this->installationConfiguration['name'] . ' remember to update Settings afterwards');
			return array(
				'dbname' => $this->installationName,
				'user' => 'root'
			);
		}

		try {
			$yaml = \Symfony\Component\Yaml\Yaml::parse(file_get_contents($settingsFile));
			return $yaml['TYPO3']['Flow']['persistence']['backendOptions'];
		} catch (ParseException $e) {
			die('Unable to parse yaml file ' . $settingsFile);
		}
	}

	private function task_createDatabase() {
		$databaseConfig = $this->getDestinationDatabaseConfig();
		$command = vsprintf(
			'mysql -h %s -u %s %s -e "DROP DATABASE IF EXISTS \`%s\`; CREATE DATABASE \`%s\` DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci;"',
			array(
				$this->configuration['database_root']['host'],
				$this->configuration['database_root']['username'],
				($this->configuration['database_root']['password'] != '') ? '-p' . $this->configuration['database_root']['password'] : '',
				$databaseConfig['dbname'],
				$databaseConfig['dbname']
			)
		);
		$this->output->writeln('Create database: ' . $databaseConfig['dbname']);
		\Rosemary\Utility\General::runCommand($command);

		$dbUser = array_key_exists('user', $databaseConfig) ? $databaseConfig['user'] : 'root';
		$dbPassword = array_key_exists('password', $databaseConfig) ? $databaseConfig['password'] : '';
		if ($dbUser !== 'root') {
			$command = sprintf('mysql -h %s -u %s %s -e "GRANT ALL ON \`%s\`.* to \'%s\'@\'localhost\' identified by \'%s\'"',
				$this->configuration['database_root']['host'],
				$this->configuration['database_root']['username'],
				($this->configuration['database_root']['password'] != '') ? '-p' . $this->configuration['database_root']['password'] : '',
				$databaseConfig['dbname'],
				$dbUser,
				$dbPassword
			);

			$this->output->writeln('Create database user: ' . $dbUser);
			\Rosemary\Utility\General::runCommand($command);
		}

		return;
	}

	private function task_updateSettings() {
		if (file_exists($this->configuration['locations']['document_root'] . '/' . $this->installationConfiguration['name'] . '/flow/Configuration/Development/Settings.yaml')) {
			$this->output->writeln('Configuration/Development/Settings.yaml exists');
		} else {
			$this->output->writeln('Creating Configuration/Settings.yaml');
			$settingsYamlTemplate = new \Rosemary\Service\Template(\Rosemary\Utility\General::getResourcePathAndName('SettingsYaml.template'));
			$settingsYamlTemplate->setVar('host', $this->configuration['database']['host']);
			$settingsYamlTemplate->setVar('user', $this->configuration['database']['username']);
			$settingsYamlTemplate->setVar('password', $this->configuration['database']['password']);
			$settingsYamlTemplate->setVar('dbname', sprintf($this->configuration['database']['database'], strtolower($this->installationName)));
			$fileContent = $settingsYamlTemplate->render();
			file_put_contents($this->configuration['locations']['document_root'] . '/' . $this->installationConfiguration['name'] . '/flow/Configuration/Settings.yaml', $fileContent);
		}
	}

	private function task_setfilepermissions() {
		chdir($this->configuration['locations']['document_root'] . '/' . $this->installationConfiguration['name'] . '/flow');
		$command = vsprintf(
			'sudo ./flow flow:core:setfilepermissions %s %s %s',
			array(
				$this->configuration['permissions']['owner'],
				$this->configuration['permissions']['group'],
				$this->configuration['permissions']['group']
			)
		);
		\Rosemary\Utility\General::runCommand($command, 'Adjust file permissions for CLI and web server access');
	}

	private function task_createVhost() {
		$virtualHostTemplate = new \Rosemary\Service\Template(\Rosemary\Utility\General::getResourcePathAndName('VirtualHost.template'));
		$virtualHostTemplate->setVar('installationName', $this->installationConfiguration['name']);
		$virtualHostTemplate->setVar('documentRoot', $this->configuration['locations']['document_root']);
		$virtualHostTemplate->setVar('flowDir', 'flow');
		$fileContent = $virtualHostTemplate->render();

		$file = tempnam('/tmp', 'rosemary');
		file_put_contents($file, $fileContent);

		$command = vsprintf(
			'sudo mv %s %s',
			array(
				$file,
				$this->configuration['locations']['apache_sites'] . '/' . $this->installationConfiguration['name'],
			)
		);

		$this->output->writeln('Creating virtual host: "' . $this->configuration['locations']['apache_sites'] . '/' . $this->installationConfiguration['name'] . '""');
		\Rosemary\Utility\General::runCommand($command);
	}

	private function task_installVhostAndRestartApache() {
		$command = vsprintf('sudo a2ensite %s', $this->installationConfiguration['name']);
		$this->output->writeln('  - Install vhost');
		\Rosemary\Utility\General::runCommand($command);

		$command = 'sudo apache2ctl graceful';
		$this->output->writeln('  - Restart apache');
		\Rosemary\Utility\General::runCommand($command);
	}

	private function task_executePostCreateCommands() {
		if ($this->installationConfiguration['seed'] !== NULL) {
			foreach (\Rosemary\Utility\General::getSeeds() as $siteSeed => $seedConfiguration) {
				if ($siteSeed === $this->installationConfiguration['seed']) {
					break;
				}
			}

			chdir($this->configuration['locations']['document_root'] . '/' . $this->installationConfiguration['name']);

			if (isset($seedConfiguration['post-create-cmd'])) {
				foreach ($seedConfiguration['post-create-cmd'] as $postCreateCommand) {
					$this->output->writeln('Running post create command: ' . $postCreateCommand);
					\Rosemary\Utility\General::runCommand($postCreateCommand);
				}
			}
		}
	}

}
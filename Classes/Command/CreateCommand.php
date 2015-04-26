<?php

namespace Rosemary\Command;

use Rosemary\Utility\General;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class CreateCommand extends \Rosemary\Command\AbstractCommand {

	private $installationName = NULL;

	private $installationType = '';

	private $installationSource = '';

	private $installationAlias = NULL;

	public function __construct() {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('create')
			->setDescription('Create blank project')
			->setHelp(file_get_contents(ROOT_DIR . '/Resources/CreateCommandHelp.text'))
			->addArgument('source', \Symfony\Component\Console\Input\InputArgument::REQUIRED, 'Set the source of the installation. Can be a aite alias, a packagist package "vendor/package" or a git reposirory "git@github.com:user/vendor-package.git"')
			->addArgument('name', \Symfony\Component\Console\Input\InputArgument::OPTIONAL, 'Set the name of the installation. If no name is given, then the alias is used');
	}

	protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
		$this->input = $input;
		$this->output = $output;

		try {
			$source = $input->getArgument('source');
			$this->validateArgumentSource($source);
			if ($input->getArgument('name') != '') {
				$this->validateArgumentName($input->getArgument('name'));
			} else {
				$this->validateArgumentName($source);
			}
		} catch (\Exception $e) {
			die('Error on validate arguments: ' . $e->getMessage());
		}

		$this->logfile = $this->configuration['locations']['log_dir'] . '/' . 'rosemary-create-' . date('d-m-Y-H-i-s') . '.log';

		$this->outputLine('Logging to ' . $this->logfile);

		try {
			$this->task_createDirectories();
			$this->task_cloneSource();
			$this->task_createDatabase();
			$this->task_updateSettings();
			$this->task_setfilepermissions();
			$this->task_createVhost();
			$this->task_installVhostAndRestartApache();
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

		// Check if source is in alias files
		$siteAliases = General::getAlises();
		foreach ($siteAliases as $alias => $conf) {
			if ($alias === $source) {
				$this->outputLine(sprintf('Alias %s found with installation source %s', $alias, $conf['source']));
				$this->installationSource = $conf['source'];
				$this->installationAlias = $alias;
			}
		}

		if (preg_match("*^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+?$*", $this->installationSource)) {
			$this->installationType = 'composer';
			return TRUE;
		} elseif (preg_match('*([A-Za-z0-9]+@|http(|s)\:\/\/)([A-Za-z0-9.]+)(:|/)([A-Za-z0-9\/]+)(\.git)?*', $this->installationSource)) {
			$this->installationType = 'git';
			return TRUE;
		} else {
			throw new \Exception('Source is not a valid alias, git repository or Packagist package');
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
			$command = vsprintf(
				'composer --verbose --no-progress --no-interaction --keep-vcs create-project %s flow',
				array(
					$this->installationSource
				)
			);
			$this->runCommand($command);

		} else {
			$this->outputLine('  Running git: git clone %s flow', array(
				$this->installationSource,
			));

			chdir($this->configuration['locations']['document_root'] . '/' . strtolower($this->installationName) . '/');

			$command = vsprintf(
				'git clone %s flow',
				array(
					$this->installationSource
				)
			);

			$this->runCommand($command, 'Git clone');

			chdir($this->configuration['locations']['document_root'] . '/' . strtolower($this->installationName) . '/flow/');
			$command = vsprintf(
				'composer --verbose --no-progress --no-interaction install',
				array()
			);
			$this->runCommand($command, 'Composer install');
		}
	}

	/**
	 * @return array
	 */
	protected function getDestinationDatabaseConfig() {
		$settingsFile = $this->configuration['locations']['document_root'] . '/' . $this->installationName . '/' . $this->configuration['locations']['flow_dir'] . '/Configuration/Development/Settings.yaml';
		if (file_exists($settingsFile) === FALSE) {
			if ($this->installationAlias === NULL) {
				$this->outputLine('  No Development/Settings.yaml found, falling back to sitename: ' . $this->installationName . ' remember to update Settings afterwards');
				return array(
					'dbname' => $this->installationName,
					'user' => 'root'
				);
			}
		}

		try {
			$yaml = Yaml::parse(file_get_contents($settingsFile));
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
		$this->outputLine('Create database: %s', array($databaseConfig['dbname']));
		$this->runCommand($command);

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

			$this->outputLine('Create database user: %s', array($dbUser));
			$this->runCommand($command);
		}

		return;
	}

	private function task_updateSettings() {
		if(file_exists($this->configuration['locations']['document_root'] . '/' . $this->installationName . '/' . $this->configuration['locations']['flow_dir'] . '/Configuration/Development/Settings.yaml')) {
			$this->outputLine('Configuration/Development/Settings.yaml exists', array());
		} else {
			$this->outputLine('Creating Configuration/Settings.yaml');
			$settingsYamlTemplate = new \Rosemary\Service\Template(\Rosemary\Utility\General::getResourcePathAndName('SettingsYaml.template'));
			$settingsYamlTemplate->setVar('host', $this->configuration['database']['host']);
			$settingsYamlTemplate->setVar('user', $this->configuration['database']['username']);
			$settingsYamlTemplate->setVar('password', $this->configuration['database']['password']);
			$settingsYamlTemplate->setVar('dbname', sprintf($this->configuration['database']['database'], strtolower($this->installationName)));
			$fileContent = $settingsYamlTemplate->render();
			file_put_contents($this->configuration['locations']['document_root'] . '/' . $this->installationName . '/' . $this->configuration['locations']['flow_dir'] . '/Configuration/Settings.yaml', $fileContent);
		}
	}

	private function task_setfilepermissions() {
		chdir($this->configuration['locations']['document_root'] . '/' . $this->installationName . '/' . $this->configuration['locations']['flow_dir']);
		$command = vsprintf(
			'sudo ./flow flow:core:setfilepermissions %s %s %s',
			array(
				$this->configuration['permissions']['owner'],
				$this->configuration['permissions']['group'],
				$this->configuration['permissions']['group']
			)
		);
		$this->runCommand($command, 'Adjust file permissions for CLI and web server access');
	}

	private function task_createVhost() {
		$virtualHostTemplate = new \Rosemary\Service\Template(\Rosemary\Utility\General::getResourcePathAndName('VirtualHost.template'));
		$virtualHostTemplate->setVar('installationName', strtolower($this->installationName));
		$virtualHostTemplate->setVar('documentRoot', $this->configuration['locations']['document_root']);
		$virtualHostTemplate->setVar('flowDir', $this->configuration['locations']['flow_dir']);
		$fileContent = $virtualHostTemplate->render();

		$file = tempnam('/tmp', 'rosemary');
		file_put_contents($file, $fileContent);

		$command = vsprintf(
			'sudo mv %s %s',
			array(
				$file,
				$this->configuration['locations']['apache_sites'] . '/' . strtolower($this->installationName),
			)
		);

		$this->outputLine('  Creating virtual host: "%s"', array($this->configuration['locations']['apache_sites'] . '/' . strtolower($this->installationName)));
		$this->runCommand($command);
	}

	private function task_installVhostAndRestartApache() {
		$command = vsprintf('sudo a2ensite %s', strtolower($this->installationName));
		$this->outputLine('  - Install vhost');
		$this->runCommand($command);

		$command = 'sudo apache2ctl graceful';
		$this->outputLine('  - Restart apache');
		$this->runCommand($command);
	}



}
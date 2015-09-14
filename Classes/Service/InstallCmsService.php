<?php

namespace Rosemary\Service;

class InstallCmsService extends AbstractInstallService {

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
		$this->seedConfiguration = \Rosemary\Utility\General::getSeed($installationConfiguration['seed']);

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
			$this->task_syncingDatafiles();

			// Updating source symlink
			$this->task_sourceSymlink();

			// Updating LocalConfiguration.php
			$this->task_updateLocalConfiguration();

			// Linking typo3 src
			// Creating typo3temp directory

			$this->task_executePostInstallCommands();

		} catch (\Exception $e) {
			die('It all stops here: ' . $e->getMessage());
		}
	}

	/*******************************************************************************************************************
	 *
	 ******************************************************************************************************************/

	protected function task_createDirectories() {
		$installDirectory = $this->configuration['locations']['document_root'] . '/' . $this->installationConfiguration['name'] . '/';

		$this->output->writeln(vsprintf('Creating directory structure at: %s', array($installDirectory)));

		if (!mkdir($installDirectory, 0777)) {
			throw new \Exception('Failed to create folder: ' . $installDirectory);
		}

		if (!mkdir($installDirectory . 'docs/', 0777, TRUE)) {
			throw new \Exception('Failed to create folder: ' . $installDirectory . 'docs/');
		}

		if (!mkdir($installDirectory . 'typo3temp/', 0777, TRUE)) {
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
			array($this->seedConfiguration['version'])
		);
		\Rosemary\Utility\General::runCommand($this->output, $command, 'Checkout version: ' . $this->seedConfiguration['version']);

		$command = vsprintf(
			'git pull',
			array()
		);
		\Rosemary\Utility\General::runCommand($this->output, $command);
	}

	private function task_cloneVersioned() {
		$installDirectory = $this->configuration['locations']['document_root'] . '/' . $this->installationConfiguration['name'] . '/';
		chdir($installDirectory);
		$command = vsprintf(
			'git clone %s %s',
			array(
				$this->installationConfiguration['source'],
				$installDirectory . 'versioned'
			)
		);
		\Rosemary\Utility\General::runCommand($this->output, $command, 'Cloning versioned folder');

		chdir($installDirectory . '/versioned/');

		try {
			$command = 'git submodule init';
			\Rosemary\Utility\General::runCommand($this->output, $command);
			$command = 'git submodule update';
			\Rosemary\Utility\General::runCommand($this->output, $command);
		} catch (\RuntimeException $e) {
			$this->output->writeln('<error>Submodule initialization failure</error>');
			$this->output->writeln('<error>' . $e->getMessage() . '</error>');
		}

		$command = 'gerrit';
		\Rosemary\Utility\General::runCommand($this->output, $command);
	}

	private function task_createVhost() {
		$virtualHostTemplate = new \Rosemary\Service\TemplateService(\Rosemary\Utility\General::getResourcePathAndName('VirtualHostCms.template'));
		$virtualHostTemplate->setVar('installationName', $this->installationConfiguration['name']);
		$virtualHostTemplate->setVar('documentRoot', $this->configuration['locations']['document_root']);
		$virtualHostTemplate->setVar('hostname', gethostname());
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
		\Rosemary\Utility\General::runCommand($this->output, $command);
	}

	private function task_syncingDatafiles() {
		$installDirectory = $this->configuration['locations']['document_root'] . '/' . $this->installationConfiguration['name'] . '/';
		chdir($installDirectory);
		$cmd = vsprintf('rsync -av --delete %sdocs %s', array(
			$this->seedConfiguration['datasource'],
			$installDirectory
		));
		\Rosemary\Utility\General::runCommand($this->output, $cmd, 'Synchronize Resources');
	}

	private function task_sourceSymlink() {
		$installDirectory = $this->configuration['locations']['document_root'] . '/' . $this->installationConfiguration['name'] . '/';
		$this->output->writeln('Updating TYPO3 source symlink');

		if (!unlink($installDirectory . 'docs/typo3_src')) {
			throw new \Exception('Unable to delete typo3_src symlink ');
		}

		if (!symlink($installDirectory . 'typo3_sources/typo3_src-git', $installDirectory . 'docs/typo3_src')) {
			throw new \Exception('Unable to create typo3_src symlink ');
		}
	}

	private function task_updateLocalConfiguration() {
		$localConfigurationFile = $this->configuration['locations']['document_root'] . '/' . $this->installationConfiguration['name'] . '/docs/typo3conf/LocalConfiguration.php';

		$localConfiguration = include($localConfigurationFile);
		$localConfiguration['DB']['host'] = $this->configuration['database_root']['host'];
		$localConfiguration['DB']['username'] = $this->configuration['database_root']['username'];
		$localConfiguration['DB']['password'] = ($this->configuration['database_root']['password'] != '') ? '-p' . $this->configuration['database_root']['password'] : '';
		$localConfiguration['DB']['database'] = $this->installationConfiguration['name'];

		$localConfiguration['SYS']['sitename'] = $localConfiguration['SYS']['sitename'] . ' - [Vagrant]';

		$localConfigurationData = '<?php' . PHP_EOL .
			'return ' .
			var_export($localConfiguration, 1) .
			';' . PHP_EOL .
			'?>';
		// TODO: Add check if file was writen
		file_put_contents($localConfigurationFile, $localConfigurationData);
	}

}
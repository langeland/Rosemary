<?php

namespace Rosemary\Service;

class InstallFlowService extends AbstractInstallService {

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
			$this->task_executePostCreateCommands();
		} catch (\Exception $e) {
			die('It all stops here: ' . $e->getMessage());
		}
	}

	/*******************************************************************************************************************
	 *
	 ******************************************************************************************************************/

	private function task_cloneSource() {
		$installDirectory = $this->configuration['locations']['document_root'] . '/' . $this->installationConfiguration['name'] . '/';

		if ($this->installationConfiguration['installer'] == 'composer') {
			$this->output->writeln('Installing package with composer');
			chdir($installDirectory);
			$command = vsprintf(
				'composer --verbose --no-progress --no-interaction --keep-vcs create-project %s flow',
				array($this->installationConfiguration['source'])
			);
			\Rosemary\Utility\General::runCommand($this->output, $command);
		} else {
			$this->output->writeln('Cloning source with git');
			chdir($installDirectory);
			$command = vsprintf(
				'git clone %s flow',
				array($this->installationConfiguration['source'])
			);

			\Rosemary\Utility\General::runCommand($this->output, $command);

			chdir($installDirectory . '/flow/');

			$command = vsprintf(
				'composer --verbose --no-progress --no-interaction install',
				array()
			);
			\Rosemary\Utility\General::runCommand($this->output, $command);
		}
	}

	private function task_updateSettings() {
		$configurationDirectory = $this->configuration['locations']['document_root'] . '/' . $this->installationConfiguration['name'] . '/flow/Configuration/Development/Vagrant/';

		if (!mkdir($configurationDirectory, 0777)) {
			throw new \Exception('Failed to create folder: ' . $configurationDirectory);
		}

		$this->output->writeln('Creating Configuration/Development/Vagrant/Settings.yaml');
		$settingsYamlTemplate = new \Rosemary\Service\Template(\Rosemary\Utility\General::getResourcePathAndName('SettingsYaml.template'));
		$settingsYamlTemplate->setVar('host', $this->configuration['database']['host']);
		$settingsYamlTemplate->setVar('user', $this->configuration['database']['username']);
		$settingsYamlTemplate->setVar('password', $this->configuration['database']['password']);
		$settingsYamlTemplate->setVar('dbname', sprintf($this->configuration['database']['database'], $this->installationConfiguration['name']));
		$fileContent = $settingsYamlTemplate->render();
		file_put_contents($this->configuration['locations']['document_root'] . '/' . $this->installationConfiguration['name'] . '/flow/Configuration/Development/Vagrant/Settings.yaml', $fileContent);
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
		\Rosemary\Utility\General::runCommand($this->output, $command, 'Adjust file permissions for CLI and web server access');
	}

	private function task_createVhost() {
		$virtualHostTemplate = new \Rosemary\Service\Template(\Rosemary\Utility\General::getResourcePathAndName('VirtualHostFlow.template'));
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
}
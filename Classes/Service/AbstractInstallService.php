<?php

namespace Rosemary\Service;


abstract class AbstractInstallService {
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

	protected function task_installVhostAndRestartApache() {
		$command = vsprintf('sudo a2ensite %s', $this->installationConfiguration['name']);
		$this->output->writeln('  - Install vhost');
		\Rosemary\Utility\General::runCommand($this->output, $command);

		$command = 'sudo apache2ctl graceful';
		$this->output->writeln('  - Restart apache');
		\Rosemary\Utility\General::runCommand($this->output, $command);
	}

	protected function task_createDirectories($addDocsDirectory = FALSE) {
		$installDirectory = $this->configuration['locations']['document_root'] . '/' . $this->installationConfiguration['name'] . '/';

		$this->output->writeln(vsprintf('Creating directory structure at: %s', array($installDirectory)));

		if (!mkdir($installDirectory, 0777)) {
			throw new \Exception('Failed to create folder: ' . $installDirectory);
		}

		if (!mkdir($installDirectory . 'logs/', 0777, TRUE)) {
			throw new \Exception('Failed to create folder: ' . $installDirectory . 'logs/');
		}

		if (!mkdir($installDirectory . 'sync/', 0777, TRUE)) {
			throw new \Exception('Failed to create folder: ' . $installDirectory . 'sync/');
		}

		if ($addDocsDirectory) {
			if (!mkdir($installDirectory . 'docs/', 0777, TRUE)) {
				throw new \Exception('Failed to create folder: ' . $installDirectory . 'sync/');
			}
		}
	}

	protected function task_createDatabase() {
		$command = vsprintf(
			'mysql -h %s -u %s %s -e "DROP DATABASE IF EXISTS \`%s\`; CREATE DATABASE \`%s\` DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci;"',
			array(
				$this->configuration['database_root']['host'],
				$this->configuration['database_root']['username'],
				($this->configuration['database_root']['password'] != '') ? '-p' . $this->configuration['database_root']['password'] : '',
				$this->installationConfiguration['name'],
				$this->installationConfiguration['name']
			)
		);
		$this->output->writeln('Create database: ' . $this->installationConfiguration['name']);
		\Rosemary\Utility\General::runCommand($this->output, $command);
	}

	protected function task_executePostInstallCommands() {
		if ($this->installationConfiguration['seed'] !== NULL) {
			foreach (\Rosemary\Utility\General::getSeeds() as $siteSeed => $seedConfiguration) {
				if ($siteSeed === $this->installationConfiguration['seed']) {
					break;
				}
			}

			chdir($this->configuration['locations']['document_root'] . '/' . $this->installationConfiguration['name']);

			if (isset($seedConfiguration['post-install-cmd'])) {
				foreach ($seedConfiguration['post-install-cmd'] as $postInstallCommand) {
					$postInstallCommandTemplate = new \Rosemary\Service\TemplateService($postInstallCommand);

					$postInstallCommandTemplate->setVar('installationName', $this->installationConfiguration['name']);
					$postInstallCommandTemplate->setVar('seedConfiguration', json_encode($seedConfiguration));

					$finalPostInstallCommand = $postInstallCommandTemplate->render();
					try {
						$this->output->writeln('Running post install command: ' . $finalPostInstallCommand);
						\Rosemary\Utility\General::runCommand($this->output, $finalPostInstallCommand);
					} catch (\Exception $e) {
						$this->output->writeln('FAIELD: Post install command: ' . $finalPostInstallCommand);
					}
				}
			}
		}
	}
}


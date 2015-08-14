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

	protected function task_createDirectories() {
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
	}

	protected function task_executePostCreateCommands() {
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
					\Rosemary\Utility\General::runCommand($this->output, $postCreateCommand);
				}
			}
		}
	}
}


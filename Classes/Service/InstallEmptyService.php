<?php

namespace Rosemary\Service;

class InstallEmptyService {

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
			$this->output->writeln('InstallEmptyService');
			$this->task_createDirectories();
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

}
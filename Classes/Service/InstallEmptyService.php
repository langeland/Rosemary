<?php

namespace Rosemary\Service;

class InstallEmptyService extends AbstractInstallService {

	public function install($installationConfiguration) {
		$this->installationConfiguration = $installationConfiguration;

		try {
			$this->output->writeln('InstallEmptyService');
			$this->task_createDirectories(TRUE);
			$this->task_createVhost();
			$this->task_installVhostAndRestartApache();
		} catch (\Exception $e) {
			die('It all stops here: ' . $e->getMessage());
		}
	}

	private function task_createVhost() {
		$virtualHostTemplate = new \Rosemary\Service\Template(\Rosemary\Utility\General::getResourcePathAndName('VirtualHostEmpty.template'));
		$virtualHostTemplate->setVar('installationName', $this->installationConfiguration['name']);
		$virtualHostTemplate->setVar('documentRoot', $this->configuration['locations']['document_root']);
		$virtualHostTemplate->setVar('htdocs', 'docs');
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
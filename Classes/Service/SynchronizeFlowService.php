<?php

namespace Rosemary\Service;

class SynchronizeFlowService extends AbstractSynchronizeService {


	protected function task_syncronizeFiles() {

		if (!is_dir($this->configuration['locations']['document_root'] . '/' . $this->installationName . '/flow/Data/Persistent/Resources')) {
			$this->output->writeln('  - Creating Resources directory', \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_VERBOSE);
			mkdir($this->configuration['locations']['document_root'] . '/' . $this->installationName . '/flow/Data/Persistent/Resources', 0777, TRUE);
		}

		$cmd = vsprintf('rsync -av --delete %sflow/Data/Persistent/Resources/ %s/%s/flow/Data/Persistent/Resources/', array(
			$this->seed['datasource'],
			$this->configuration['locations']['document_root'],
			$this->installationName
		));

		\Rosemary\Utility\General::runCommand($this->output, $cmd, 'Synchronize Resources');
	}
}
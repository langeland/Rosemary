<?php

namespace Rosemary\Command;

class SynchronizeCommand extends \Rosemary\Command\AbstractCommand {

	protected function configure() {
		parent::configure();
		$this
			->setName('sync')
			->setAliases(array('pull'))
			->setDescription('Synchronize data from moc-files to local project')
			->addArgument('seedName', \Symfony\Component\Console\Input\InputArgument::REQUIRED, 'Set the site seed. See available seeds with rosemary list-seeds')
			->addArgument('installationName', \Symfony\Component\Console\Input\InputArgument::OPTIONAL, 'Set the name of the installation. If not given, the seed is used');
	}

	protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
		$this->input = $input;
		$this->output = $output;

		try {

			/*******************************************************************************************************************
			 * Validating seed name
			 ******************************************************************************************************************/
			if (!$seed = \Rosemary\Utility\General::getSeed($input->getArgument('seedName'))) {
				throw new \Exception(sprintf('Unable to sync for unknown seedName: %s', $input->getArgument('seedName')));
			}

			/*******************************************************************************************************************
			 * Validating installation name
			 ******************************************************************************************************************/
			if ($input->getArgument('installationName') != '') {
				$installationName = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", $this->input->getArgument('installationName')));;
			} else {
				$installationName = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", $this->input->getArgument('seedName')));;
			}

			if (!is_dir($this->configuration['locations']['document_root'] . '/' . $installationName)) {
				throw new \Exception('Installation not found. (' . $this->configuration['locations']['document_root'] . '/' . $installationName . ')');
			}

			/*******************************************************************************************************************
			 *
			 ******************************************************************************************************************/
			$output->writeln('SEED');
			print_r($seed);

			$output->writeln('INSTALLATION NAME');
			$output->writeln($installationName);

			/*******************************************************************************************************************
			 * Do the stuff
			 ******************************************************************************************************************/
			if ($seed['type'] === 'flow') {
				$synchronizer = new \Rosemary\Service\SynchronizeFlowService($input, $output);
				$synchronizer->synchronize($seed, $installationName);
			} elseif ($seed['type'] === 'cms') {
				$synchronizer = new \Rosemary\Service\SynchronizeCmsService($input, $output);
				$synchronizer->synchronize($seed, $installationName);
			} else {
				throw new \Exception('installationType is not valid');
			}

		} catch (\Exception $e) {
			die('Error on validate arguments: ' . $e->getMessage());
		}

	}
}
<?php

namespace Rosemary\Command;

use Rosemary\Utility\General;

class InstallCommand extends \Rosemary\Command\AbstractCommand {

	private $installationName = NULL;

	private $installationSource = NULL;

	private $installationSeed = NULL;

	private $installationSeedConfiguration = NULL;

	private $installationInstaller = NULL;

	private $installationType = NULL;

	/**
	 * @return null
	 */
	protected function configure() {
		$this
			->setName('install')
			->setDescription('Create blank project')
			->setHelp(file_get_contents(ROOT_DIR . '/Resources/InstallCommandHelp.text'))
			->addArgument('source', \Symfony\Component\Console\Input\InputArgument::REQUIRED, 'Set the source of the installation. Can be a site seed, a packagist package "vendor/package" or a git reposirory "git@github.com:user/vendor-package.git"')
			->addArgument('name', \Symfony\Component\Console\Input\InputArgument::OPTIONAL, 'Set the name of the installation. If no name is given, then the seed is used')
			->addOption('empty', null, \Symfony\Component\Console\Input\InputOption::VALUE_NONE, 'Set to create an empty site');
	}

	/**
	 * @param \Symfony\Component\Console\Input\InputInterface $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 * @throws \Exception
	 * @return null
	 */
	protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
		$this->input = $input;
		$this->output = $output;

		try {
			$this->prepare();

			/*******************************************************************************************************************
			 *
			 ******************************************************************************************************************/
			if ($this->installationSeed !== NULL) {
				$this->output->writeln('<info>Installation Seed Configuration</info>');
				$table = new \Symfony\Component\Console\Helper\Table($output);
				$table
					->setRows(array(
						array('description', $this->installationSeedConfiguration['description']),
						array('source', $this->installationSeedConfiguration['source']),
						array('type', $this->installationSeedConfiguration['type']),
						array('datasource', $this->installationSeedConfiguration['datasource']),
					));
				$table->render();
				$this->output->writeln('');
			}

			/*******************************************************************************************************************
			 *
			 ******************************************************************************************************************/
			$this->output->writeln('<info>Installation Configuration</info>');
			$table = new \Symfony\Component\Console\Helper\Table($output);
			$table
				->setRows(array(
					array('Installation name', $this->installationName),
					array('Installation source', $this->installationSource),
					array('Installation seed', $this->installationSeed),
					array('Installation installer', $this->installationInstaller),
					array('Installation type', $this->installationType),
				));
			$table->render();

			/*******************************************************************************************************************
			 *
			 ******************************************************************************************************************/
			$this->output->writeln('<info>Logfile: ' . LOG_FILE . '</info>');

			/*******************************************************************************************************************
			 *
			 ******************************************************************************************************************/
			$installationConfiguration = array(
				'name' => $this->installationName,
				'source' => $this->installationSource,
				'seed' => $this->installationSeed,
				'installer' => $this->installationInstaller,
				'type' => $this->installationType
			);

			/*******************************************************************************************************************
			 *
			 ******************************************************************************************************************/
			$helper = $this->getHelper('question');
			$question = new \Symfony\Component\Console\Question\ConfirmationQuestion('Continue installation? [Y/n] ', TRUE);

			if (!$helper->ask($input, $output, $question)) {
				$output->writeln('The installation was cancelled.');
				return;
			}

			/*******************************************************************************************************************
			 *
			 ******************************************************************************************************************/

			if ($this->installationType === 'flow') {
				$installer = new \Rosemary\Service\InstallFlowService($input, $output);
				$installer->install($installationConfiguration);
			} elseif ($this->installationType === 'cms') {
				$installer = new \Rosemary\Service\InstallCmsService($input, $output);
				$installer->install($installationConfiguration);
			} elseif ($this->installationType === 'empty') {
				$installer = new \Rosemary\Service\InstallEmptyService($input, $output);
				$installer->install($installationConfiguration);
			} else {
				throw new \Exception('installationType is not valid');
			}

		} catch (\Exception $e) {
			die('It all stops here: ' . $e->getMessage());
		}
	}

	/**
	 * @throws \Exception
	 * @return null
	 */
	protected function prepare() {
		/*******************************************************************************************************************
		 * Prepare source
		 ******************************************************************************************************************/
		if ($seedConfiguration = General::getSeed($this->input->getArgument('source'))) {
			$this->installationSource = $seedConfiguration['source'];
			$this->installationSeed = $this->input->getArgument('source');
			$this->installationSeedConfiguration = $seedConfiguration;
		} elseif ($this->input->getOption('empty')) {
			$this->installationSource = '';
		} else {
			$this->installationSource = $this->input->getArgument('source');
		}

		if (preg_match("*^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+?$*", $this->installationSource)) {
			$this->installationInstaller = 'composer';
		} elseif (preg_match('*([A-Za-z0-9]+@|http(|s)\:\/\/)([A-Za-z0-9.]+)(:|/)([A-Za-z0-9\/]+)(\.git)?*', $this->installationSource)) {
			$this->installationInstaller = 'git';
		} elseif ($this->input->getOption('empty')) {
			$this->installationInstaller = 'none';
		} else {
			throw new \Exception('Source is not a valid seed, git repository or Packagist package');
		}

		/*******************************************************************************************************************
		 * Prepare name
		 ******************************************************************************************************************/
		if ($this->input->getArgument('name') != '') {
			$this->installationName = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", $this->input->getArgument('name')));
		} elseif (!is_null($this->installationSeed) || $this->input->getOption('empty')) {
			$this->installationName = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", $this->input->getArgument('source')));
		} else {
			throw new \Exception('Cannot determine a installation name');
		}

		/*******************************************************************************************************************
		 * Prepare type
		 ******************************************************************************************************************/
		if ($this->installationType === NULL) {

			if ($this->installationSeed !== NULL && isset($this->installationSeedConfiguration['type'])) {
				$this->installationType = $this->installationSeedConfiguration['type'];
			} elseif ($this->input->getOption('empty')) {
				$this->installationType = 'empty';
			} else {
				$helper = $this->getHelper('question');
				$question = new \Symfony\Component\Console\Question\ChoiceQuestion(
					'Please select inatallation type (defaults to empty)',
					array('empty', 'cms', 'flow/neos'),
					'0'
				);
				$question->setErrorMessage('That\'s not an answer.');
				$this->installationType = $helper->ask($this->input, $this->output, $question);
			}
		}
	}

}

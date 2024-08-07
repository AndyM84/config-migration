<?php

	namespace AndyM84\Config;

	/**
	 * Class that performs migration of a configuration file between versions.
	 *
	 * @version 1.1
	 * @author Andrew Male (AndyM84)
	 * @package AndyM84\Config
	 */
	class Migrator {
		/**
		 * Collection of migration files containing instructions.
		 *
		 * @var MigrationFile[]
		 */
		protected array $files = [];
		/**
		 * Path to the directory containing migration instruction files.
		 *
		 * @var ?string
		 */
		protected ?string $migrationDirectory = null;
		/**
		 * Extension to use for migration instruction files.
		 *
		 * Defaults to '.cfg'.
		 *
		 * @var ?string
		 */
		protected ?string $migrationExtension = null;
		/**
		 * Path to the settings file which will have the migration instructions applies to it.
		 *
		 * @var ?string
		 */
		protected ?string $settingsFile = null;


		/**
		 * Instantiates a new Migrator, attempting to load all instruction files in preparation for migration.
		 *
		 * @param string $migrationDirectory The directory path where the instruction files exist.
		 * @param string $settingsFile The name of the settings file to read/generate, defaults to 'siteSettings.json'.
		 * @param string $migrationExtension The file extension used by the instruction files, defaults to '.cfg'.
		 * @throws \InvalidArgumentException
		 */
		public function __construct(string $migrationDirectory, string $settingsFile = 'siteSettings.json', string $migrationExtension = '.cfg') {
			$migrationDirectory = str_replace("\\", "/", $migrationDirectory);

			if (!is_dir($migrationDirectory)) {
				throw new \InvalidArgumentException("Invalid migration directory");
			}

			if (!str_ends_with($migrationDirectory, '/')) {
				$migrationDirectory .= "/";
			}

			$this->migrationDirectory = $migrationDirectory;

			if ($migrationExtension === null || empty(trim($migrationExtension))) {
				throw new \InvalidArgumentException("Invalid migration extension");
			}

			$this->settingsFile = $settingsFile;
			$this->migrationExtension = $migrationExtension;

			foreach (glob("{$this->migrationDirectory}*{$this->migrationExtension}") as $file) {
				$fh = @fopen($file, 'r');

				if ($fh) {
					$lines = array();

					while (($buf = fgets($fh)) !== false) {
						$lines[] = trim(preg_replace("/\R/", "", $buf));
					}

					if (feof($fh) && count($lines) > 0) {
						$this->files[] = new MigrationFile($file, $lines, $this->migrationExtension);
					}

					@fclose($fh);
				}
			}

			if (count($this->files) > 0) {
				usort($this->files, function ($a, $b) {
					if ($a->origVersion == $b->origVersion) {
						return 0;
					}

					return ($a->origVersion < $b->origVersion) ? -1 :  1;
				});
			}

			return;
		}

		/**
		 * Perform migration of settings file using the loaded instruction files.
		 *
		 * @return void
		 */
		public function migrate() : void {
			$currentSettings = new ConfigContainer();

			if (file_exists($this->settingsFile)) {
				$currentSettings = new ConfigContainer(file_get_contents($this->settingsFile));
			}

			if (!$currentSettings->has('configVersion')) {
				$currentSettings->set('configVersion', 0, FieldTypes::INTEGER);
			}

			$filesToApply = array();
			$currentVersion = $currentSettings->get('configVersion');

			foreach ($this->files as $file) {
				if ($file->origVersion >= $currentVersion) {
					$filesToApply[] = $file;
				}
			}

			foreach ($filesToApply as $file) {
				foreach ($file->actions as $action) {
					switch ($action->operator->getValue()) {
						case MigrationOperators::ADD:
							if ($action->type->isArrayType()) {
								if ($currentSettings->has($action->field)) {
									$tmp = $currentSettings->get($action->field);
									$currentSettings->set($action->field, array_merge($tmp, [$action->value]), $action->type->getValue());
								} else {
									$currentSettings->set($action->field, $action->value, $action->type->getValue());
								}

								break;
							}

							if (!$currentSettings->has($action->field)) {
								$currentSettings->set($action->field, $action->value, $action->type->getValue());
							}

							break;
						case MigrationOperators::CHANGE:
							if ($currentSettings->getType($action->field)->isArrayType()) {
								break;
							}

							$currentSettings->set($action->field, $action->value);

							break;
						case MigrationOperators::REMOVE:
							$currentSettings->remove($action->field);

							break;
						case MigrationOperators::RENAME:
							$currentSettings->rename($action->field, $action->value);

							break;
					// @codeCoverageIgnoreStart
						default:

							break;
					// @codeCoverageIgnoreEnd
					}
				}

				$currentSettings->set('configVersion', intval($file->destVersion));
			}

			file_put_contents($this->settingsFile, json_encode($currentSettings, JSON_PRETTY_PRINT));

			return;
		}
	}

<?php

	namespace AndyM84\Config;

	/**
	 * Class that represents a single settings node with children.
	 *
	 * @version 1.3
	 * @author Andrew Male (AndyM84)
	 * @package AndyM84\Config
	 */
	class SettingsNodeGroup {
		public array $keys = [];
		public int $numChildren;


		/**
		 * Instantiates a new SettingNodeGroup object.
		 *
		 * @param string $key String key for the setting node group.
		 * @param int $index Current index for children traversal.
		 * @param array $children The children for the setting node.
		 */
		public function __construct(
			public string $key,
			public int $index,
			public array $children
		) {
			$this->keys        = array_keys($this->children);
			$this->numChildren = count($this->children);

			return;
		}
	}

	/**
	 * Class that provides basic operations on configuration settings.
	 *
	 * @version 1.3
	 * @author Andrew Male (AndyM84)
	 * @package AndyM84\Config
	 */
	class ConfigContainer implements \JsonSerializable {
		/**
		 * Collection of configuration settings and their field types.
		 *
		 * @var array
		 */
		protected array $schema = [];
		/**
		 * Collection of configuration settings and their values.
		 *
		 * @var array
		 */
		protected array $settings = [];


		/**
		 * Instantiates a new ConfigContainer object.
		 *
		 * @param null|string $jsonString Optional JSON string to attempt pulling settings from.
		 */
		public function __construct(null|string $jsonString = null) {
			$data = null;

			if ($jsonString !== null) {
				$data = json_decode($jsonString, true);
			}

			if ($data === null || array_key_exists('schema', $data) === false || array_key_exists('settings', $data) === false) {
				return;
			}

			foreach ($data['settings'] as $field => $value) {
				if (!is_array($value)) {
					if (array_key_exists($field, $data['schema']) === false) {
						continue;
					}

					$this->schema[$field]   = FieldTypes::fromString($data['schema'][$field]);
					$this->settings[$field] = $value;

					continue;
				}

				if (array_key_exists($field, $data['schema']) !== false) {
					$type = FieldTypes::fromString($data['schema'][$field]);

					if ($type->isArrayType()) {
						$this->schema[$field]   = $type;
						$this->settings[$field] = $value;
					}
				}

				$keyParts  = explode('.', $field);
				$node      = new SettingsNodeGroup($keyParts[0], 0, $data['settings'][$keyParts[0]]);
				$nodePairs = $this->readNode($node, $data['schema']);

				foreach ($nodePairs as $key => $val) {
					$this->schema[$key]   = FieldTypes::fromString($data['schema'][$key]);
					$this->settings[$key] = $val;
				}
			}

			return;
		}

		/**
		 * Attempts to retrieve a setting.
		 *
		 * @param string $field Name of field to try retrieving.
		 * @param mixed $defaultValue Optional default value to use if setting not present.
		 * @return mixed
		 */
		public function get(string $field, mixed $defaultValue = null) : mixed {
			if ($this->has($field)) {
				return $this->settings[$field];
			}

			return $defaultValue;
		}

		/**
		 * Retrieves the configuration schema.
		 *
		 * @return array
		 */
		public function getSchema() : array {
			return $this->schema;
		}

		/**
		 * Retrieves the configuration settings.
		 *
		 * @return array
		 */
		public function getSettings() : array {
			return $this->settings;
		}

		/**
		 * Retrieves the type of specific setting, if possible.
		 *
		 * @param string $field String value of field name.
		 * @return FieldTypes
		 */
		public function getType(string $field) : FieldTypes {
			if ($this->has($field)) {
				return $this->schema[$field];
			}

			return new FieldTypes(null);
		}

		/**
		 * Determines whether the setting exists within the configuration.
		 *
		 * @param string $field String value of field name.
		 * @return bool
		 */
		public function has(string $field) : bool {
			return array_key_exists($field, $this->schema) !== false && array_key_exists($field, $this->settings) !== false;
		}

		/**
		 * Converts the configuration object into a JSON serializable array.
		 *
		 * @return array
		 */
		public function jsonSerialize() : mixed {
			$settings = [];

			foreach ($this->settings as $key => $val) {
				if (stripos($key, '.') === false) {
					$settings[$key] = $val;

					continue;
				}

				$tmp			= &$settings;
				$keyParts = explode('.', $key);
				$numParts = count($keyParts);

				for ($i = 0; $i < $numParts; $i++) {
					$currPart = $keyParts[$i];

					if (array_key_exists($currPart, $tmp) === false) {
						$tmp[$currPart] = [];
					}

					if ($i === $numParts - 1) {
						$tmp[$currPart] = $val;

						break;
					}

					if (array_key_exists($currPart, $tmp) === false) {
						$tmp[$currPart] = [];
					}

					$tmp = &$tmp[$currPart];
				}
			}

			return [
				'schema'   => $this->schema,
				'settings' => $settings,
			];
		}

		/**
		 * Reads a node from the settings and produces a flattened array of values that are validated against the schema.
		 * For example:
		 *
		 * {
		 *   'key1': 'value1',
		 *   'key2': {
		 *     'key3': 'value3'
		 *   },
		 *   'key4': {
		 *     'key5': {
		 *       'key6': 'value6'
		 *     }
		 *   }
		 * }
		 *
		 * Would produce:
		 *
		 * [
		 *   'key1' => 'value1',
		 *   'key2.key3' => 'value3',
		 *   'key4.key5.key6' => 'value6'
		 * ]
		 *
		 * @param SettingsNodeGroup $node The node to read.
		 * @param array $schema The schema to use for reading.
		 * @return array
		 */
		protected function readNode(SettingsNodeGroup $node, array $schema) : array {
			$ret        = [];
			$currNode   = $node;
			$nodeGroups = new \SplStack();

			do {
				while ($currNode->index < $currNode->numChildren) {
					$innerKey = $currNode->keys[$currNode->index++];
					$innerVal = $currNode->children[$innerKey];
					$dotKey   = "{$currNode->key}.{$innerKey}";

					if (array_key_exists($dotKey, $schema) !== false) {
						$ret[$dotKey] = $innerVal;

						continue;
					}

					if (!is_array($innerVal)) {
						continue;
					}

					$nodeGroups->push($currNode);
					$currNode = new SettingsNodeGroup($dotKey, 0, $innerVal);

					continue;
				}

				if ($nodeGroups->isEmpty()) {
					break;
				}

				$currNode = $nodeGroups->pop();
			} while ($currNode !== null);

			return $ret;
		}

		/**
		 * Attempts to remove a setting from the configuration.
		 *
		 * @param string $field String value of the field name.
		 * @throws \InvalidArgumentException
		 * @return void
		 */
		public function remove(string $field) : void {
			if (!$this->has($field)) {
				throw new \InvalidArgumentException("Cannot remove a field that doesn't exist");
			}

			unset($this->schema[$field]);
			unset($this->settings[$field]);

			return;
		}

		/**
		 * Attempts to rename a setting in the configuration.
		 *
		 * @param string $oldField Current string value of the field name.
		 * @param string $newField New string value of the field name.
		 * @throws \InvalidArgumentException
		 * @return void
		 */
		public function rename(string $oldField, string $newField) : void {
			if (!$this->has($oldField)) {
				throw new \InvalidArgumentException("Cannot rename a field that doesn't exist");
			}

			$this->schema[$newField] = $this->schema[$oldField];
			$this->settings[$newField] = $this->settings[$oldField];

			$this->remove($oldField);

			return;
		}

		/**
		 * Attempts to set a setting in the configuration.
		 *
		 * @param string $field String value of the field name.
		 * @param mixed $value Value to set field to in configuration.
		 * @param ?int $type Integer value of field type, only used if field doesn't already exist.
		 * @throws \InvalidArgumentException
		 * @return void
		 */
		public function set(string $field, mixed $value, ?int $type = null) : void {
			if (is_string($value) && stripos($value, '${') !== false) {
				$replacements = [];

				foreach ($this->settings as $key => $val) {
					if ($this->schema[$key]->isArrayType()) {
						continue;
					}

					$replacements["\${{$key}}"] = $val;
				}

				if (count($replacements) > 0) {
					$value = str_replace(array_keys($replacements), array_values($replacements), $value);
				}
			}

			if (!$this->has($field)) {
				if (FieldTypes::validValue($type) === false) {
					throw new \InvalidArgumentException("Invalid type given for new setting");
				}

				$this->schema[$field] = new FieldTypes($type);
			}

			switch ($this->schema[$field]->getValue()) {
				case FieldTypes::BOOLEAN:
					$lValue = strtolower($value);

					if ($lValue == 'true' || $lValue == 'false') {
						$this->settings[$field] = $lValue == 'true';
					} else {
						$this->settings[$field] = boolval($value);
					}

					break;
				case FieldTypes::FLOAT:
					$this->settings[$field] = floatval($value);

					break;
				case FieldTypes::INTEGER:
					$this->settings[$field] = intval($value);

					break;
				case FieldTypes::STRING:
					$this->settings[$field] = "{$value}";

					break;
				case FieldTypes::BOOLEAN_ARR:
					if (!is_array($value)) {
						$this->settings[$field] = [boolval($value)];
					} else {
						$this->settings[$field] = array_map(function ($val) { return boolval($val); }, $value);
					}

					break;
				case FieldTypes::FLOAT_ARR:
					if (!is_array($value)) {
						$this->settings[$field] = [floatval($value)];
					} else {
						$this->settings[$field] = array_map(function ($val) { return floatval($val); }, $value);
					}

					break;
				case FieldTypes::INTEGER_ARR:
					if (!is_array($value)) {
						$this->settings[$field] = [intval($value)];
					} else {
						$this->settings[$field] = array_map(function ($val) { return intval($val); }, $value);
					}

					break;
				case FieldTypes::STRING_ARR:
					if (!is_array($value)) {
						$this->settings[$field] = ["{$value}"];
					} else {
						$this->settings[$field] = array_map(function ($val) { return "{$val}"; }, $value);
					}

					break;
				// @codeCoverageIgnoreStart
				default:

					break;
				// @codeCoverageIgnoreEnd
			}

			return;
		}
	}

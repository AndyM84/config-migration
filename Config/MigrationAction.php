<?php

	namespace AndyM84\Config;

	/**
	 * Class that represents a single action from an instruction file.
	 *
	 * @version 1.1
	 * @author Andrew Male (AndyM84)
	 * @package AndyM84\Config
	 */
	class MigrationAction {
		/**
		 * The name of the field the action will be performed upon.
		 *
		 * @var string
		 */
		public string $field;
		/**
		 * The operator representing the operation which will be performed.
		 *
		 * @var MigrationOperators
		 */
		public MigrationOperators $operator;
		/**
		 * The type of the field, if provided.
		 *
		 * @var FieldTypes
		 */
		public FieldTypes $type;
		/**
		 * The value of the field, if provided.
		 *
		 * @var mixed
		 */
		public mixed $value;


		/**
		 * Instantiates a new MigrationAction object, parsing the provided instruction line into its pieces.
		 *
		 * @param string $string String value of line from instruction file.
		 * @throws \InvalidArgumentException
		 */
		public function __construct(string $string) {
			$this->value = null;
			$field       = substr($string, 0, stripos($string, ' '));
			
			if (stripos($field, '[') !== false) {
				$this->field = substr($field, 0, stripos($field, '['));

				if (stripos($field, '[]') !== false) {
					$this->type = FieldTypes::fromString(substr($field, stripos($field, '[') + 1, 5));
				} else {
					$this->type = FieldTypes::fromString(substr($field, stripos($field, '[') + 1, 3));
				}
			} else {
				$this->field = $field;
			}

			if (strtolower($this->field) == 'configversion') {
				throw new \InvalidArgumentException("Cannot access or modify the configVersion setting");
			}

			$this->operator = MigrationOperators::fromString(substr($string, strlen($field) + 1, 1));

			if (strlen($string) > (strlen($field) + 3)) {
				$this->value = substr($string, strlen($field) + 3);
			}

			if ($this->operator->getValue() < 3 && $this->value === null) {
				throw new \InvalidArgumentException("Non-REMOVE action without a value");
			}

			if ($this->value == '""') {
				$this->value = '';
			}

			return;
		}
	}

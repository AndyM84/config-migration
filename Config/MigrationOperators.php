<?php

	namespace AndyM84\Config;

	/**
	 * Enumerated operators available for use in instructions.
	 *
	 * @version 1.1
	 * @author Andrew Male (AndyM84)
	 * @package AndyM84\Config
	 */
	class MigrationOperators implements \JsonSerializable {
		const ERROR = 0;
		const ADD = 1;
		const CHANGE = 2;
		const REMOVE = 3;
		const RENAME = 4;

		/**
		 * Name of set value.
		 *
		 * @var string
		 */
		protected string $name = 'err';
		/**
		 * Value set for object.
		 *
		 * @var int
		 */
		protected int $value = self::ERROR;
		/**
		 * Static lookup for names to values.
		 *
		 * @var array
		 */
		protected static array $lookup = array(
			'+' => self::ADD,
			'=' => self::CHANGE,
			'-' => self::REMOVE,
			'>' => self::RENAME
		);


		/**
		 * Static method to generate a new MigrationOperator object from its string representation.
		 *
		 * @param string $string String representation of an operator value.
		 * @return MigrationOperators
		 */
		public static function fromString(string $string) : MigrationOperators {
			$string = strtolower($string);

			foreach (static::$lookup as $str => $val) {
				if ($string === $str) {
					return new MigrationOperators($val);
				}
			}

			return new MigrationOperators(null);
		}

		/**
		 * Static method to determine if a given name is a valid string representation of a value.
		 *
		 * @param string $name String representation of an operator value.
		 * @return bool
		 */
		public static function validName(string $name) : bool {
			return array_key_exists(strtolower($name), static::$lookup);
		}

		/**
		 * Static method to determine if a given integer is a valid operator value.
		 *
		 * @param int $value Integer value to check as an operator value.
		 * @return bool
		 */
		public static function validValue(int $value) : bool {
			foreach (static::$lookup as $validValue) {
				if ($validValue === $value) {
					return true;
				}
			}

			return false;
		}


		/**
		 * Instantiates a new MigrationOperators object.
		 *
		 * @param ?int $value Integer value for the operator.
		 */
		public function __construct(?int $value) {
			if ($value !== null) {
				foreach (static::$lookup as $name => $val) {
					if ($value === $val) {
						$this->name = $name;
						$this->value = $val;

						break;
					}
				}
			}

			return;
		}

		/**
		 * Converts a MigrationOperators object into the name of the operator.
		 *
		 * @return string
		 */
		public function __toString() : string {
			return $this->name;
		}

		/**
		 * Checks if the operator has the same value as the provided integer.
		 *
		 * @param int $value Integer to compare operator value against.
		 * @return bool
		 */
		public function is(int $value) : bool {
			if ($this->value === $value) {
				return true;
			}

			return false;
		}

		/**
		 * Serializes the MigrationOperators object, returning the name string.
		 *
		 * @return string
		 */
		public function jsonSerialize() : string {
			return $this->name;
		}

		/**
		 * Retrieves the name of the object.
		 *
		 * @return string
		 */
		public function getName() : string {
			return $this->name;
		}

		/**
		 * Retrieves the value of the object.
		 *
		 * @return int
		 */
		public function getValue() : int {
			return $this->value;
		}
	}

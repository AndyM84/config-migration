<?php

	namespace AndyM84\Config;

	/**
	 * Enumerated types available for use in configuration.
	 *
	 * @version 1.1
	 * @author Andrew Male (AndyM84)
	 * @package AndyM84\Config
	 */
	class FieldTypes implements \JsonSerializable {
		const ERROR = 0;
		const BOOLEAN = 1;
		const FLOAT = 2;
		const INTEGER = 3;
		const STRING = 4;

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
		protected static array $lookup = [
			'bln' => self::BOOLEAN,
			'flt' => self::FLOAT,
			'int' => self::INTEGER,
			'str' => self::STRING
		];


		/**
		 * Static method to generate a new FieldTypes object from its string representation.
		 *
		 * @param string $string String representation of a field type.
		 * @return FieldTypes
		 */
		public static function fromString(string $string) : FieldTypes {
			$string = strtolower($string);

			foreach (static::$lookup as $str => $val) {
				if ($string === $str) {
					return new FieldTypes($val);
				}
			}

			return new FieldTypes(null);
		}

		/**
		 * Static method to determine if a given name is a valid string representation of a value.
		 *
		 * @param string $name String representation of a value.
		 * @return bool
		 */
		public static function validName(string $name) : bool {
			return array_key_exists(strtolower($name), static::$lookup);
		}

		/**
		 * Static method to determine if a given integer is a valid field type.
		 *
		 * @param int $value Integer value to check as a field type.
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
		 * Instantiates a new FieldTypes object.
		 *
		 * @param ?int $value Integer value for the field type.
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
		 * Converts a FieldTypes object into the name of the field type.
		 *
		 * @return string
		 */
		public function __toString() : string {
			return $this->name;
		}

		/**
		 * Checks if the field type has the same value as the provided integer.
		 *
		 * @param int $value Integer to compare against field type.
		 * @return bool
		 */
		public function is(int $value) : bool {
			if ($this->value === $value) {
				return true;
			}

			return false;
		}

		/**
		 * Serializes the FieldTypes object, returning the name string.
		 *
		 * @return string
		 */
		public function jsonSerialize() : mixed {
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

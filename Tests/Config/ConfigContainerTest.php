<?php

	namespace AndyM84\Tests\Config;

	use PHPUnit\Framework\TestCase;
	use AndyM84\Config\ConfigContainer;
	use AndyM84\Config\FieldTypes;

	class ConfigContainerTest extends TestCase {
		public function test_FieldTypes() {
			$ft = FieldTypes::fromString('bln');
			self::assertTrue($ft->is(FieldTypes::BOOLEAN));
			self::assertEquals(FieldTypes::BOOLEAN, $ft->getValue());
			self::assertEquals('"bln"', json_encode($ft));
			self::assertEquals('bln', $ft->__toString());

			$ft = FieldTypes::fromString('flt');
			self::assertTrue($ft->is(FieldTypes::FLOAT));
			self::assertEquals(FieldTypes::FLOAT, $ft->getValue());
			self::assertEquals('"flt"', json_encode($ft));
			self::assertEquals('flt', $ft->__toString());

			$ft = FieldTypes::fromString('int');
			self::assertTrue($ft->is(FieldTypes::INTEGER));
			self::assertEquals(FieldTypes::INTEGER, $ft->getValue());
			self::assertEquals('"int"', json_encode($ft));
			self::assertEquals('int', $ft->__toString());

			$ft = FieldTypes::fromString('str');
			self::assertTrue($ft->is(FieldTypes::STRING));
			self::assertEquals(FieldTypes::STRING, $ft->getValue());
			self::assertEquals('"str"', json_encode($ft));
			self::assertEquals('str', $ft->__toString());

			$ft = FieldTypes::fromString('bln[]');
			self::assertTrue($ft->is(FieldTypes::BOOLEAN_ARR));
			self::assertEquals(FieldTypes::BOOLEAN_ARR, $ft->getValue());
			self::assertEquals('"bln[]"', json_encode($ft));
			self::assertEquals('bln[]', $ft->__toString());

			$ft = FieldTypes::fromString('flt[]');
			self::assertTrue($ft->is(FieldTypes::FLOAT_ARR));
			self::assertEquals(FieldTypes::FLOAT_ARR, $ft->getValue());
			self::assertEquals('"flt[]"', json_encode($ft));
			self::assertEquals('flt[]', $ft->__toString());

			$ft = FieldTypes::fromString('int[]');
			self::assertTrue($ft->is(FieldTypes::INTEGER_ARR));
			self::assertEquals(FieldTypes::INTEGER_ARR, $ft->getValue());
			self::assertEquals('"int[]"', json_encode($ft));
			self::assertEquals('int[]', $ft->__toString());

			$ft = FieldTypes::fromString('test');
			self::assertFalse($ft->getName() == 'test');
			self::assertFalse($ft->is(FieldTypes::INTEGER));

			self::assertTrue(FieldTypes::validName('int'));
			self::assertFalse(FieldTypes::validName('test'));
			self::assertTrue(FieldTypes::validValue(FieldTypes::INTEGER));
			self::assertFalse(FieldTypes::validValue(-1));

			return;
		}

		public function test_Loading() {
			$settings = [
				'schema' => [
					'test1'             => 'int',
					'test2'             => 'str',
					'test3.test4'       => 'bln',
					'test5'             => 'flt[]',
					'test6.test7.test8' => 'str',
					'test6.test7.test9' => 'str[]',
				],
				'settings' => [
					'test1' => 5,
					'test2' => 'this is a test',
					'test3' => [
						'test4' => true
					],
					'test5' => [3.14, 2.71],
					'test6' => [
						'test7' => [
							'test8' => 'sure',
							'test9' => ['hello', 'world']
						]
					]
				]
			];

			$cfg = new ConfigContainer(json_encode($settings));
			self::assertTrue($cfg->has('test1'));
			self::assertEquals(FieldTypes::INTEGER, $cfg->getType('test1')->getValue());
			self::assertEquals(json_encode($settings), json_encode($cfg));

			$cfg = new ConfigContainer("{ \"test: ");
			self::assertFalse($cfg->has('test1'));

			$cfg = new ConfigContainer(
				json_encode(
					[
						'schema' => ['test1' => 'int'],
						'settings' => []
					]
				)
			);
			self::assertFalse($cfg->has('test1'));

			$cfg = new ConfigContainer(
				json_encode(
					[
						'schema' => ['test1' => 'int'],
						'settings' => ['test2' => false]
					]
				)
			);
			self::assertFalse($cfg->has('test1'));

			return;
		}

		public function test_Retrieval() {
			$settings = [
				'schema' => [
					'test1'             => 'int',
					'test2'             => 'str',
					'test3.test4.test5' => 'int[]',
					'test3.test4.test6' => 'bln',
				],
				'settings' => [
					'test1' => 5,
					'test2' => 'this is a test',
					'test3' => [
						'test4' => [
							'test5' => [1, 2, 3],
							'test6' => true
						]
					]
				]
			];

			$cfg = new ConfigContainer(json_encode($settings));

			self::assertEquals(5, $cfg->get('test1'));
			self::assertNull($cfg->get('test7'));
			self::assertEquals(4, count($cfg->getSchema()));
			self::assertEquals(4, count($cfg->getSettings()));
			self::assertEquals(0, $cfg->getType('test3')->getValue());
			self::assertTrue($cfg->get('test3.test4.test6'));
			self::assertCount(3, $cfg->get('test3.test4.test5'));

			return;
		}

		public function test_Manipulation() {
			$settings = [
				'schema' => [
					'test1'             => 'int',
					'test2'             => 'str',
					'test3.test4'       => 'str',
					'test3.test5.test6' => 'int[]',
					'test7'             => 'flt[]',
				],
				'settings' => [
					'test1' => 5,
					'test2' => 'this is a test',
					'test3' => [
						'test4' => 'hello',
						'test5' => [
							'test6' => [1, 2]
						]
					],
					'test7' => [3.13, 2.15]
				]
			];

			$cfg = new ConfigContainer(json_encode($settings));

			$cfg->remove('test2');
			self::assertFalse($cfg->has('test2'));

			$cfg->rename('test1', 'test');
			self::assertFalse($cfg->has('test1'));
			self::assertTrue($cfg->has('test'));
			self::assertEquals(5, $cfg->get('test'));

			$cfg->set('test3.test5.test6', [3, 4]);
			self::assertCount(2, $cfg->get('test3.test5.test6'));
			self::assertEquals(4, $cfg->get('test3.test5.test6')[1]);

			$cfg->set('test', 10);
			self::assertEquals(10, $cfg->get('test'));

			$cfg->set('test8', false, FieldTypes::BOOLEAN);
			self::assertTrue($cfg->has('test8'));
			self::assertFalse($cfg->get('test8'));

			$cfg->set('test9', 3.14, FieldTypes::FLOAT);
			self::assertTrue($cfg->has('test9'));
			self::assertEquals(3.14, $cfg->get('test9'));

			$cfg->set('test10', 'testing strings', FieldTypes::STRING);
			self::assertTrue($cfg->has('test10'));
			self::assertEquals('testing strings', $cfg->get('test10'));

			$cfg->set('test11', '${test9}', FieldTypes::FLOAT);
			self::assertTrue($cfg->has('test11'));
			self::assertEquals(3.14, $cfg->get('test11'));

			$cfg->set('test12', 'false', FieldTypes::BOOLEAN);
			self::assertTrue($cfg->has('test12'));
			self::assertFalse($cfg->get('test12'));

			try {
				$cfg->remove('test55');
				self::assertTrue(false);
			} catch (\InvalidArgumentException $ex) {
				self::assertEquals("Cannot remove a field that doesn't exist", $ex->getMessage());
			}

			try {
				$cfg->rename('test55', 'testing');
				self::assertTrue(false);
			} catch (\InvalidArgumentException $ex) {
				self::assertEquals("Cannot rename a field that doesn't exist", $ex->getMessage());
			}

			try {
				$cfg->set('test55', 'test', -1);
				self::assertTrue(false);
			} catch (\InvalidArgumentException $ex) {
				self::assertEquals("Invalid type given for new setting", $ex->getMessage());
			}

			return;
		}
	}

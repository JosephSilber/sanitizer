<?php

use Mockery as m;
use Silber\Sanitizer\Sanitizer;

class SanitizerTest extends PHPUnit_Framework_TestCase {

	public function testItCanUseGlobalFunctions()
	{
		$s = new Sanitizer;
		$data = $s->sanitize(['name' => 'Joseph'], ['name' => 'strrev']);
		$this->assertEquals(['name' => 'hpesoJ'], $data);
	}


	public function testItCanUseGlobalFunctionsWithParameters()
	{
		$s = new Sanitizer;
		$data = $s->sanitize(['amount' => '2000'], ['amount' => 'number_format:2,",",.']);
		$this->assertEquals(['amount' => '2.000,00'], $data);
	}


	public function testItCanUseExtensions()
	{
		$s = new Sanitizer;
		$s->extend('uppercase', function($value){ return strtoupper($value); });
		$data = $s->sanitize(['name' => 'Joseph'], ['name' => 'uppercase']);
		$this->assertEquals(['name' => 'JOSEPH'], $data);
	}


	public function testItCanUseExtensionsWithParameters()
	{
		$s = new Sanitizer;
		$s->extend('currency', function($value, $symbol){ return $symbol.number_format($value, 2); });
		$data = $s->sanitize(['amount' => '2000'], ['amount' => 'currency:$']);
		$this->assertEquals(['amount' => '$2,000.00'], $data);
	}


	public function testItCanUseAnArrayCallableExtension()
	{
		$s = new Sanitizer;
		$s->extend('reverse', [new TestSanitizer, 'reverse']);
		$data = $s->sanitize(['name' => 'Joseph'], ['name' => 'reverse']);
		$this->assertEquals(['name' => 'hpesoJ'], $data);
	}


	public function testItCanUseAnArrayCallableExtensionWithParameters()
	{
		$s = new Sanitizer;
		$s->extend('substring', [new TestSanitizer, 'substring']);
		$data = $s->sanitize(['name' => 'Joseph'], ['name' => 'substring:3']);
		$this->assertEquals(['name' => 'eph'], $data);
	}


	public function testItCanUseAClass()
	{
		$s = new Sanitizer;
		$data = $s->sanitize(['name' => 'Joseph'], ['name' => 'TestSanitizer']);
		$this->assertEquals(['name' => 'JOSEPH'], $data);
	}


	public function testItCanUseAClassMethodPair()
	{
		$s = new Sanitizer;
		$data = $s->sanitize(['name' => 'Joseph'], ['name' => 'TestSanitizer@reverse']);
		$this->assertEquals(['name' => 'hpesoJ'], $data);
	}


	public function testItCanUseAClassWithParameters()
	{
		$s = new Sanitizer;
		$data = $s->sanitize(['name' => 'Joseph'], ['name' => 'TestSanitizer@substring:1']);
		$this->assertEquals(['name' => 'oseph'], $data);
	}


	public function testItCanUseAnArrayCallable()
	{
		$s = new Sanitizer;
		$data = $s->sanitize(['name' => 'Joseph'], ['name' => 'TestSanitizer@substring:1']);
		$this->assertEquals(['name' => 'oseph'], $data);
	}


	public function testItUsesAllProvidedRules()
	{
		$s = new Sanitizer;
		$s->extend('reverse', function($value){ return strrev($value); });
		$data = $s->sanitize(['name' => '!!Joseph!!'], ['name' => 'trim:!|TestSanitizer|TestSanitizer@substring:1|reverse']);
		$this->assertEquals(['name' => 'HPESO'], $data);
	}


	public function testItRunsSanitizationOnAllItemsInArray()
	{
		$s = new Sanitizer;
		$data = ['first' => ' Joseph ', 'last' => ' Silber '];
		$data = $s->sanitize($data, ['*' => 'ltrim|TestSanitizer|TestSanitizer@reverse']);
		$this->assertEquals(['first' => ' HPESOJ', 'last' => ' REBLIS'], $data);
	}

	public function testItRunsSanitizationOnAllItemsInNestedArray()
	{
		$s = new Sanitizer;

		$data = ['users' => [
			['first' => ' Joseph ', 'last' => ' Silber '],
			['first' => ' John ', 'last' => ' Doe '],
		]];

		$expected = ['users' => [
			['first' => ' HPESOJ', 'last' => 'REBLIS '],
			['first' => ' NHOJ', 'last' => 'EOD '],
		]];

		$result = $s->sanitize($data, [
			'users.*.first' => 'ltrim|TestSanitizer|TestSanitizer@reverse',
			'users.*.last'  => 'rtrim|TestSanitizer|TestSanitizer@reverse',
		]);

		$this->assertEquals($expected, $result);
	}


}


class TestSanitizer {

	public function sanitize($value)
	{
		return strtoupper($value);
	}

	public function reverse($value)
	{
		return strrev($value);
	}

	public function substring($value, $start)
	{
		return substr($value, $start);
	}

}

<?php namespace Silber\Sanitizer;

use Exception;
use ArrayAccess;

class Sanitizer {

	/**
	 * All of the custom sanitizer extensions.
	 *
	 * @var array
	 */
	protected $extensions = [];

	/**
	 * The container instance.
	 *
	 * @var ArrayAccess
	 */
	protected $container;

	/**
	 * Constructor.
	 *
	 * @param ArrayAccess  $container
	 */
	public function __construct(ArrayAccess $container = null)
	{
		$this->container = $container;
	}

	/**
	 * Sanitize the given data by the given rules.
	 *
	 * @param  array  $data
	 * @param  array  $rules
	 * @return array
	 */
	public function sanitize(array $data, array $rules)
	{
		foreach ($rules as $key => $ruleset)
		{
			$ruleset = $this->explodeRuleset($ruleset);

			$key = $this->explodeKey($key);

			$this->sanitizeByKey($key, $data, $ruleset);
		}

		return $data;
	}

	/**
	 * Register a custom sanitizer extension.
	 *
	 * @param  string  $name
	 * @param  callable  $extension
	 * @return $this
	 */
	public function extend($name, callable $extension)
	{
		$this->extensions[$name] = $extension;
	}

	/**
	 * Explode a key into its segments.
	 *
	 * @param  string  $key
	 * @return array
	 */
	protected function explodeKey($key)
	{
		return explode('.', $key);
	}

	/**
	 * Explode a ruleset into an array of rules.
	 *
	 * @param  array|string  $rules
	 * @return array
	 */
	protected function explodeRuleset($rules)
	{
		if (is_array($rules)) return $rules;

		return explode('|', $rules);
	}

	/**
	 * Validate a given key against a ruleset.
	 *
	 * @param  array  $keys
	 * @param  array  $data
	 * @param  array  $ruleset
	 * @return void
	 */
	protected function sanitizeByKey(array $keys, array &$data, array $ruleset)
	{
		$segment = array_shift($keys);

		if ($segment === '*')
		{
			return $this->runArrayRuleset($keys, $data, $ruleset);
		}

		if ( ! array_key_exists($segment, $data)) return;

		if ($keys)
		{
			$this->sanitizeByKey($keys, $data[$segment], $ruleset);
		}
		else
		{
			$this->runRuleset($data, $segment, $ruleset);
		}
	}

	/**
	 * Run a ruleset over each item in the given array.
	 *
	 * @param  string  $keys
	 * @param  array  $data
	 * @param  array  $ruleset
	 * @return void
	 */
	protected function runArrayRuleset($keys, array &$data, array $ruleset)
	{
		foreach ($data as $index => &$item)
		{
			if (empty($keys))
			{
				$this->runRuleset($data, $index, $ruleset);
			}
			elseif (is_array($item))
			{
				$this->sanitizeByKey($keys, $item, $ruleset);
			}
		}
	}

	/**
	 * Run a ruleset over the key in the given data array.
	 *
	 * @param  array  $data
	 * @param  mixed  $key
	 * @param  array  $ruleset
	 * @return void
	 */
	protected function runRuleset(array &$data, $key, array $ruleset)
	{
		// If the developer has provided a 'nullable' rule, we will
		// assume that they only want to run sanitization on this
		// field if it is not null. We will check for that here.
		if (is_null($data[$key]) && in_array('nullable', $ruleset))
		{
			return;
		}

		foreach ($ruleset as $rule)
		{
			$this->runRule($data, $key, $rule);
		}
	}

	/**
	 * Run a rule on the given value.
	 *
	 * @param  array  $data
	 * @param  mixed  $key
	 * @param  string  $rule
	 * @return void
	 */
	protected function runRule(array &$data, $key, $rule)
	{
		list($rule, $parameters) = $this->parseStringRule($rule);

		array_unshift($parameters, $data[$key]);

		$sanitizer = $this->getSanitizer($rule);

		$data[$key] = call_user_func_array($sanitizer, $parameters);
	}

	/**
	 * Parse a string based rule.
	 *
	 * @param  string  $rule
	 * @return array
	 */
	protected function parseStringRule($rule)
	{
		$parameters = [];

		if (strpos($rule, ':') !== false)
		{
			list($rule, $parameters) = explode(':', $rule, 2);

			$parameters = str_getcsv($parameters);
		}

		return [trim($rule), $parameters];
	}

	/**
	 * Get a sanitizer by its key.
	 *
	 * @param  string  $key
	 * @return callable
	 */
	protected function getSanitizer($key)
	{
		if (isset($this->extensions[$key]))
		{
			return $this->extensions[$key];
		}

		if (method_exists($this, $method = "{$key}Sanitizer"))
		{
			return [$this, $method];
		}

		if (is_callable($key)) return $key;

		return $this->resolveSanitizer($key);
	}

	/**
	 * Resolve a sanitizer from a class and method pair.
	 *
	 * @param  string  $key
	 * @return callable
	 */
	protected function resolveSanitizer($key)
	{
		if (strpos($key, '@') === false) $key .= '@sanitize';

		list($class, $method) = explode('@', $key);

		$instance = $this->container ? $this->container[$class] : new $class;

		return [$instance, $method];
	}

	/**
	 * Remove all non-alphabetic characters.
	 *
	 * @param  mixed  $value
	 * @return string
	 */
	protected function alphaSanitizer($value)
	{
		$value = $this->stringSanitizer($value);

		return preg_replace('/[\pL\pM]/u', '', $value);
	}

	/**
	 * Remove characters that are not alpha-numeric, dashes, and underscores.
	 *
	 * @param  mixed   $value
	 * @return string
	 */
	protected function alphaDashSanitizer($value)
	{
		$value = $this->stringSanitizer($value);

		return preg_match('/^[\pL\pM\pN_-]+$/u', '', $value);
	}

	/**
	 * Remove all non-alpha-numeric characters.
	 *
	 * @param  mixed   $value
	 * @return string
	 */
	protected function alphaNumSanitizer($value)
	{
		$value = $this->stringSanitizer($value);

		return preg_replace('/^[\pL\pM\pN]+$/u', '', $value);
	}

	/**
	 * Convert a value to an array.
	 *
	 * @param  mixed  $value
	 * @return array
	 */
	protected function arraySanitizer($value)
	{
		return (array) $value;
	}

	/**
	 * Convert a value to a boolean.
	 *
	 * @param  mixed  $value
	 * @return boolean
	 */
	protected function booleanSanitizer($value)
	{
		return (bool) $value;
	}

	/**
	 * Remove all non-numeric characters.
	 *
	 * @param  mixed  $value
	 * @return string
	 */
	protected function numericSanitizer($value)
	{
		$value = $this->stringSanitizer($value);

		return preg_replace('~[^\d]~', '', $value);
	}

	/**
	 * Convert a value to a number.
	 *
	 * @param  mixed  $value
	 * @return int
	 */
	protected function numberSanitizer($value)
	{
		return (int) $this->numericSanitizer($value);
	}

	/**
	 * Convert a value to a float.
	 *
	 * @param  mixed  $value
	 * @return float
	 */
	protected function floatSanitizer($value)
	{
		$value = $this->stringSanitizer($value);

		return (float) preg_replace('~[^\d.]~', '', $value);
	}

	/**
	 * Convert the given value to a string.
	 *
	 * @param  mixed  $value
	 * @return string
	 */
	protected function stringSanitizer($value)
	{
		try
		{
			if (is_array($value)) return implode(', ', $value);

			return (string) $value;
		}
		catch (Exception $e)
		{
			return '';
		}
	}

	/**
	 * Trim off whitespace from both ends of a given string.
	 *
	 * @param  mixed  $value
	 * @return string
	 */
	protected function trimSanitizer($value)
	{
		if ( ! is_string($value)) return $value;

		return trim($value);
	}

}

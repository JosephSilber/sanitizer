<?php namespace Silber\Sanitizer;

use ArrayAccess;

class FormSanitizer {

	/**
	 * The sanitizer class name.
	 *
	 * @var string
	 */
	protected $sanitizer = 'Silber\Sanitizer\Sanitizer';

	/**
	 * The sanitization rules.
	 *
	 * @var array
	 */
	protected $rules = [];

	/**
	 * The container instance.
	 *
	 * @var ArrayAccess
	 */
	static protected $container;

	/**
	 * Sanitize the given data.
	 *
	 * @param  array  $data
	 * @return array
	 */
	public function sanitize(array $data)
	{
		return $this->newSanitizer()->sanitize($data, $this->rules);
	}

	/**
	 * Sets the container instance.
	 *
	 * @param  ArrayAccess  $container
	 * @return void
	 */
	static public function setContainer(ArrayAccess $container)
	{
		static::$container = $container;
	}

	/**
	 * Create a new sanitizer instance.
	 *
	 * @return Silber\Sanitizer\Sanitizer
	 */
	protected function newSanitizer()
	{
		return new $this->sanitizer(static::$container);
	}

}

<?php namespace Silber\Sanitizer\Laravel;

use Silber\Sanitizer\Sanitizer;
use Silber\Sanitizer\FormSanitizer;
use Illuminate\Support\ServiceProvider;

class SanitizerServiceProvider extends ServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->bindShared('sanitizer', function()
		{
			return new Sanitizer($this->app);
		});

		$this->app->alias('sanitizer', 'Silber\Sanitizer\Sanitizer');

		FormSanitizer::setContainer($this->app);
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return [
			'sanitizer',
			'Silber\Sanitizer\Sanitizer',
			'Silber\Sanitizer\FormSanitizer',
		];
	}

}

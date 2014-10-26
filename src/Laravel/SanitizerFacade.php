<?php namespace Silber\Sanitizer\Laravel;

use Illuminate\Support\Facades\Facade;

class SanitizerFacade extends Facade {

	protected static function getFacadeAccessor()
	{
		return 'Silber\Sanitizer\Sanitizer';
	}

}

Checkdomain TOML Parser [![Build Status](https://travis-ci.org/checkdomain/toml.png?branch=master)](https://travis-ci.org/checkdomain/toml)
====

Checkdomain TOML Parser is a parser for [TOML](https://github.com/mojombo/toml) files.

Installation
====
PHP TOML Parser is available via Composer or direct download from github.com. You can
find some package infos at [packagist.org](https://packagist.org/packages/checkdomain/php-toml).

Composer
----
Get [Composer](http://getcomposer.org/) and add the following to your **composer.json**:
    
````json
"require": {
    // ...
    "checkdomain/php-toml": "*"
}
````
    
Download
----
If you want to download this (I strongly suggest to use composer) you need to include
the files directly or use any sort of [PSR-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md) 
compatible autoloading mechanism.

Usage
====
Once installed Checkdomain TOML Parser is easy to use:

````php
<?php

$toml = new \Checkdomain\Toml('/path/to/toml/file.toml');
    
// Path accessor
$toml->get('database.server');
	
// Array accessor
$toml->values['database']['server']
````	
You can also use the instance multiple times by calling the `parse` method:

````php
<?php

$toml = new \Checkdomain\Toml;
$result = $toml->parse('/path/to/toml/file.toml');
    
// You can access the returned array like this
$result['database']['server'];	
````

Contributions
====
You are free to contribute to this project. Please provide test if neccessary for your
changes. All tests can be found in the `/tests` directory.

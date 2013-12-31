# Check php code syntax

[![Build Status](https://secure.travis-ci.org/Gemorroj/Syntax.png?branch=master)](https://travis-ci.org/Gemorroj/Syntax)

Requirements:

- PHP >= 5.3


Example:
```php
<?php
use Syntax\Php;

$syntax = new Php();

$resultCheck = $syntax->check('<?php echo 1; ?>');
print_r($resultCheck);

$resultCheckFile = $syntax->checkFile('example.php');
print_r($resultCheckFile);
```
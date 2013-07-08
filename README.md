# Check php code syntax

[![Build Status](https://secure.travis-ci.org/Gemorroj/syntax.png?branch=master)](https://travis-ci.org/Gemorroj/syntax)

Requirements:

- PHP >= 5.2


Example:
```php
<?php
require_once 'Syntax.php';

$syntax = new Syntax();

$resultCheck = $syntax->check('<?php echo 1; ?>');
print_r($resultCheck);

$resultCheckFile = $syntax->checkFile('example.php');
print_r($resultCheckFile);
```
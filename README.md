# Check php code syntax

[![Continuous Integration](https://github.com/Gemorroj/Syntax/workflows/Continuous%20Integration/badge.svg?branch=master)](https://github.com/Gemorroj/Syntax/actions?query=workflow%3A%22Continuous+Integration%22)


### Example:
```php
<?php
use Syntax\Php;

$syntax = new Php();

$resultCheck = $syntax->check('<?php echo 1; ?>');
print_r($resultCheck);

$resultCheckFile = $syntax->checkFile('example.php');
print_r($resultCheckFile);
```

### Requirements:

- PHP >= 7.3

### Installation:
```bash
composer require gemorroj/syntax
```

# Check php code syntax

[![Build Status](https://secure.travis-ci.org/Gemorroj/Syntax.png?branch=master)](https://travis-ci.org/Gemorroj/Syntax)


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

- PHP >= 5.3

### Installation:

- Add to composer.json:

```json
{
    "require": {
        "gemorroj/syntax": "dev-master"
    }
}
```
- Install project:

```bash
$ php composer.phar update gemorroj/syntax
```

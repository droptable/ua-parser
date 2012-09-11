UA-Parser for PHP 5.4+ based on https://github.com/tobie/ua-parser

Example:
```php
<?php

require 'src/parser.php';

$parser = new ua_parser\Parser;
$client = $parser->parse('User-Agent string here');

// if you need it as JSON:
$json = $client->toJSON();

// or:
$json = json_encode($client);

// if you need it as array:
$array = $client->toArray();
print $array['device']['name'];

// otherwise use it as an object:
print $client->device->name;

?>
```

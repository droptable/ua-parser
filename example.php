<?php

require 'src/parser.php';
use ua_parser\Parser;

$uap = new Parser;
$res = $uap->parse('Mozilla/5.0 (iPhone; CPU iPhone OS 5_1_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Version/5.1 Mobile/9B206 Safari/7534.48.3');

print_r($res->toArray());

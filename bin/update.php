#!/usr/bin/php -q
<?php

namespace ua_parser;

if (PHP_SAPI !== 'cli')
  exit('updating the regexes.yaml file is slow and not recommended at server-side');

const URL = 'https://raw.github.com/tobie/ua-parser/master/regexes.yaml';

print 'downloading yaml file from ' . URL . PHP_EOL;

if (!($data = file_get_contents(URL)))
  exit('unable to load data from url: ' . URL);

$path = realpath(__DIR__ . '/../lib') . '/regexes.yaml';

// make backup and write data

if (is_file($path . '.bkp')) {
  print "removing old backup file\n";
  unlink($path . '.bkp');
}

if (is_file($path)) {
  print "creating new backup file ($path.bkp)\n";
  rename($path, $path . '.bkp');
}

print "saving data into $path\n";
file_put_contents($path, $data);

print "\ndone!\n";

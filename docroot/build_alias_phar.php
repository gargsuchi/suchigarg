<?php

/**
 * @file
 * Builds a test phar file.
 */

if (PHP_SAPI !== 'cli') {
  return;
}
// Create a phar to run from CLI.
$phar = new \Phar(__DIR__ . '/alias_phar.phar');
$phar->setAlias('alias_phar.phar');
$phar->buildFromDirectory(__DIR__ . '/alias_phar_builder');

// pointing main file which requires all classes
$phar->setDefaultStub('index.php', '/index.php');

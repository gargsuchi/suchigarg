<?php

/**
 * @file
 * Tests phar protection.
 */

/**
 * Returns test text.
 *
 * @return string
 *   Test test.
 */
function phar_test_include() {
  return "Included a file inside the phar using 'phar://alias_phar.phar/test/file_to_include.php'";
}

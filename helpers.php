<?php declare(strict_types=1);

/**
 * Output a string.
 *
 * @param string $message
 *   The message.
 */
function output(string $message = ''): void {
  if ($message !== '') {
    print $message;
  }
  print PHP_EOL;
}

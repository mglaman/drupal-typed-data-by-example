<?php declare(strict_types=1);

/**
 * @file
 * Allows us to boostrap the Drupal kernel.
 *
 * This is used to run parts of the Typed Data API which require stateful
 * aspects of Drupal for our stateless examples.
 */

use mglaman\DrupalMemoryKernel\MemoryKernelFactory;

$kernel = MemoryKernelFactory::get(
  environment: 'testing',
  autoloader: require __DIR__ . '/vendor/autoload.php',
  modules: [
    'system',
    'serialization',
  ],
);

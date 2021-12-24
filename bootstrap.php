<?php declare(strict_types=1);

/**
 * @file
 *
 * This is used to run parts of the Typed Data API which require stateful
 * aspects of Drupal for our stateless examples.
 */

use Drupal\Core\Database\Database;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    return;
}
$autoloader = require __DIR__ . '/vendor/autoload.php';

$drupalRoot = __DIR__ . '/vendor/drupal';
Database::addConnectionInfo('default', 'default', [
  'driver' => 'sqlite',
  'database' => ':memory:',
]);

chdir($drupalRoot);

// Initialize empty settings.
Settings::initialize($drupalRoot, __DIR__, $autoloader);

// Cause the static \Drupal class to become populated with a container.
DrupalKernel::bootEnvironment($drupalRoot);
$kernel = new DrupalKernel('prod', $autoloader, TRUE, $drupalRoot);
$kernel->setSitePath(__DIR__);
$kernel->boot();

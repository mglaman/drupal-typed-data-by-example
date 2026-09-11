<?php

declare(strict_types=1);

namespace TypedDataByExample;

use Composer\Autoload\ClassLoader;
use Drupal\Core\Config\MemoryStorage;
use Drupal\Core\Database\Database;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;

/**
 * A Drupal kernel backed by an in-memory SQLite database.
 *
 * The examples are stateless, but parts of the Typed Data API need a booted
 * container. This kernel boots Drupal without a site directory, a database
 * file, or a cached container.
 */
final class MemoryKernel extends DrupalKernel {

  /**
   * {@inheritdoc}
   */
  protected $sitePath = __DIR__;

  /**
   * Boots a kernel with the given modules installed.
   *
   * @param string $environment
   *   The kernel environment.
   * @param \Composer\Autoload\ClassLoader $autoloader
   *   The Composer class loader.
   * @param array<string, int> $modules
   *   Module names mapped to their weight, as stored in core.extension.
   */
  public static function create(string $environment, ClassLoader $autoloader, array $modules): self {
    $modules['system'] = 0;
    $modules['sqlite'] = 0;
    Database::addConnectionInfo('default', 'default', [
      'driver' => 'sqlite',
      'database' => ':memory:',
    ]);

    new Settings([
      'bootstrap_config_storage' => static function () use ($modules) {
        $storage = new MemoryStorage();
        $storage->write('core.extension', [
          'module' => $modules,
          'profile' => 'minimal',
        ]);
        return $storage;
      },
      'cache' => [
        'default' => 'cache.backend.memory',
      ],
    ]);

    $kernel = new self($environment, $autoloader, FALSE);
    chdir($kernel->getAppRoot());
    self::bootEnvironment();
    $kernel->boot();
    return $kernel;
  }

  /**
   * {@inheritdoc}
   */
  public function getCachedContainerDefinition() {
    return NULL;
  }

}

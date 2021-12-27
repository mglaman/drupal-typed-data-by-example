<?php
declare(strict_types=1);

/**
 * @file
 * An example of using the Typed Data API for timestamps.
 *
 * @todo Again, the constraints are on the FieldItem and not DataType.
 */
require __DIR__ . '/../vendor/autoload.php';

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\Plugin\DataType\IntegerData;
use Drupal\Core\TypedData\Plugin\DataType\Timestamp;

$typed_data_manager = \Drupal::typedDataManager();

$definition = DataDefinition::create('timestamp')
  ->addConstraint('Range', [
    'min' => -2147483648,
    'max' => 2147483648,
  ]);

$timestamp = $typed_data_manager->create($definition, 1640639271);

assert(count($timestamp->validate()) === 0);
assert($timestamp->getValue() === 1640639271);
assert($timestamp instanceof Timestamp);
assert($timestamp->getCastedValue() === 1640639271);

output($timestamp->getDateTime()->format(\DateTimeInterface::ATOM));


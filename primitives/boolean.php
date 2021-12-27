<?php declare(strict_types=1);

/**
 * @file
 * An example of using the Typed Data API for booleans.
 */
require __DIR__ . '/../vendor/autoload.php';

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\Plugin\DataType\BooleanData;

$typed_data_manager = \Drupal::typedDataManager();

$definition = DataDefinition::create('boolean');

$bool_true = $typed_data_manager->create($definition, TRUE);

assert(count($bool_true->validate()) === 0);
assert($bool_true->getValue() === TRUE);
assert($bool_true instanceof BooleanData);
assert($bool_true->getCastedValue() === TRUE);

// Casting a non-empty string to bool is TRUE.
$bool_string = $typed_data_manager->create($definition, 'false');
assert($bool_string instanceof BooleanData);
assert($bool_string->getValue() === 'false');
assert($bool_string->getCastedValue() === TRUE);

// But casting 0 to bool is FALSE, string or integer.
// This is just normal PHP behavior, but wrapped in the Typed Data API.
$bool_as_int = $typed_data_manager->create($definition, '0');
assert($bool_as_int instanceof BooleanData);
assert($bool_as_int->getCastedValue() === FALSE);

<?php
declare(strict_types=1);

/**
 * @file
 * An example of using the Typed Data API for floats.
 */
require __DIR__ . '/../vendor/autoload.php';

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\Plugin\DataType\FloatData;

$typed_data_manager = \Drupal::typedDataManager();

$definition = DataDefinition::create('float');

$float = $typed_data_manager->create($definition, 3.1415926535);

assert(count($float->validate()) === 0);
assert($float->getValue() === 3.1415926535);
assert($float instanceof FloatData);
assert($float->getCastedValue() === 3.1415926535);

// When data comes back from the database via PDO, it's always a string.
// That's where getCastedValue is useful.
// Honestly, I wish `getValue` defaulted to `getCastedValue` for primitives.
$float_as_string = $typed_data_manager->create($definition, '3.1415926535');

assert(count($float_as_string->validate()) === 0);
assert($float_as_string->getValue() === '3.1415926535');
assert($float_as_string instanceof FloatData);
assert($float_as_string->getCastedValue() === 3.1415926535);

$float_with_integer_value = $typed_data_manager->create($definition, 10);
assert(count($float_with_integer_value->validate()) === 0);
assert($float_with_integer_value instanceof FloatData);
// The integer `10` passed in is cast to float and becomes 10.0.
assert($float_with_integer_value->getCastedValue() !== 10);
assert($float_with_integer_value->getCastedValue() === 10.0);

$not_a_number = $typed_data_manager->create($definition, 'ope');
$violations = $not_a_number->validate();
assert(count($violations) === 1);

output('Reason');
output((string) $violations->get(0));

<?php
declare(strict_types=1);

/**
 * @file
 *
 * An example of using the Typed Data API for integers
 *
 * @todo Drupal does not expose `\Symfony\Component\Validator\Constraints\Positive` for unsigned integers.
 */
require __DIR__.'/../vendor/autoload.php';

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\Plugin\DataType\IntegerData;

$typed_data_manager = \Drupal::typedDataManager();

// An integer
$definition = DataDefinition::create('integer');

$integer = $typed_data_manager->create($definition, 10);

assert(count($integer->validate()) === 0);
assert($integer->getValue() === 10);
assert($integer instanceof IntegerData);
assert($integer->getCastedValue() === 10);

// When data comes back from the database via PDO, it's always a string.
// That's where getCastedValue is useful.
// Honestly, I wish `getValue` defaulted to `getCastedValue` for primitives.
$integer_as_string = $typed_data_manager->create($definition, '10');

assert(count($integer_as_string->validate()) === 0);
assert($integer_as_string->getValue() === '10');
assert($integer_as_string instanceof IntegerData);
assert($integer_as_string->getCastedValue() === 10);

$not_an_integer = $typed_data_manager->create($definition, 'ope');
$violations = $not_an_integer->validate();
assert(count($violations) === 1);

output('Reason');
output((string) $violations->get(0));

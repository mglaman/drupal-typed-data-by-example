<?php declare(strict_types=1);

/**
 * @file
 * This file contains basic usage of Typed Data with strings.
 */
require __DIR__ . '/../vendor/autoload.php';

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\Plugin\DataType\StringData;

$typed_data_manager = \Drupal::typedDataManager();

// Let's create a data definition representing a string.
// By using a data definition, we can apply metadata that describes our string
// to describe its schema and validation requirements.
$string_definition = DataDefinition::create('string')
  // The string has a maximum length of 25.
  ->addConstraint('Length', [
    'max' => 10,
    'maxMessage' => 'Cannot be longer than 10',
  ]);

// Now, we'll use the typed data manager to create a typed data value.
// It requires the data definition and the value.
$value = $typed_data_manager->create($string_definition, 'foobar');
assert($value instanceof StringData);

// We can use the `::validate` method to check our applied constraints.
$violations = $value->validate();
assert(count($violations) === 0);

$invalid_value = $typed_data_manager->create($string_definition, 'some really long string');
assert($invalid_value instanceof StringData);

$violations = $invalid_value->validate();
assert(count($violations) === 1);

output((string) $violations->get(0));

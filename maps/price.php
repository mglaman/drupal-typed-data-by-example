<?php declare(strict_types=1);

/**
 * @file
 * This file contains an example of using a map definition for a price object.
 */
require __DIR__ . '/../vendor/autoload.php';

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\TypedData\Plugin\DataType\Map;

$typed_data_manager = \Drupal::typedDataManager();

// Let's create a data definition that represents a complex value.
// Take a price. It has a number and a currency, two properties
// - number: float, which may have constraints to be > 0.00.
// -currency: needs to be a valid (or allowed) currency.
$price_definition = MapDataDefinition::create()
  // There is an interesting Drupal problem, here. Drupal has a Decimal field
  // that uses a `string` data property for the value. It's schema for the
  // database is "numeric". In MySQL this maps to DECIMAL but PGSQL and SQLite
  // it is the NUMERIC. This is treated differently than floats.
  //
  // Decimal fields handle precision, but Float fields do not.
  // Remember: The field system deals with databases and storage, typed data
  // does not.
  // @todo It'd be neat to dig up the history of the DecimalItem field type
  //   why wasn't a decimal data type created as well?
  ->setPropertyDefinition(
    'number',
    DataDefinition::create('string')
      ->setRequired(TRUE)
  )
  ->setPropertyDefinition(
    'currency_code',
    DataDefinition::create('string')
      ->setRequired(TRUE)
      ->addConstraint('AllowedValues', ['USD', 'CAD'])
  );

// Now, we'll use the typed data manager to create a typed data value.
$value = $typed_data_manager->create($price_definition, [
  'number' => '10.99',
  'currency_code' => 'USD',
]);
assert($value instanceof Map);

$violations = $value->validate();
assert(count($violations) === 0);

assert($value->getValue() === ['number' => '10.99', 'currency_code' => 'USD']);

$invalid_currency = $typed_data_manager->create($price_definition, [
  'number' => '10.99',
  'currency_code' => 'RSD',
]);
$violations = $invalid_currency->validate();
assert(count($violations) === 1);
assert((string) $violations->get(0)->getMessage() === 'The value you selected is not a valid choice.');
output((string) $violations->get(0));

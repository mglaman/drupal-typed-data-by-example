<?php declare(strict_types=1);

/**
 * @file
 * This file contains validation quirks with maps.
 */
require __DIR__ . '/../vendor/autoload.php';

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;

$typed_data_manager = \Drupal::typedDataManager();

// Maps are made up of data definition properties. These properties can be
// required or optional. This may be misleading when it comes to validating
// a map definition that has some required properties.
//
// Here is an example shape which has the following properties:
// - foo: required
// - bar: optional
// - baz: required.
//
// We will walk through some validation scenarios for this data definition.
$map = MapDataDefinition::create()
  ->setPropertyDefinition(
    'foo',
    DataDefinition::create('string')->setRequired(TRUE)
  )
  ->setPropertyDefinition(
    'bar',
    DataDefinition::create('string')
  )
  ->setPropertyDefinition(
    'baz',
    DataDefinition::create('string')->setRequired(TRUE)
  );

// This passes. Required simply means "exists", not that it cannot be an empty
// value. That is because flagging a data definition as required applies the
// NotNull constraint – which only checks for `null`, not empty.
// @see \Drupal\Core\Validation\Plugin\Validation\Constraint\NotNullConstraintValidator::validate
$example1 = $typed_data_manager->create($map, [
  'foo' => '',
  'bar' => '',
  'baz' => '',
]);
assert(count($example1->validate()) === 0);

// Repeat the example, but without the required `baz` property.
$example2 = $typed_data_manager->create($map, [
  'foo' => '',
  'bar' => '',
]);
$violations = $example2->validate();
assert(count($violations) === 1);
assert((string) $violations->get(0)->getMessage() === 'This value should not be null.');
output((string) $violations->get(0));

// But what if we passed _nothing_, an empty array?
$example3 = $typed_data_manager->create($map, []);
$violations = $example3->validate();

// Wait, what?!?!! ZERO!?!? Yep.
assert(count($violations) === 0);

// Let's try making the root definition required.
$map->setRequired(TRUE);

// I have opened a Drupal core issue to try and fix this. Map definitions are
// only properly validated if they have a value OR the root definition is
// required. In my opinion, the validator should iterate over each map
// definition property versus the data (or lack of) in the value.
//
// @see https://www.drupal.org/project/drupal/issues/3256536
$violations = $example3->validate();
assert(count($violations) === 1);

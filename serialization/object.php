<?php declare(strict_types=1);

/**
 * @file
 * Showcases Drupal's serialization of a generic object.
 */
require __DIR__ . '/../vendor/autoload.php';

use Drupal\Component\Serialization\Json;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\TypedData\Plugin\DataType\Map;
use Symfony\Component\Serializer\Serializer;

$typed_data_manager = \Drupal::typedDataManager();

$definition = MapDataDefinition::create()
  ->setPropertyDefinition('id', DataDefinition::create('integer'))
  ->setPropertyDefinition('name', DataDefinition::create('string'))
  ->setPropertyDefinition('email', DataDefinition::create('email'))
  ->setPropertyDefinition('verified', DataDefinition::create('boolean'));

// Values are all wrapped as strings to mimic load from database.
$user_data = $typed_data_manager->create($definition, [
  'id' => '12345678',
  'name' => 'foobar',
  'email' => 'foobar@example.com',
  'verified' => '0',
]);

// The serializer is provided by the Serialization module.
$serializer = \Drupal::getContainer()->get('serializer');
assert($serializer instanceof Serializer);

// Show the data typed normalized into a regular array with proper types.
$normalized = $serializer->normalize($user_data, 'json');
assert($normalized === [
  'id' => 12345678,
  'name' => 'foobar',
  'email' => 'foobar@example.com',
  'verified' => FALSE,
]);

// Show the data serialized into a JSON, with proper types.
$serialized = $serializer->serialize($user_data, 'json');
assert($serialized === '{"id":12345678,"name":"foobar","email":"foobar@example.com","verified":false}');

// This is the biggest use case: receiving request data and transforming the
// payload into a data type for validation.
$user_data = $typed_data_manager->create($definition, Json::decode($serialized));
assert($user_data instanceof Map);
assert($user_data->get('name')->getValue() === 'foobar');
assert(count($user_data->validate()) === 0);

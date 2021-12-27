<?php declare(strict_types=1);

/**
 * @file
 * Showcases Drupal's serialization of timestamp data types.
 */
require __DIR__ . '/../vendor/autoload.php';

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\Plugin\DataType\Timestamp;
use Symfony\Component\Serializer\Serializer;

$typed_data_manager = \Drupal::typedDataManager();

$definition = DataDefinition::create('timestamp');

$timestamp = $typed_data_manager->create($definition, 1640639271);
assert($timestamp instanceof Timestamp);

$serializer = \Drupal::getContainer()->get('serializer');
assert($serializer instanceof Serializer);

// Passing to the serializer transforms the value to its ::getCastedValue value.
$normalized_json = $serializer->normalize($timestamp, 'json');
assert($normalized_json === '2021-12-27T21:07:51+00:00', $normalized_json);
$serialized_json = $serializer->serialize($timestamp, 'json');
assert($serialized_json === '"2021-12-27T21:07:51+00:00"');

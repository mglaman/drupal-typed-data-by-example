<?php

declare(strict_types=1);

/**
 * @file
 * Translates JSON Schema into typed data definitions.
 *
 * This is the inverse of json_schema.php. A schema describes the shape of a
 * payload. Turning it into data definitions lets Drupal wrap and validate the
 * payload with the Typed Data API, with no hand-written definitions.
 */
require __DIR__ . '/../vendor/autoload.php';

use Drupal\Component\Serialization\Json;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\TypedData\Plugin\DataType\Map;

$typed_data_manager = \Drupal::typedDataManager();

/**
 * Picks the typed data type for a JSON Schema type and format.
 *
 * Formats narrow a string to a more specific data type. Drupal's timestamp
 * data type normalizes to a date-time string, but it stores an integer, so a
 * date-time string maps to the ISO 8601 data type instead.
 *
 * @param string $type
 *   The JSON Schema type keyword.
 * @param string|null $format
 *   The JSON Schema format keyword, if any.
 *
 * @return string
 *   The typed data plugin ID.
 */
function data_type_for_schema(string $type, ?string $format): string {
  return match ($type) {
    'integer' => 'integer',
    'number' => 'float',
    'boolean' => 'boolean',
    'string' => match ($format) {
      'email' => 'email',
      'uri' => 'uri',
      'number' => 'decimal',
      'date-time', 'date' => 'datetime_iso8601',
      'duration' => 'duration_iso8601',
      default => 'string',
    },
    default => throw new \InvalidArgumentException("Unsupported JSON Schema type: $type"),
  };
}

/**
 * Translates JSON Schema validation keywords into typed data constraints.
 *
 * @param array $schema
 *   The JSON Schema.
 * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
 *   The definition to add constraints to.
 */
function add_constraints_from_schema(array $schema, DataDefinitionInterface $definition): void {
  if (isset($schema['enum'])) {
    $definition->addConstraint('AllowedValues', $schema['enum']);
  }
  $length = array_intersect_key($schema, ['minLength' => 1, 'maxLength' => 1]);
  if ($length !== []) {
    $definition->addConstraint('Length', [
      'min' => $schema['minLength'] ?? NULL,
      'max' => $schema['maxLength'] ?? NULL,
    ]);
  }
  $range = array_intersect_key($schema, ['minimum' => 1, 'maximum' => 1]);
  if ($range !== []) {
    $definition->addConstraint('Range', [
      'min' => $schema['minimum'] ?? NULL,
      'max' => $schema['maximum'] ?? NULL,
    ]);
  }
  if (isset($schema['pattern'])) {
    $definition->addConstraint('Regex', ['pattern' => '/' . $schema['pattern'] . '/']);
  }
}

/**
 * Translates a JSON Schema into a data definition.
 *
 * Objects become maps and arrays become lists. A oneOf that pairs a schema
 * with null is unwrapped, since optional typed data already allows null.
 *
 * @param array $schema
 *   The JSON Schema.
 *
 * @return \Drupal\Core\TypedData\DataDefinitionInterface
 *   The data definition.
 */
function from_json_schema(array $schema): DataDefinitionInterface {
  if (isset($schema['oneOf'])) {
    $branches = array_filter($schema['oneOf'], static fn (array $branch) => ($branch['type'] ?? NULL) !== 'null');
    if (count($branches) !== 1) {
      throw new \InvalidArgumentException('Only nullable oneOf schemas are supported.');
    }
    return from_json_schema(current($branches));
  }

  if ($schema['type'] === 'object') {
    $definition = MapDataDefinition::create();
    foreach ($schema['properties'] ?? [] as $name => $property_schema) {
      $property_definition = from_json_schema($property_schema);
      $property_definition->setRequired(in_array($name, $schema['required'] ?? [], TRUE));
      $definition->setPropertyDefinition($name, $property_definition);
    }
    return $definition;
  }

  if ($schema['type'] === 'array') {
    return ListDataDefinition::createFromDataType('list')
      ->setItemDefinition(from_json_schema($schema['items']));
  }

  $definition = DataDefinition::create(data_type_for_schema($schema['type'], $schema['format'] ?? NULL));
  add_constraints_from_schema($schema, $definition);
  return $definition;
}

// A schema for a product, as an API might publish it.
$schema = Json::decode(<<<'JSON'
{
  "type": "object",
  "properties": {
    "id": {"type": "integer", "minimum": 1},
    "title": {"type": "string", "maxLength": 255},
    "sku": {"type": "string", "pattern": "^[A-Z]{3}-[0-9]{4}$"},
    "price": {
      "type": "object",
      "properties": {
        "number": {"type": "string", "format": "number"},
        "currency_code": {"type": "string", "enum": ["USD", "CAD"]}
      },
      "required": ["number", "currency_code"]
    },
    "tags": {"type": "array", "items": {"type": "string", "minLength": 2}},
    "contact": {"oneOf": [{"type": "string", "format": "email"}, {"type": "null"}]}
  },
  "required": ["id", "title", "price"]
}
JSON);

$definition = from_json_schema($schema);
assert($definition instanceof MapDataDefinition);
assert($definition->getPropertyDefinition('id')->getDataType() === 'integer');
assert($definition->getPropertyDefinition('id')->isRequired() === TRUE);
assert($definition->getPropertyDefinition('id')->getConstraint('Range') === ['min' => 1, 'max' => NULL]);
assert($definition->getPropertyDefinition('contact')->getDataType() === 'email');
assert($definition->getPropertyDefinition('contact')->isRequired() === FALSE);
$tag_definition = $definition->getPropertyDefinition('tags')->getItemDefinition();
assert($tag_definition->getDataType() === 'string');
assert($tag_definition->getConstraint('Length') === ['min' => 2, 'max' => NULL]);
$currency_definition = $definition->getPropertyDefinition('price')->getPropertyDefinition('currency_code');
assert($currency_definition->getConstraint('AllowedValues') === ['USD', 'CAD']);

// Wrap a payload that satisfies the schema.
$valid = $typed_data_manager->create($definition, Json::decode(<<<'JSON'
{
  "id": 42,
  "title": "Widget",
  "sku": "WDG-0001",
  "price": {"number": "10.99", "currency_code": "USD"},
  "tags": ["hardware", "featured"],
  "contact": "sales@example.com"
}
JSON));
assert($valid instanceof Map);
assert(count($valid->validate()) === 0);
assert($valid->get('price')->get('number')->getValue() === '10.99');

// Now a payload that breaks the schema in several places. Every failure
// surfaces as a violation with a property path back into the payload.
$invalid = $typed_data_manager->create($definition, Json::decode(<<<'JSON'
{
  "id": 0,
  "sku": "widget",
  "price": {"number": "10.99", "currency_code": "EUR"},
  "tags": ["x"],
  "contact": "not-an-email"
}
JSON));
$violations = $invalid->validate();
$messages = [];
foreach ($violations as $violation) {
  $messages[$violation->getPropertyPath()] = (string) $violation->getMessage();
}
assert($messages === [
  'id' => 'This value should be <em class="placeholder">1</em> or more.',
  'title' => 'This value should not be null.',
  'sku' => 'This value is not valid.',
  'price.currency_code' => 'The value you selected is not a valid choice.',
  'tags.0' => 'This value is too short. It should have <em class="placeholder">2</em> characters or more.',
  'contact' => 'This value is not a valid email address.',
]);
foreach ($messages as $path => $message) {
  output("$path: $message");
}

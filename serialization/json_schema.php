<?php

declare(strict_types=1);

/**
 * @file
 * Translates typed data definitions into JSON Schema.
 *
 * Drupal 11 added a JsonSchema attribute that normalizers and data types use
 * to describe the shape of their normalized output. The serializer exposes it
 * through a `json_schema` format. Core only covers primitives, so this example
 * shows what you get for free and then fills the gap for maps and lists.
 *
 * @see https://www.drupal.org/node/3424710
 */
require __DIR__ . '/../vendor/autoload.php';

use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\serialization\Serializer\Serializer;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Range;

$typed_data_manager = \Drupal::typedDataManager();

// Drupal's serializer service wraps Symfony's and adds getJsonSchema().
$serializer = \Drupal::getContainer()->get('serializer');
assert($serializer instanceof Serializer);

// Primitive data types declare their schema with the JsonSchema attribute on
// getCastedValue(). The serializer reads the attribute through reflection.
$name = $typed_data_manager->create(DataDefinition::create('string')->setRequired(TRUE));
assert($serializer->getJsonSchema($name, []) === ['type' => 'string']);

// An optional value is allowed to be null, so the schema becomes a oneOf.
$email = $typed_data_manager->create(DataDefinition::create('email'));
assert($serializer->getJsonSchema($email, []) === [
  'oneOf' => [
    ['type' => 'string', 'format' => 'email'],
    ['type' => 'null'],
  ],
]);

// Complex data and lists have no schema support in core. Asking for one
// returns a placeholder with a $comment explaining why.
$empty_map = $typed_data_manager->create(MapDataDefinition::create());
$map_schema = $serializer->getJsonSchema($empty_map, []);
assert(array_keys($map_schema) === ['$comment']);
assert(str_starts_with($map_schema['$comment'], 'No schema is defined for property of type Drupal\Core\TypedData\Plugin\DataType\Map.'));

/**
 * Translates validation constraints into JSON Schema keywords.
 *
 * Constraints live on the typed data object, not the definition, so the
 * definition must be instantiated before its constraints can be read.
 *
 * @param \Drupal\Core\TypedData\TypedDataInterface $data
 *   The typed data.
 *
 * @return array
 *   JSON Schema keywords.
 */
function constraints_to_json_schema(TypedDataInterface $data): array {
  $keywords = [];
  foreach ($data->getConstraints() as $constraint) {
    if ($constraint instanceof Choice) {
      $keywords['enum'] = array_values($constraint->choices);
    }
    if ($constraint instanceof Length) {
      $keywords += array_filter([
        'minLength' => $constraint->min,
        'maxLength' => $constraint->max,
      ], is_int(...));
    }
    if ($constraint instanceof Range) {
      $keywords += array_filter([
        'minimum' => $constraint->min,
        'maximum' => $constraint->max,
      ], is_numeric(...));
    }
  }
  return $keywords;
}

/**
 * Translates a data definition into JSON Schema.
 *
 * Maps become objects and lists become arrays. Everything else is handed to
 * the serializer, which knows how each primitive will be normalized.
 *
 * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
 *   The data definition.
 *
 * @return array
 *   JSON Schema for the definition.
 */
function to_json_schema(DataDefinitionInterface $definition): array {
  $typed_data_manager = \Drupal::typedDataManager();
  $serializer = \Drupal::getContainer()->get('serializer');
  assert($serializer instanceof Serializer);

  if ($definition instanceof ComplexDataDefinitionInterface) {
    $properties = [];
    $required = [];
    foreach ($definition->getPropertyDefinitions() as $property_name => $property_definition) {
      $properties[$property_name] = to_json_schema($property_definition);
      if ($property_definition->isRequired()) {
        $required[] = $property_name;
      }
    }
    return array_filter([
      'type' => 'object',
      'properties' => $properties,
      'required' => $required,
      'additionalProperties' => FALSE,
    ], static fn ($value) => $value !== []);
  }

  if ($definition instanceof ListDataDefinitionInterface) {
    return [
      'type' => 'array',
      'items' => to_json_schema($definition->getItemDefinition()),
    ];
  }

  $data = $typed_data_manager->create($definition);
  $schema = $serializer->getJsonSchema($data, []);
  $keywords = constraints_to_json_schema($data);
  // Optional primitives come back as oneOf [type, null]. The constraints only
  // apply to the non-null branch.
  if (isset($schema['oneOf'])) {
    $schema['oneOf'][0] += $keywords;
    return $schema;
  }
  return $schema + $keywords;
}

// Build a definition that uses a nested map and a list, the two shapes the
// serializer cannot describe on its own.
$price_definition = MapDataDefinition::create()
  ->setPropertyDefinition('number', DataDefinition::create('decimal')->setRequired(TRUE))
  ->setPropertyDefinition(
    'currency_code',
    DataDefinition::create('string')
      ->setRequired(TRUE)
      ->addConstraint('AllowedValues', ['USD', 'CAD'])
  );

$product_definition = MapDataDefinition::create()
  ->setPropertyDefinition('id', DataDefinition::create('integer')->setRequired(TRUE))
  ->setPropertyDefinition(
    'title',
    DataDefinition::create('string')
      ->setRequired(TRUE)
      ->addConstraint('Length', ['max' => 255])
  )
  ->setPropertyDefinition('price', $price_definition->setRequired(TRUE))
  ->setPropertyDefinition('tags', ListDataDefinition::create('string'))
  ->setPropertyDefinition('created', DataDefinition::create('timestamp'));

$schema = to_json_schema($product_definition);
assert($schema === [
  'type' => 'object',
  'properties' => [
    'id' => ['type' => 'integer'],
    'title' => ['type' => 'string', 'maxLength' => 255],
    'price' => [
      'type' => 'object',
      'properties' => [
        'number' => ['type' => 'string', 'format' => 'number'],
        'currency_code' => ['type' => 'string', 'enum' => ['USD', 'CAD']],
      ],
      'required' => ['number', 'currency_code'],
      'additionalProperties' => FALSE,
    ],
    'tags' => [
      'type' => 'array',
      'items' => [
        'oneOf' => [
          ['type' => 'string'],
          ['type' => 'null'],
        ],
      ],
    ],
    'created' => ['type' => 'string', 'format' => 'date-time'],
  ],
  'required' => ['id', 'title', 'price'],
  'additionalProperties' => FALSE,
]);

output(json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

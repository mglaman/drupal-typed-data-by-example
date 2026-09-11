# Serialization

An example of using Typed Data API with Drupal's Serialization module (Symfony Serializer component) to convert typed data
into other formats (like JSON.)

## JSON Schema

Drupal 11 added a `JsonSchema` attribute (`\Drupal\Core\Serialization\Attribute\JsonSchema`) that describes the shape of a normalized value. Primitive data types carry it on `getCastedValue()`, and the serializer reads it when asked for the `json_schema` format or through `getJsonSchema()`.

Core stops at primitives. Maps and lists return a `$comment` placeholder instead of a schema. The [`json_schema.php`](json_schema.php) example shows what core provides and then walks a data definition to build an object and array schema on top of it, folding validation constraints such as `AllowedValues` and `Length` into `enum` and `maxLength` keywords.

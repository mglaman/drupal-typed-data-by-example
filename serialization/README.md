# Serialization

An example of using Typed Data API with Drupal's Serialization module (Symfony Serializer component) to convert typed data
into other formats (like JSON.)

## JSON Schema

Drupal 11 added a `JsonSchema` attribute (`\Drupal\Core\Serialization\Attribute\JsonSchema`) that describes the shape of a normalized value. Primitive data types carry it on `getCastedValue()`, and the serializer reads it when asked for the `json_schema` format or through `getJsonSchema()`.

Core stops at primitives. Maps and lists return a `$comment` placeholder instead of a schema. The [`json_schema.php`](json_schema.php) example shows what core provides and then walks a data definition to build an object and array schema on top of it, folding validation constraints such as `AllowedValues` and `Length` into `enum` and `maxLength` keywords.

The inverse works too. [`from_json_schema.php`](from_json_schema.php) walks a JSON Schema and builds map, list, and primitive data definitions from it, turning `enum`, `minLength`/`maxLength`, `minimum`/`maximum`, and `pattern` into constraints. It then wraps JSON payloads in those definitions and validates them, so a published schema is enough to validate incoming requests with typed data.

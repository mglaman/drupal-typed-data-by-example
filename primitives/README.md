# Primitives
This section covers primitive values in the Typed Data API.

Data types that represent primitive values implement `\Drupal\Core\TypedData\PrimitiveInterface`. This interface has a `getCastedValue` method. This ensures the value is always returned into it's actual type in PHP (not a string representation.)

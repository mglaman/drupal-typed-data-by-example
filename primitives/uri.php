<?php declare(strict_types=1);

/**
 * @file
 * An example of using the Typed Data API for URIs.
 *
 * Uniform Resource Identifier (URI) are described in RFC 3986. A URI is used
 * to identify a resource. Uniform Resource Locators (URLs) are a subset.
 *
 * @link https://datatracker.ietf.org/doc/html/rfc3986
 */

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\Plugin\DataType\Uri;

require __DIR__ . '/../vendor/autoload.php';


$typed_data_manager = \Drupal::typedDataManager();

// An URI definition.
$definition = DataDefinition::create('uri');

$url = $typed_data_manager->create($definition, 'https://mglaman.dev');
assert($url instanceof Uri);

assert(count($url->validate()) === 0);
assert($url->getValue() === 'https://mglaman.dev');

// This should be invalid as there is no scheme (file://, tel:, etc.)
$invalid_uri = $typed_data_manager->create($definition, 'mglaman.dev');
$violations = $invalid_uri->validate();
assert(count($violations) === 1);
// The validation constraint error is a bit misleading, since it does not tell
// us _why_ the URI is invalid. This is handled by PrimitiveTypeConstraint.
// @see \Drupal\Core\Validation\Plugin\Validation\Constraint\PrimitiveTypeConstraintValidator::validate:56
assert((string) $violations->get(0)->getMessage() === 'This value should be of the correct primitive type.');
output((string) $violations->get(0));

$uri = $typed_data_manager->create($definition, 'public://foo/bar/baz');
assert(count($uri->validate()) === 0);

<?php
declare(strict_types=1);

/**
 * @file
 * An example of using the Typed Data API for an email string.
 */

require __DIR__ . '/../vendor/autoload.php';

use Drupal\Core\Render\Element\Email as EmailElement;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\Plugin\DataType\Email;

$typed_data_manager = \Drupal::typedDataManager();

// The `Email` data type has annotation which automatically adds the `Email`
// validation constraint.
//
// Drupal also adds a maximum length constraint (applied
// on Field API level, above Typed Data API) in EmailItem.
$definition = DataDefinition::create('email')
  ->addConstraint('Length', [
    // @todo We should get this constant added to the DataType plugin!
    'max' => EmailElement::EMAIL_MAX_LENGTH,
    'maxMessage' => sprintf('The email address can not be longer than %s characters.',
      EmailElement::EMAIL_MAX_LENGTH),
  ]);


$valid = $typed_data_manager->create($definition, 'foo@example.com');
assert($valid instanceof Email);
$violations = $valid->validate();
assert(count($violations) === 0);

$invalid = $typed_data_manager->create($definition, 'foo@localhost');
assert($valid instanceof Email);
$violations = $invalid->validate();
assert(count($violations) === 1);
output('Reason');
output((string) $violations->get(0));

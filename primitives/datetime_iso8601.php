<?php declare(strict_types=1);

/**
 * @file
 * This file contains usage of Typed Data with ISO8601 date time strings.
 */
require __DIR__ . '/../vendor/autoload.php';

use Drupal\Core\TypedData\DataDefinition;
use \Drupal\Core\TypedData\Plugin\DataType\DateTimeIso8601;

$typed_data_manager = \Drupal::typedDataManager();

$definition = DataDefinition::create('datetime_iso8601');

$zulu_time = $typed_data_manager->create($definition, '2021-08-18T11:37:19Z');
assert($zulu_time instanceof DateTimeIso8601);
assert(count($zulu_time->validate()) === 0);
// ???? what. This PHP not Drupal. Expexted: UTC.
assert('Z' === $zulu_time->getDateTime()->getTimezone()->getName());

$zero_time = $typed_data_manager->create($definition, '2021-08-18T11:37:19+00:00');
assert($zero_time instanceof DateTimeIso8601);
assert(count($zero_time->validate()) === 0);
// ???? what. This PHP not Drupal. Expexted: UTC.
assert('+00:00' === $zero_time->getDateTime()->getTimezone()->getName(), $zero_time->getDateTime()->getTimezone()->getName());

$malformed = $typed_data_manager->create($definition, '2021-08-18');
assert($malformed instanceof DateTimeIso8601);
assert(count($malformed->validate()) === 0);
assert('2021-08-18T00:00:00+00:00' === $malformed->getDateTime()->format(\DateTimeInterface::ATOM));

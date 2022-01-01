# Drupal's Typed Data API by example

This repository aims to help show use cases and various exercises with using Drupal's [Typed Data API](https://www.drupal.org/docs/8/api/typed-data-api/typed-data-api-overview). 

> The Typed Data API was created to provide developers with a consistent way of interacting with data in different ways.

## Purpose

This repository provides examples of using the Typed Data API. The examples range from generic usage explaining the different data types available, to creating complex data definitions, to utilizing them in real world Drupal applications.

The intent is not to _run_ this code in your Drupal site, but to browse the code examples.

## Structure

The examples are broken into different groups, each with their own directory:

* [`primitives`](https://github.com/mglaman/drupal-typed-data-by-example/tree/main/primitives): each example covers the data types provided by Drupal core. These are intended to be simplistic examples of using a value and wrapping it in Typed Data data types for validation and representation.
* [`lists`](https://github.com/mglaman/drupal-typed-data-by-example/tree/main/lists): each example covers using lists (arrays of a specific type)
* [`maps`](https://github.com/mglaman/drupal-typed-data-by-example/tree/main/maps): each example covers using the Map data type, which allows you to define complex data definitions to describe an object's shape.
* [`serialization`](https://github.com/mglaman/drupal-typed-data-by-example/tree/main/serialization): each example shows how the Typed Data API integrates with Drupal's Serialization module, which leverages the Serializer component from Symfony.

## Trying it out!

All of the examples can be run to see sample outputs.

First, install the dependencies and generate the autoloader (requires PHP `^8.0`):

```bash
composer install
```

For example, the following will run the `emails` example in `primitives/` and print any of the additional output provided for context.

```bash
php primitives/email.php
```

# POTION

> Put your translations in motion.

Provides an enhanced Developer Experience (DX) when dealing with translations & multilingual websites.

|       Travis-CI        |        Style-CI         |        Downloads        |         Releases         |
|:----------------------:|:-----------------------:|:-----------------------:|:------------------------:|
| [![Travis](https://img.shields.io/travis/antistatique/drupal-potion.svg?style=flat-square)](https://travis-ci.org/antistatique/drupal-potion) | [![StyleCI](https://styleci.io/repos/104479458/shield)](https://styleci.io/repos/104479458) | [![Downloads](https://img.shields.io/badge/downloads-8.x--1.x--dev-green.svg?style=flat-square)](https://ftp.drupal.org/files/projects/potion-8.x-1.x-dev.tar.gz) | [![Latest Stable Version](https://img.shields.io/badge/release-v1.x--dev-blue.svg?style=flat-square)](https://www.drupal.org/project/potion/releases) |

## You need Potion if

* You want to use the Drupal Core built-in translation importation feature directly from your CLI,
* You want to use the Drupal Core built-in translation exportation feature directly from your CLI,
* You want to make your life easier by generating final client-friendly .po files by scrapping your code looking for custom translations,
* You want to be able to re-play a .po file by filling it with data from your database,
* You are a CLI lover & don't like to click in an UI to deal with translations.

Potion can do a lot more than that,
but those are some of the obvious uses of this module.

## Features

Still under active development, checkout our [Roadmap](./ROADMAP.md).

***Everything contained in this document is in draft form and subject to change at any time and provided for information purposes only***

## Standard usage scenario

Every command line has a strong documentation when using the `--help` argument.
Give it a try and feel free to send us feedback in the issue thread.

### Import standard po files into Drupal database

```bash
drush potion-import [--mode mode] [--overwrite] [--progress] [--verbose] [--quiet] [--dry-run] langcode source
```

### Export standard po files from Drupal database

```bash
drush potion-export [--include-core] [--include-custom] [--include-untranslated] [--progress] [--verbose] [--quiet] [--dry-run] langcode dest
```

### Generate, scrape & parse your code to generate po file

```bash
drush potion-generate [--exclude-yaml] [--exclude-twig] [--exclude-php] [--progress] [--verbose] [--quiet] langcode source dest
```

### Re-fill an existing po file with translations from Drupal database

```bash
drush potion-fill [--overwrite] [--verbose] [--quiet] langcode source
```

## Versions

Potion is only available for Drupal 8 !
The module is ready to be used in Drupal 8, there are no known issues.

This version should work with all Drupal 8 releases using Drush 9+,
and it is always recommended keeping Drupal core installations up to date.

## Dependencies

The Drupal 8 version of Potion requires [gettext]() & the [Symfony Process Component](https://symfony.com/doc/current/components/process.html).

* `Gettext` is a binary library embedded in the module to prevent version incompatibility & other mishandled behaviours.
* `Symfony Process Component` is an external PHP library. The recommended way of solving this dependency is using composer, running the following from the command line:

  ```bash
  composer require symfony/process:^3.4
  ```

## Supporting organizations

This project is sponsored by Antistatique. We are a Swiss Web Agency,
Visit us at [www.antistatique.net](https://www.antistatique.net) or
[Contact us](mailto:info@antistatique.net).

## Getting Started

We highly recommend you to install the module using `composer`.

  ```bash
  $ composer require drupal/potion
  ```

You can also install it using the `drush` or `drupal console` cli.

  ```bash
  $ drush dl potion
  ```

  ```bash
  $ drupal module:install potion
  ```
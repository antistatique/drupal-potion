# POTION

> Put your translations in motion.

Provides a normalized way to collect internationalization strings to export, merge & create .po files from versatile sources such as Twig, PHP or YML files.
Ensure an better Developer Experience (DX) when dealing with translations & multilingual websites.

|       Test-CI        |        Downloads        |         Releases         |
|:----------------------:|:-----------------------:|:------------------------:|
| [![Build Status](https://github.com/antistatique/drupal-potion/actions/workflows/ci.yml/badge.svg)](https://github.com/antistatique/drupal-potion/actions/workflows/ci.yml) | [![Code styles](https://github.com/antistatique/drupal-potion/actions/workflows/styles.yml/badge.svg)](https://github.com/antistatique/drupal-potion/actions/workflows/styles.yml) | [![Latest Stable Version](https://img.shields.io/badge/release-v2.0-blue.svg?style=flat-square)](https://www.drupal.org/project/potion/releases) |

## You need Potion if

* You want to use the Drupal Core built-in translation importation feature directly from your CLI,
* You want to use the Drupal Core built-in translation exportation feature directly from your CLI,
* You want to use a module based on the Core [Translation API](https://www.drupal.org/docs/8/api/translation-api/overview),
* You want to retrieve translation strings from your [Twig templates](https://www.drupal.org/docs/8/api/translation-api/overview) in theme(s),
* You want to retrieve translation strings from code,
* You want to retrieve translation strings from your shipped configuration and configuration schemas files formatted in YML(s),
* You want to make your life easier by generating final client-friendly .po files by scrapping your code looking for custom translations,
* You want to be able to re-play a .po file by filling it with data from your database,
* You are a CLI lover & don't like to click in an UI to deal with translations.

Potion can do a lot more than that,
but those are some obvious uses of this module.

## Features

Still under active development, checkout our [Roadmap](./ROADMAP.md).

***Everything contained in this document is in draft form and subject to change at any time and provided for information purposes only***

## Standard usage scenario

Every command line has a strong documentation when using the `--help` argument.
Give it a try and feel free to send us feedback in the issue thread.

### Import standard po files into Drupal database

Expose the [Core feature of translation importation](/admin/config/regional/translate/import) from .po as a command line.

```bash
drush potion-import [--mode mode] [--overwrite] [-q|--quiet] [-h|--help] langcode source
```

* `source`: The source po file
* `langcode`: Import the po into the given langcode
* `--mode`: Define the importation mode from 'customized' or 'non-customized'.
            Use 'non-customized' when translations are imported from .po files
            downloaded from localize.drupal.org for example.
            Use 'customized' when translations are edited from their imported
            originals on the user interface or are imported as customized.
            [default: "customized"]
* `--overwrite`: Overwrite existing translations with value in the source file.
* `-q|--quiet`: Do not ask any interactive question.
* `-h|--help`: Display usage details.


### Export standard po files from Drupal database

Expose the [Core feature of translation exportation](/admin/config/regional/translate/export) from .po as a command.

```bash
drush potion-export [--non-customized] [--customized] [--untranslated] [--progress] [-q|--quiet] [-h|--help] langcode dest
```

* `langcode`: The langcode to export
* `dest`: The destination folder of po file
* `--customized`: Export the customized translations.
* `--non-customized`: Export the non-customized translations
* `--untranslated`: Export untranslated string
* `-q|--quiet`: Do not ask any interactive question.
* `-h|--help`: Display usage details.

### Generate, scrape & parse your code to generate po file

Parse all the files in into the given `source` & generate a fresh `langcode`.po file.
If a `langcode`.po already exists in the `dest` dir, merge them & remove duplicates.

```bash
drush potion-generate [--exclude-yaml] [--exclude-twig] [--exclude-php] [--recursive] [-q|--quiet] [-h|--help] langcode source dest
```

* `langcode`: The langcode to generate
* `source`: The source folder to scan for translations
* `dest`: The destination folder of po file
* `--exclude-yaml`: Exclude YAML files (.yaml) to be scanned for translations
* `--exclude-twig`: Exclude TWIG files (.twig) to be scanned for translations
* `--exclude-php`: Exclude PHP files (.php, .module) to be scanned for translations
* `--recursive`: Enable scan recursion on the source folder
* `-q|--quiet`: Do not ask any interactive question.
* `-h|--help`: Display usage details.

### Re-fill an existing po file with translations from Drupal database

From a given `source` po file into a `langcode` read the whole database & fill the same po file with data.

```bash
drush potion-fill [--overwrite] [-q|--quiet] [-h|--help] langcode source
```

* `langcode`: The langcode to generate from database
* `source`: The source folder to scan for translations
* `--overwrite`: Overwrite existing translations in the po with value from the database.
* `-q|--quiet`: Do not ask any interactive question.
* `-h|--help`: Display usage details.

## Versions

The version `8.x-1.x` is not compatible with Drupal `8.8.x`.
Drupal `8.8.x` brings some breaking change with tests and so you
must upgrade to `8.x-2.x` version of **Potion**.

Potion is available for both Drupal 8 & Drupal 9 !

## Which version should I use?

|Drupal Core|Potion|Drush|
|:---------:|:-----|:---:|
|8.7.x      |1.x   |9.x  |
|8.8.x      |2.x   |10.x |
|9.x        |2.x   |10.x |

## Dependencies

This module relies on the [GNU gettext toolset](https://www.gnu.org/software/gettext/), the [Symfony Process Component](https://symfony.com/doc/current/components/process.html) & the [Symfony Finder Component](https://symfony.com/doc/current/components/finder.html).

* `Symfony Process Component` is an external PHP library to execute commands in sub-processes.
* `Symfony Finder Component` is an external PHP library to finds files and directories via an intuitive fluent interface.
* `Gettext` utilities are a set of tools that provides a framework to help other packages produce multi-lingual messages. The minimum version of the `gettext` utilities supported is `0.19.8.1`.

We assume, that you have installed `symfony/process`, `symfony/finder` & `gettex`. We also assume `gettext` utilities are accessible through your `$PATH`.
Otherwise, please see [How to install & setup Gettext](https://www.drupal.org/docs/8/modules/potion/how-to-install-setup-gettext) for more information,

## Supporting organizations

This project is sponsored by Antistatique. We are a Swiss Web Agency,
Visit us at [www.antistatique.net](https://www.antistatique.net) or
[Contact us](mailto:info@antistatique.net).

## Getting Started

We highly recommend you to install the module using `composer`.

  ```bash
  composer require drupal/potion
  ```

You can also install it using the `drush` or `drupal console` cli.

  ```bash
  drush dl potion
  ```

  ```bash
  drupal module:install potion
  ```

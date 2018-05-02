Potion Roadmap
-----------------

This document outlines the development plan from a high level and will be updated as progress is made toward version 1.0.
It should be noted that this roadmap applies only to potion Drupal 8 module.

***Everything contained in this document is in draft form and subject to change at any time and provided for information  purposes only. We do not guarantee the accuracy of the information contained in this roadmap and the information is provided “as is” with no representations or warranties, express or implied.***

## Phase 1 - Minimal Viable Product

No user interface, everything is done via CLI.

### CLI - Import standard po files into Drupal database

Expose the [Core feature of translation importation](/admin/config/regional/translate/import) from .po as a command line.

*Usage*
```
drush potion-import [--mode mode] [--overwrite] [--progress] [--verbose] [--quiet] [--dry-run] langcode source
```

*Errors handling*
- Warning on wrong langcode
- Warning on malformed po file
- Use Interactive question for mandatory parameters such as:
  - `langcode`
  - `source`

*Parameters*
- `source`: The source po file
- `langcode`: Import the po into the given langcode
- `--mode`: define the importation mode from 'custom' or 'core'. `custom` is used by default.
- `--overwrite`: Overwrite existing translations with value in the source file.
- `--progress`: Show progress bar during the importation
- `--verbose`: Summarise table when finish (imported, skipped, updated ...)
- `--quiet`: Do not ask any interactive question
- `--dry-run`: Give a preview of what the command will do

### CLI - Export standard po files from Drupal database

Expose the [Core feature of translation exportation](/admin/config/regional/translate/export) from .po as a command.

*Usage*
```
drush potion-export [--include-core] [--include-custom] [--include-untranslated] [--progress] [--verbose] [--quiet] [--dry-run] langcode dest
```

*Errors handling*
- Warning on wrong langcode
- Warning on malformed existing po file
- Use Interactive question for mandatory parameters such as:
  - `langcode`
  - `dest`

*Parameters*
- `langcode`: The langcode to export
- `dest`: The destination folder of po file
- `--include-core`: Export the core translations.
- `--include-custom`: Export the custom translations
- `--include-untranslated`: Export untranslated string
- `--progress`: Show progress bar during the importation
- `--verbose`: Summarise table when finish (n° customs, n° core, n° translated, n° untranslated ...)
- `--quiet`: Do not ask any interactive question
- `--dry-run`: Give a preview of what the command will do

### CLI - Generate po file from code

Parse all the files in into the given `source` & generate a fresh `langcode`.po file.
If a `langcode`.po already exists in the `dest` dir, merge them & remove duplicates.

*Usage*
```
drush potion-generate [--exclude-yaml] [--exclude-twig] [--exclude-php] [--progress] [--verbose] [--quiet] langcode source dest
```

*Errors handling*
- Warning on wrong langcode
- Warning source is not a dir
- Warning dest is not a dir
- Use Interactive question for mandatory parameters such as:
  - `langcode`
  - `source`
  - `dest`

*Parameters*
- `langcode`: The langcode to generate
- `source`: The source folder to scan for translations
- `dest`: The destination folder of po file
- `--exclude-yaml`: Exclude YAML files (.yaml) to be scanned for translations
- `--exclude-twig`: Exclude TWIG files (.twig) to be scanned for translations
- `--exclude-php`: Exclude PHP files (.php, .module) to be scanned for translations
- `--progress`: Show progress bar during the importation
- `--verbose`: Summarise table when finish (merged or not with the previous file, n° strings found, n° of new strings)
- `--quiet`: Do not ask any interactive question

### CLI - Fill po file from database

*Usage*
```
drush potion-fill [--overwrite] [--verbose] [--quiet] langcode source
```

From a given `source` po file into a `langcode` read the whole database & fill the same po file with data.

*Errors handling*
- Warning on wrong langcode
- Warning source is not a compatible po file
  - Use Interactive question for mandatory parameters such as:
  - `langcode`
  - `source`

*Parameters*
- `langcode`: The langcode to generate from database
- `source`: The source folder to scan for translations
- `--overwrite`: Overwrite existing translations in the po with value from the database.
- `--verbose`: Summarise table when finish (translated, untranslated, overwritten)
- `--quiet`: Do not ask any interactive question

### CLI - Validate po file

*Usage*
```
drush potion-validate [--verbose] [--dry-run] source
```

From a given `source` po file check his validity.

*Errors handling*
- Warning source is not a po file

*Parameters*
- `source`: The source po file to validate
- `--verbose`: Summarise table when finish (strings, translated, untranslated)

# Phase 2 - Enhanced Product

Implements User Interface for new features:
- Generate po file from code
- Fill po file from database
- Validate po file


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
drush potion-import [--mode mode] [--overwrite] [-q|--quiet] [-h|--help] langcode source
```

*Errors handling*
- Warning on wrong langcode
- Warning on malformed po file

*Parameters*
- `source`: The source po file
- `langcode`: Import the po into the given langcode
- `--mode`: Define the importation mode from 'customized' or 'non-customized'.
            Use 'non-customized' when translations are imported from .po files
            downloaded from localize.drupal.org for example.
            Use 'customized' when translations are edited from their imported
            originals on the user interface or are imported as customized.
            [default: "customized"]
- `--overwrite`: Overwrite existing translations with value in the source file.
- `-q|--quiet`: Do not ask any interactive question.
- `-h|--help`: Display usage details.

*Out of Scope parameters*
- `--progress`: Show progress bar during the importation
- `--dry-run`: Give a preview of what the command will do

### CLI - Export standard po files from Drupal database

Expose the [Core feature of translation exportation](/admin/config/regional/translate/export) from .po as a command.

*Usage*
```
drush potion-export [--non-customized] [--customized] [--untranslated] [--progress] [-q|--quiet] [-h|--help] langcode dest
```

*Errors handling*
- Warning on wrong langcode
- Warning on malformed existing po file

*Parameters*
- `langcode`: The langcode to export
- `dest`: The destination folder of po file
- `--customized`: Export the customized translations.
- `--non-customized`: Export the non-customized translations
- `--untranslated`: Export untranslated string
- `-q|--quiet`: Do not ask any interactive question.
- `-h|--help`: Display usage details.

*Out of Scope parameters*
- `--progress`: Show progress bar during the exportation
- `--dry-run`: Give a preview of what the command will do
- `--verbose`: Report table when finish (n° customs, n° core, n° translated, n° untranslated ...)

### CLI - Generate po file from code

Parse all the files in into the given `source` & generate a fresh `langcode`.po file.
If a `langcode`.po already exists in the `dest` dir, merge them & remove duplicates.

*Usage*
```
drush potion-generate [--exclude-yaml] [--exclude-twig] [--exclude-php] [--recursive] [-q|--quiet] [-h|--help] langcode source dest
```

*Errors handling*
- Warning on wrong langcode
- Warning source is not a dir
- Warning dest is not a dir
- Warning source is not a readable
- Warning dest is not a writable

*Parameters*
- `langcode`: The langcode to generate
- `source`: The source folder to scan for translations
- `dest`: The destination folder of po file
- `--exclude-yaml`: Exclude YAML files (.yaml) to be scanned for translations
- `--exclude-twig`: Exclude TWIG files (.twig) to be scanned for translations
- `--exclude-php`: Exclude PHP files (.php, .module) to be scanned for translations
- `--recursive`: Enable scan recursion on the source folder
- `-q|--quiet`: Do not ask any interactive question.
- `-h|--help`: Display usage details.

### CLI - Fill po file from database

*Usage*
```
drush potion-fill [--overwrite] [-q|--quiet] [-h|--help] langcode source
```

From a given `source` po file on a `langcode` read the whole database & fill the same po file with data.

*Errors handling*
- Warning on wrong langcode
- Warning source is not a compatible po file

*Parameters*
- `langcode`: The langcode to generate from database
- `source`: The source folder to scan for translations
- `--overwrite`: Overwrite existing translations in the po with value from the database.
- `-q|--quiet`: Do not ask any interactive question.
- `-h|--help`: Display usage details.

# Phase 2 - Enhanced Product

Implements User Interface for new features:
- Generate po file from code
- Fill po file from database

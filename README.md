# Composer - Project Builder Plugin

This composer plugin allows you to build your composer project in different
locations depending on the dev mode of your composer command and/or the branch
or tag you are on currently on in your VCS. Whenever possible composer packages
will be installed in your original vendor-dir location and a symlink will be
placed at the build location.

The plugin is developed to be used in combination with `composer/installers` and
`cweagans/composer-patches` that help provide the functional build structure of
this plugin.


* [How it works](#how-it-works)
* [Installation](#installation)
* [Project build example](#project-build-example)

## How it works

When running any composer install/update command your `vendor-dir` configuration
will be prefixed with a subdirectory depending on the dev mode option in
combination with the active VCS branch or tag. By default commands run with:
* `--dev` are prefixed with `build/{$branch}`
* `--no-dev` are prefixed `dist/{$tag}` and `dist/{$branch}/` as fallback

All packages will still be placed at the original vendor-dir location unless
your package provides a `binary` or will be `patched`. Such cases will always
have their source code placed within the build location to ensure they are
functioning as intended.

Any package that does not require the source code to be present at the build
location will have a relative symlink placed within the vendor-dir of the build
location.

## Installation

Run the following command:

```
$ composer require verbruggenalex/composer-project-builder-plugin:dev-master
```

## Project build example

Here is a complete example of a Drupal 7 project with `drush/drush` that
contains a binary and `drupal/stage_file_proxy` that will be patched. The
project will have the source code of package `drupal/drupal` copied to the root
directory of the build location.

### Composer file

<details><summary>composer.json</summary>

``` json
{
    "require": {
        "drush/drush": "8.*",
        "verbruggenalex/composer-project-builder-plugin": "dev-master",
        "verbruggenalex/multisite_drupal_standard": "2.4.79",
    },
    "require-dev": {
        "drupal/devel": "~1.5.0",
        "drupal/maillog": "1.0.0-alpha1",
        "drupal/stage_file_proxy": "1.7.0"
    },
    "repositories": [
        {"type": "composer", "url": "https://packages.drupal.org/7"},
    ],
    "minimum-compatibility": "dev",
    "extra": {
        "project-builder": {
            "build-dir": {
                 "--dev": "build/{$branch}",
                 "--no-dev": "dist/{$branch}"
             },
             "root-dir": {
                 "--dev": "drupal/drupal",
                 "--no-dev": "drupal/drupal"
            }
        },
        "installer-paths": {
            "profiles/{$name}/": ["type:drupal-profile"],
            "sites/all/drush/{$name}/": ["type:drupal-drush"],
            "sites/all/libraries/{$name}/": ["type:drupal-library"],
            "sites/all/modules/contrib/{$name}/": ["type:drupal-module"],
            "sites/all/themes/contrib/{$name}/": ["type:drupal-theme"]
         },
         "patches": {
             "drupal/stage_file_proxy": [
                 "https://www.drupal.org/files/issues/hotlinking-doesnt-work-for-files-2820271-1.patch"
             ]
         },
         "enable-patching": true
    }
}
```

</details>

### Directory structure after `composer install` :

<details><summary>Functional Drupal 7 installation found under `build/master`</summary>

``` bash
├── authorize.php
├── CHANGELOG.txt
├── COPYRIGHT.txt
├── cron.php
├── includes
├── index.php
├── INSTALL.mysql.txt
├── INSTALL.pgsql.txt
├── install.php
├── INSTALL.sqlite.txt
├── INSTALL.txt
├── LICENSE.txt
├── MAINTAINERS.txt
├── misc
├── modules
├── PATCHES.txt
├── profiles
│   ├── minimal
│   ├── multisite_drupal_standard -> ../../../vendor/verbruggenalex/multisite_drupal_standard-2.4.79
│   ├── standard
│   └── testing
├── scripts
├── sites
│   ├── all
│   │   ├── libraries
│   │   ├── modules
│   │   │   └── contrib
│   │   │       ├── ctools -> ../../../../../../vendor/drupal/ctools-1.13.0
│   │   │       ├── devel -> ../../../../../../vendor/drupal/devel-1.5.0
│   │   │       ├── maillog -> ../../../../../../vendor/drupal/maillog-1.0.0-alpha1
│   │   │       ├── stage_file_proxy -> ../../../../../../vendor/drupal/stage_file_proxy-1.7.0
│   │   │       └── views -> ../../../../../../vendor/drupal/views-3.18.0
│   │   └── themes
│   └── default
├── themes
└── vendor
    ├── autoload.php
    ├── bin
    │   ├── drush -> ../drush/drush/drush
    │   ├── drush.complete.sh -> ../drush/drush/drush.complete.sh
    │   ├── drush.launcher -> ../drush/drush/drush.launcher
    │   ├── drush.php -> ../drush/drush/drush.php
    │   ├── php-parse -> ../nikic/php-parser/bin/php-parse
    │   └── psysh -> ../psy/psysh/bin/psysh
    ├── composer
    │   └── autoload_classmap.php
    │   └── autoload_files.php
    │   └── autoload_namespaces.php
    │   └── autoload_psr4.php
    │   └── autoload_real.php
    │   └── autoload_static.php
    │   └── ClassLoader.php
    │   └── installers
    ├── consolidation
    │   ├── annotated-command -> ../../../../vendor/consolidation/annotated-command-2.8.2
    │   └── output-formatters -> ../../../../vendor/consolidation/output-formatters-3.1.13
    ├── cweagans
    │   └── composer-patches
    ├── dnoegel
    │   └── php-xdg-base-dir -> ../../../../vendor/dnoegel/php-xdg-base-dir-0.1
    ├── drupal
    │   ├── ctools -> ../../../../vendor/drupal/ctools-1.13.0
    │   ├── devel -> ../../../../vendor/drupal/devel-1.5.0
    │   ├── drupal
    │   ├── maillog -> ../../../../vendor/drupal/maillog-1.0.0-alpha1
    │   ├── stage_file_proxy -> ../../../../vendor/drupal/stage_file_proxy-1.7.0
    │   └── views -> ../../../../vendor/drupal/views-3.18.0
    ├── drush
    │   └── drush
    │       ├── drush
    ├── ec-europa
    │   └── oe-poetry-client -> ../../../../vendor/ec-europa/oe-poetry-client-0.3.5
    ├── guzzlehttp
    │   ├── guzzle -> ../../../../vendor/guzzlehttp/guzzle-6.3.0
    │   ├── promises -> ../../../../vendor/guzzlehttp/promises-v1.3.1
    │   └── psr7 -> ../../../../vendor/guzzlehttp/psr7-1.4.2
    ├── jakub-onderka
    │   ├── php-console-color -> ../../../../vendor/jakub-onderka/php-console-color-0.1
    │   └── php-console-highlighter -> ../../../../vendor/jakub-onderka/php-console-highlighter-v0.3.2
    ├── league
    │   └── plates -> ../../../../vendor/league/plates-3.3.0
    ├── nikic
    │   └── php-parser
    │       ├── bin
    ├── pear
    │   └── console_table -> ../../../../vendor/pear/console_table-v1.3.1
    ├── pimple
    │   └── pimple -> ../../../../vendor/pimple/pimple-v3.2.3
    ├── psr
    │   ├── cache -> ../../../../vendor/psr/cache-1.0.1
    │   ├── container -> ../../../../vendor/psr/container-1.0.0
    │   ├── http-message -> ../../../../vendor/psr/http-message-1.0.1
    │   ├── log -> ../../../../vendor/psr/log-1.0.2
    │   └── simple-cache -> ../../../../vendor/psr/simple-cache-1.0.0
    ├── psy
    │   └── psysh
    │       ├── bin
    ├── symfony
    │   ├── cache -> ../../../../vendor/symfony/cache-v4.0.4
    │   ├── console -> ../../../../vendor/symfony/console-v3.4.4
    │   ├── debug -> ../../../../vendor/symfony/debug-v4.0.4
    │   ├── dom-crawler -> ../../../../vendor/symfony/dom-crawler-v3.4.4
    │   ├── event-dispatcher -> ../../../../vendor/symfony/event-dispatcher-v3.4.4
    │   ├── expression-language -> ../../../../vendor/symfony/expression-language-v3.4.4
    │   ├── finder -> ../../../../vendor/symfony/finder-v3.4.4
    │   ├── polyfill-mbstring -> ../../../../vendor/symfony/polyfill-mbstring-v1.7.0
    │   ├── translation -> ../../../../vendor/symfony/translation-v4.0.4
    │   ├── validator -> ../../../../vendor/symfony/validator-v3.4.4
    │   ├── var-dumper -> ../../../../vendor/symfony/var-dumper-v3.4.4
    │   └── yaml -> ../../../../vendor/symfony/yaml-v3.4.4
    ├── verbruggenalex
    │   ├── composer-project-builder-plugin -> ../../../../vendor/verbruggenalex/omposer-project-builder-plugin-dev-master
    │   └── multisite_drupal_standard -> ../../../../vendor/verbruggenalex/multisite_drupal_standard-2.4.79
    └── webmozart
        ├── assert -> ../../../../vendor/webmozart/assert-1.3.0
        └── path-util -> ../../../../vendor/webmozart/path-util-2.3.0
```

</details>

### Directory structure after `composer install --no-dev` :

``` bash

```

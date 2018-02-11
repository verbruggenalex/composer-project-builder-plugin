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

``` json
// composer.json (project)
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

Directory structure after `composer install` :

``` bash

```

Directory structure after `composer install --no-dev` :

``` bash

```

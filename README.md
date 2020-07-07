# Slug

[![Build Status](https://img.shields.io/github/workflow/status/UseMuffin/Slug/CI/master)](https://github.com/UseMuffin/Slug/actions)
[![Coverage](https://img.shields.io/codecov/c/github/UseMuffin/Slug/master.svg?style=flat-square)](https://codecov.io/github/UseMuffin/Slug)
[![Total Downloads](https://img.shields.io/packagist/dt/muffin/slug.svg?style=flat-square)](https://packagist.org/packages/muffin/slug)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

Slugging for CakePHP

## Installation

Using [Composer][composer]:

```bash
composer require muffin/slug
```

Load the plugin using the CLI command:

```bash
./bin/cake plugin load Muffin/Slug
```

## Usage
To enable slugging add the behavior to your table classes in the
`initialize()` method.

```php
public function initialize(array $config): void
{
    //etc
    $this->addBehavior('Muffin/Slug.Slug', [
        // Optionally define your custom options here (see Configuration)
    ]);
}
```

> Please note that Slug expects a database column named `slug` to function.
> If you prefer to use another column make sure to specify the `field`
> configuration option.

### Searching
If you want to find a record using its slug, a custom finder is provided by the plugin.

```php
// src/Controller/ExamplesController.php
$example = $this->Examples->find('slugged', ['slug' => $slug]);
```

## Configuration

Slug comes with the following configuration options:

- `field`: name of the field (column) to hold the slug. Defaults to `slug`.
- `displayField`: name of the field(s) to build the slug from. Defaults to
     the `\Cake\ORM\Table::displayField()`.
- `separator`: defaults to `-`.
- `replacements`: hash of characters (or strings) to custom replace before
 generating the slug.
- `maxLength`: maximum length of a slug. Defaults to the field's limit as
 defined in the schema (when possible). Otherwise, no limit.
- `slugger`: class that implements the `Muffin\Slug\SlugInterface`. Defaults
 to `Muffin\Slug\Slugger\CakeSlugger`.
- `unique:`: tells if slugs should be unique. Set this to a callable if you
 want to customize how unique slugs are generated. Defaults to `true`.
- `scope`: extra conditions used when checking a slug for uniqueness.
- `implementedEvents`: events this behavior listens to. Defaults to
  `['Model.buildValidator' => 'buildValidator', 'Model.beforeSave' => 'beforeSave']`.
  By default the behavior adds validation for the `displayField` fields to make
  them required on record creating. If you don't want these auto added validations
  you can set `implementedEvents` to just `['Model.beforeSave' => 'beforeSave']`.
- `onUpdate`: Boolean indicating whether slug should be updated when updating
  record, defaults to `false`.
- `onDirty`: Boolean indicating whether slug should be updated when slug field
  is dirty (has a preset value custom value), defaults to `false`.

## Sluggers

The plugin contains two sluggers:

### CakeSlugger

The `CakeSlugger` uses `\Cake\Utility\Text::slug()` to generate slugs. In the
behavior config you can set the `slugger` key as shown below to pass options to
the `$options` arguments of `Text::slug()`.

```php
'slugger' => [
    'className' => \Muffin\Slug\Slugger\CakeSlugger::class,
    'transliteratorId' => '<A valid ICU Transliterator ID here>'
]
```

### ConcurSlugger

The `ConcurSlugger` uses [concur/slugify](https://github.com/cocur/slugify) to generate slugs.
You can use config array similar to the one shown above to pass options to
`Cocur\Slugify\Slugify`'s constructor.

## Patches & Features

* Fork
* Mod, fix
* Test - this is important, so it's not unintentionally broken
* Commit - do not mess with license, todo, version, etc. (if you do change any, bump them into commits of
their own that I can ignore when I pull)
* Pull request - bonus point for topic branches

To ensure your PRs are considered for upstream, you MUST follow the CakePHP coding standards.

## Bugs & Feedback

http://github.com/usemuffin/slug/issues

## License

Copyright (c) 2015-Present, [Use Muffin][muffin] and licensed under [The MIT License][mit].

[cakephp]:http://cakephp.org
[composer]:http://getcomposer.org
[mit]:http://www.opensource.org/licenses/mit-license.php
[muffin]:http://usemuffin.com

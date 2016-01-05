# Slug

[![Build Status](https://img.shields.io/travis/UseMuffin/Slug/master.svg?style=flat-square)](https://travis-ci.org/UseMuffin/Slug)
[![Coverage](https://img.shields.io/coveralls/UseMuffin/Slug/master.svg?style=flat-square)](https://coveralls.io/r/UseMuffin/Slug)
[![Total Downloads](https://img.shields.io/packagist/dt/muffin/slug.svg?style=flat-square)](https://packagist.org/packages/muffin/slug)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

Slugging for CakePHP 3.x

## Requirements

- CakePHP 3.0+

## Installation

Using [Composer][composer]:

```bash
composer require muffin/slug:~1.0
```

To make your application load the plugin either run:

```bash
./bin/cake plugin load Muffin/Slug
```

or add the following line to `config/bootstrap.php`:

```php
Plugin::load('Muffin/Slug');
```

## Usage
To enable slugging add the behavior to your table classes in the
`initialize()` method.

```php
public function initialize(array $config)
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
If you want to find a record using it's slug, a custom finder is provided by the plugin.

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
- `implementedEvents`: events this behavior listens to.
- `implementedFinders`: custom finders implemented by this behavior.
- `implementedMethods`: mixin methods directly accessible from the table.
- `onUpdate`: Boolean indicating whether slug should be updated when updating 
  record, defaults to `false`.

## Patches & Features

* Fork
* Mod, fix
* Test - this is important, so it's not unintentionally broken
* Commit - do not mess with license, todo, version, etc. (if you do change any, bump them into commits of
their own that I can ignore when I pull)
* Pull request - bonus point for topic branches

To ensure your PRs are considered for upstream, you MUST follow the CakePHP coding standards. A `pre-commit`
hook has been included to automatically run the code sniffs for you:

```bash
ln -s ../../contrib/pre-commit .git/hooks/.
```

## Bugs & Feedback

http://github.com/usemuffin/slug/issues

## Credits

This was originally inspired by @dereuromark's [`SluggedBehavior`](https://github.com/dereuromark/cakephp-tools/blob/cake3/src/Model/Behavior/SluggedBehavior.php).

## License

Copyright (c) 2015, [Use Muffin][muffin] and licensed under [The MIT License][mit].

[cakephp]:http://cakephp.org
[composer]:http://getcomposer.org
[mit]:http://www.opensource.org/licenses/mit-license.php
[muffin]:http://usemuffin.com

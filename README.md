# Slug

[![Build Status](https://travis-ci.org/muffin/slug.svg?branch=master)](https://travis-ci.org/usemuffin/slug)
[![Total Downloads](https://poser.pugx.org/muffin/slug/downloads.svg)](https://packagist.org/packages/muffin/slug)
[![License](https://poser.pugx.org/muffin/slug/license.svg)](https://packagist.org/packages/muffin/slug)

Slugging for CakePHP 3.x

## Install

Using [Composer][composer]:

```
composer require muffin/slug:dev-master
```

You then need to load the plugin. In `boostrap.php`, something like:

```php
\Cake\Core\Plugin::load('Muffin/Slug');
```

## Usage

{{@TODO documentation}}

## Patches & Features

* Fork
* Mod, fix
* Test - this is important, so it's not unintentionally broken
* Commit - do not mess with license, todo, version, etc. (if you do change any, bump them into commits of
their own that I can ignore when I pull)
* Pull request - bonus point for topic branches

To ensure your PRs are considered for upstream, you MUST follow the CakePHP coding standards. A `pre-commit`
hook has been included to automatically run the code sniffs for you:

```
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
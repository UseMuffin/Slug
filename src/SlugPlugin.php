<?php
declare(strict_types=1);

namespace Muffin\Slug;

use Cake\Core\BasePlugin;

class Plugin extends BasePlugin
{
    /**
     * The name of this plugin
     *
     * @var string|null
     */
    protected ?string $name = 'Slug';

    /**
     * Do bootstrapping or not
     *
     * @var bool
     */
    protected bool $bootstrapEnabled = false;

    /**
     * Load routes or not
     *
     * @var bool
     */
    protected bool $routesEnabled = false;

    /**
     * Console middleware
     *
     * @var bool
     */
    protected bool $consoleEnabled = false;
}

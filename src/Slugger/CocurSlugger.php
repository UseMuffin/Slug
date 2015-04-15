<?php
namespace Muffin\Slug\Slugger;

use Cocur\Slugify\Slugify;
use Muffin\Slug\SluggerInterface;

/**
 * Cocur\Slugify's slugger.
 */
class CocurSlugger implements SluggerInterface
{
    /**
     * Regex.
     *
     * @var string
     */
    public static $regex = '/([^a-z0-9]|-)+/';

    /**
     * {@inheritdoc}
     */
    public static function slug($string, $replacement = '-')
    {
        return (new Slugify(static::$regex))->slugify($string, $replacement);
    }
}

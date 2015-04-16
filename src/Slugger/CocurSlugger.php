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
     * Generate slug.
     *
     * @param string $string Input string.
     * @param string $replacement Replacement string.
     * @return string Sluggified string.
     */
    public static function slug($string, $replacement = '-')
    {
        return (new Slugify(static::$regex))->slugify($string, $replacement);
    }
}

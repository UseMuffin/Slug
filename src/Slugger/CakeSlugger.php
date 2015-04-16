<?php
namespace Muffin\Slug\Slugger;

use Cake\Utility\Inflector;
use Muffin\Slug\SluggerInterface;

/**
 * CakePHP slugger (using `Inflector::slug()`).
 */
class CakeSlugger implements SluggerInterface
{

    /**
     * Generate slug.
     *
     * @param string $string Input string.
     * @param string $replacement Replacement string.
     * @return string Sluggified string.
     */
    public static function slug($string, $replacement = '-')
    {
        return Inflector::slug($string, $replacement);
    }
}

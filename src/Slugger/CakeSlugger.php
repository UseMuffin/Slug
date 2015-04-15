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
     * {@inheritdoc}
     */
    public static function slug($string, $replacement = '-')
    {
        return Inflector::slug($string, $replacement);
    }
}

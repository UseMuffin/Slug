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
     * Config options.
     *
     * - `lowercase` - Boolean indication whether slug should be lowercased.
     *   Default to true.
     *
     * @var array
     */
    public $config = [
        'lowercase' => true
    ];

    /**
     * Generate slug.
     *
     * @param string $string Input string.
     * @param string $replacement Replacement string.
     * @return string Sluggified string.
     */
    public function slug($string, $replacement = '-')
    {
        $string = Inflector::slug($string, $replacement);

        if ($this->config['lowercase']) {
            return strtolower($string);
        }

        return $string;
    }
}

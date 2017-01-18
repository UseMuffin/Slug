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
     * Config options.
     *
     * - `regex` - Regular expression passed to Concur slugger's constructor.
     * - `lowercase` - Boolean indication whether slug should be lowercased.
     *   Default to true.
     *
     * @var array
     */
    public $config = [
        'regex' => null,
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
        $options = $this->config;
        $regex = $options['regex'];
        unset($options['regex']);

        return Slugify::create($regex, $options)->slugify($string, $replacement);
    }
}

<?php
declare(strict_types=1);

namespace Muffin\Slug\Slugger;

use Cake\Utility\Text;
use Muffin\Slug\SluggerInterface;

/**
 * CakePHP slugger.
 */
class CakeSlugger implements SluggerInterface
{
    /**
     * Config options. You can set any valid option which Text::slug() takes
     * besides the one listed below:
     *
     * - `lowercase` - Boolean indication whether slug should be lowercased.
     *   Defaults to true.
     *
     * @var array<string, mixed>
     */
    protected array $config = [
        'lowercase' => true,
    ];

    /**
     * Constructor
     *
     * @param array<string, mixed> $config Configuration.
     */
    public function __construct(array $config = [])
    {
        $this->config = $config + $this->config;
    }

    /**
     * Generate slug.
     *
     * @param string $string Input string.
     * @param string $separator Replacement string.
     * @return string Sluggified string.
     */
    public function slug(string $string, string $separator = '-'): string
    {
        $config = $this->config;
        $config['replacement'] = $separator;
        $string = Text::slug($string, $config);

        if ($config['lowercase']) {
            return mb_strtolower($string);
        }

        return $string;
    }
}

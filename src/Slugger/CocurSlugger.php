<?php
declare(strict_types=1);

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
     * @var array<string, mixed>
     */
    protected array $config = [
        'regex' => null,
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
        return Slugify::create($this->config)->slugify($string, $separator);
    }
}

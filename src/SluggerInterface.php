<?php
declare(strict_types=1);

namespace Muffin\Slug;

/**
 * Slugger interface.
 */
interface SluggerInterface
{
    /**
     * Return a URL safe version of a string.
     *
     * @param string $string Original string.
     * @param string $separator Separator.
     * @return string
     */
    public function slug(string $string, string $separator = '-'): string;
}

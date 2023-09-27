<?php
namespace Muffin\Slug\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class AuthorsFixture extends TestFixture
{
    public string $table = 'slug_authors';

    /**
     * records property
     *
     * @var array
     */
    public array $records = [
        ['name' => 'admad'],
        ['name' => 'jadb'],
    ];
}

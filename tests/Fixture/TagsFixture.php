<?php
namespace Muffin\Slug\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class TagsFixture extends TestFixture
{
    public string $table = 'slug_tags';

    public array $records = [
        [
            'namespace' => null,
            'slug' => 'color',
            'name' => 'Color',
            'counter' => 2,
        ],
        [
            'namespace' => null,
            'slug' => 'dark-color',
            'name' => 'Dark Color',
            'counter' => 1,
        ],
    ];

    public function init(): void
    {
        $created = $modified = date('Y-m-d H:i:s');
        array_walk($this->records, function (&$record) use ($created, $modified) {
            $record += compact('created', 'modified');
        });

        parent::init();
    }
}

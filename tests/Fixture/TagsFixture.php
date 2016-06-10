<?php
namespace Muffin\Slug\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class TagsFixture extends TestFixture
{
    public $table = 'slug_tags';

    public $fields = [
        'id' => ['type' => 'integer'],
        'namespace' => ['type' => 'string', 'length' => 255, 'null' => true],
        'slug' => ['type' => 'string', 'length' => 255],
        'name' => ['type' => 'string', 'length' => 320],
        'counter' => ['type' => 'integer', 'unsigned' => true, 'default' => 0, 'null' => true],
        'created' => ['type' => 'datetime', 'null' => true],
        'modified' => ['type' => 'datetime', 'null' => true],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id']],
        ],
    ];

    public $records = [
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

    public function init()
    {
        $created = $modified = date('Y-m-d H:i:s');
        array_walk($this->records, function (&$record) use ($created, $modified) {
            $record += compact('created', 'modified');
        });
        parent::init();
    }
}

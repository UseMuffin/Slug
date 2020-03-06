<?php
namespace Muffin\Slug\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class RecipesFixture extends TestFixture
{
    public $table = 'slug_recipes';

    public $fields = [
        'id' => ['type' => 'integer'],
        'slug' => ['type' => 'string'],
        'name' => ['type' => 'string', 'length' => 320],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id']],
        ],
    ];

    public $records = [
        [
            'slug' => 'cake',
            'name' => 'Cake',
        ],
        [
            'slug' => 'muffin',
            'name' => 'Muffin',
        ],
    ];
}

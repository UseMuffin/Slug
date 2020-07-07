<?php
namespace Muffin\Slug\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class ArticlesFixture extends TestFixture
{
    public $table = 'slug_articles';

    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer'],
        'author_id' => ['type' => 'integer'],
        'title' => ['type' => 'string', 'null' => false],
        'sub_title' => ['type' => 'string', 'null' => false],
        'slug' => ['type' => 'string', 'null' => false],
        'created' => ['type' => 'datetime', 'null' => true],
        'modified' => ['type' => 'datetime', 'null' => true],
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        ['author_id' => 1, 'title' => 'First Article', 'sub_title' => 'subtitle 1', 'slug' => 'first-title'],
        ['author_id' => 1, 'title' => 'Second Article', 'sub_title' => 'subtitle 2', 'slug' => 'second-title'],
        ['author_id' => 1, 'title' => 'Third Article', 'sub_title' => 'subtitle 3', 'slug' => 'third-title'],
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

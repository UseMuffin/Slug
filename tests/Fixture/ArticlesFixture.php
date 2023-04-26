<?php
namespace Muffin\Slug\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class ArticlesFixture extends TestFixture
{
    public string $table = 'slug_articles';

    /**
     * records property
     *
     * @var array
     */
    public array $records = [
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

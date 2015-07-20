<?php

namespace Muffin\Slug\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class ArticlesTagsFixture extends TestFixture
{
    public $table = 'slug_articles_tags';

    public $fields = [
        'id' => ['type' => 'integer'],
        'article_id' => ['type' => 'integer'],
        'slug_tag_id' => ['type' => 'integer']
    ];

    public $records = [
        ['article_id' => 1, 'slug_tag_id' => 1],
        ['article_id' => 1, 'slug_tag_id' => 2],
        ['article_id' => 2, 'slug_tag_id' => 2],
    ];
}

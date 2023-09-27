<?php

namespace Muffin\Slug\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class ArticlesTagsFixture extends TestFixture
{
    public string $table = 'slug_articles_tags';

    public array $records = [
        ['article_id' => 1, 'slug_tag_id' => 1],
        ['article_id' => 1, 'slug_tag_id' => 2],
        ['article_id' => 2, 'slug_tag_id' => 2],
    ];
}

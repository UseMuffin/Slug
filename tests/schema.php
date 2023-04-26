<?php
return [
    [
        'table' => 'slug_articles',
        'columns' => [
            'id' => ['type' => 'integer'],
            'author_id' => ['type' => 'integer'],
            'title' => ['type' => 'string', 'null' => false],
            'sub_title' => ['type' => 'string', 'null' => false],
            'slug' => ['type' => 'string', 'null' => false],
            'created' => ['type' => 'datetime', 'null' => true],
            'modified' => ['type' => 'datetime', 'null' => true],
        ],
        'constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
    ],
    [
        'table' => 'slug_articles_tags',
        'columns' => [
            'id' => ['type' => 'integer'],
            'article_id' => ['type' => 'integer'],
            'slug_tag_id' => ['type' => 'integer'],
        ],
    ],
    [
        'table' => 'slug_authors',
        'columns' => [
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string', 'null' => false],
        ],
        'constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
    ],
    [
        'table' => 'slug_tags',
        'columns' => [
            'id' => ['type' => 'integer'],
            'namespace' => ['type' => 'string', 'length' => 255, 'null' => true],
            'slug' => ['type' => 'string', 'length' => 255],
            'name' => ['type' => 'string', 'length' => 320],
            'counter' => ['type' => 'integer', 'unsigned' => true, 'default' => 0, 'null' => true],
            'created' => ['type' => 'datetime', 'null' => true],
            'modified' => ['type' => 'datetime', 'null' => true],
        ],
        'constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
    ],
];

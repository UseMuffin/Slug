<?php
namespace Muffin\Slug\Test\TestCase\Model\Behavior;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

class SlugBehaviorTest extends TestCase
{
    public $fixtures = [
        'plugin.Muffin/Slug.Tags',
        'plugin.Muffin/Slug.Articles',
        'plugin.Muffin/Slug.ArticlesTags',
        'plugin.Muffin/Slug.Authors',
    ];

    public function setUp()
    {
        parent::setUp();

        $this->Tags = TableRegistry::get('Muffin/Slug.Tags', ['table' => 'slug_tags']);
        $this->Tags->setDisplayField('name');
        $this->Tags->addBehavior('Muffin/Slug.Slug');

        $this->Behavior = $this->Tags->behaviors()->Slug;
    }

    public function tearDown()
    {
        parent::tearDown();
        TableRegistry::clear();
        unset($this->Behavior, $this->Tags);
    }

    public function testInitialize()
    {
        $result = $this->Behavior->getConfig('displayField');
        $expected = 'name';
        $this->assertEquals($expected, $result);

        $result = $this->Behavior->getConfig('maxLength');
        $expected = 255;
        $this->assertEquals($expected, $result);
    }

    public function testImplementedEvents()
    {
        $result = $this->Behavior->implementedEvents();
        $expected = [
            'Model.buildValidator' => 'buildValidator',
            'Model.beforeSave' => 'beforeSave',
        ];
        $this->assertEquals($expected, $result);

        $implementedEvents = ['foo' => 'bar'];
        $this->Tags->removeBehavior('Slug');
        $this->Tags->addBehavior('Muffin/Slug.Slug', compact('implementedEvents'));

        $result = $this->Tags->behaviors()->Slug->implementedEvents();
        $expected = $implementedEvents;
        $this->assertEquals($expected, $result);
    }

    public function testBeforeSave()
    {
        $data = ['name' => 'foo'];
        $tag = $this->Tags->newEntity($data);

        $result = $this->Tags->save($tag)->slug;
        $expected = 'foo';
        $this->assertEquals($expected, $result);

        $tag = $this->Tags->newEntity($data);

        $result = $this->Tags->save($tag)->slug;
        $expected = 'foo-1';
        $this->assertEquals($expected, $result);
    }

    public function testBeforeSaveOnUpdate()
    {
        $data = ['name' => 'foo'];
        $tag = $this->Tags->newEntity($data);

        $result = $this->Tags->save($tag)->slug;
        $expected = 'foo';
        $this->assertEquals($expected, $result);

        $tag->name = 'bar';
        $result = $this->Tags->save($tag)->slug;
        $this->assertEquals($expected, $result);

        $this->Tags->behaviors()->Slug->setConfig('onUpdate', true);
        $tag->name = 'baz';
        $result = $this->Tags->save($tag)->slug;
        $expected = 'baz';
        $this->assertEquals($expected, $result);

        $tag->unsetProperty('name');
        $tag->namespace = 'foobar';
        $result = $this->Tags->save($tag)->slug;
        $this->assertEquals($expected, $result);
    }

    public function testBeforeSaveDirtyField()
    {
        $data = ['name' => 'foo', 'slug' => 'bar'];
        $tag = $this->Tags->newEntity($data);

        $result = $this->Tags->save($tag)->slug;
        $expected = 'bar';
        $this->assertEquals($expected, $result);

        $data = ['name' => 'baz', 'slug' => ''];
        $tag = $this->Tags->newEntity($data);

        $result = $this->Tags->save($tag)->slug;
        $expected = 'baz';
        $this->assertEquals($expected, $result);

        $this->Tags->behaviors()->Slug->setConfig('onDirty', true);

        $data = ['name' => 'I am nice', 'slug' => 'make ME Nice'];
        $tag = $this->Tags->newEntity($data);

        $result = $this->Tags->save($tag)->slug;
        $expected = 'make-me-nice';
        $this->assertEquals($expected, $result);

        $data = ['name' => 'Fooz', 'slug' => ''];
        $tag = $this->Tags->newEntity($data);

        $result = $this->Tags->save($tag)->slug;
        $expected = 'fooz';
        $this->assertEquals($expected, $result);

        $this->Tags->behaviors()->Slug->setConfig('onUpdate', true);

        $tag = $this->Tags->find()->where(['name' => 'I am nice'])->first();
        $tag->slug = 'I is NICE';

        $result = $this->Tags->save($tag)->slug;
        $expected = 'i-is-nice';
        $this->assertEquals($expected, $result);
    }

    /**
     * Make sure no slug is generated when `displayField` is empty.
     */
    public function testBeforeSaveEmptyField()
    {
        $this->Tags->removeBehavior('Slug');
        $this->Tags->addBehavior('Muffin/Slug.Slug', [
            'displayField' => 'namespace',
            'implementedEvents' => [
                'Model.beforeSave' => 'beforeSave',
            ],
        ]);

        $data = ['name' => 'foo'];
        $tag = $this->Tags->newEntity($data);

        $result = $this->Tags->save($tag)->slug;
        $expected = null;
        $this->assertEquals($expected, $result);
    }

    public function testSlug()
    {
        $result = $this->Behavior->slug('foo/bar');
        $expected = 'foo-bar';
        $this->assertEquals($expected, $result);

        $result = $this->Behavior->slug('foo/bar', '_');
        $expected = 'foo_bar';
        $this->assertEquals($expected, $result);

        $result = $this->Behavior->slug('admad\'s "double quote "');
        $expected = 'admads-double-quote';
        $this->assertEquals($expected, $result);
    }

    public function testSlugWithAssociatedTableField()
    {
        $Articles = TableRegistry::get('Muffin/Slug.Articles', ['table' => 'slug_articles']);
        $Authors = TableRegistry::get('Muffin/Slug.Authors', ['table' => 'slug_authors']);

        $Articles->belongsTo('Authors', ['className' => 'Muffin/Slug.Authors']);
        $Articles->addBehavior('Muffin/Slug.Slug', ['displayField' => ['author.name', 'title']]);

        $data = ['title' => 'foo', 'sub_title' => 'unused'];
        $article = $Articles->newEntity($data);
        $article->author = $Authors->get(1);

        $result = $Articles->slug($article);
        $expected = 'admad-foo';
        $this->assertEquals($expected, $result);
    }

    public function testBeforeSaveMultiField()
    {
        $Articles = TableRegistry::get('Muffin/Slug.Articles', ['table' => 'slug_articles']);
        $Articles->addBehavior('Muffin/Slug.Slug', ['displayField' => ['title', 'sub_title']]);

        $data = ['title' => 'foo', 'sub_title' => 'bar'];
        $article = $Articles->newEntity($data);

        $result = $Articles->save($article)->slug;
        $expected = 'foo-bar';
        $this->assertEquals($expected, $result);

        $data = ['title' => 'foo', 'sub_title' => 'bar'];
        $article = $Articles->newEntity($data);

        $result = $Articles->save($article)->slug;
        $expected = 'foo-bar-1';
        $this->assertEquals($expected, $result);

        $data = ['title' => 'foo', 'sub_title' => 'bar'];
        $article = $Articles->newEntity($data);

        $result = $Articles->save($article)->slug;
        $expected = 'foo-bar-2';

        $this->assertEquals($expected, $result);
        $data = ['title' => 'foo', 'sub_title' => 'bar-1'];
        $article = $Articles->newEntity($data);

        $result = $Articles->save($article)->slug;
        $expected = 'foo-bar-1-1';
        $this->assertEquals($expected, $result);

        $entity = new Entity(['title' => 'ad', 'sub_title' => 'mad']);
        $result = $Articles->slug($entity);
        $expected = 'ad-mad';
        $this->assertEquals($expected, $result);
    }

    public function testBeforeSaveMultiWithOptionalField()
    {
        $Articles = TableRegistry::get('Muffin/Slug.Articles', ['table' => 'slug_articles']);
        $Articles->addBehavior('Muffin/Slug.Slug', [
            'displayField' => ['title', 'sub_title'],
            'implementedEvents' => [
                'Model.beforeSave' => 'beforeSave',
            ],
        ]);

        $data = ['title' => 'foo', 'sub_title' => ''];
        $article = $Articles->newEntity($data);

        $result = $Articles->save($article)->slug;
        $expected = 'foo';
        $this->assertEquals($expected, $result);
    }

    public function testBeforeSaveSlugGenerationWithAssociatedTableField()
    {
        $Articles = TableRegistry::get('Muffin/Slug.Articles', ['table' => 'slug_articles']);
        $Authors = TableRegistry::get('Muffin/Slug.Authors', ['table' => 'slug_authors']);

        $Articles->belongsTo('Authors', ['className' => 'Muffin/Slug.Authors']);
        $Articles->addBehavior('Muffin/Slug.Slug', ['displayField' => ['author.name', 'title']]);

        $data = ['title' => 'foo', 'sub_title' => 'unused'];
        $article = $Articles->newEntity($data);
        $article->author = $Authors->get(1);

        $result = $Articles->save($article)->slug;
        $expected = 'admad-foo';
        $this->assertEquals($expected, $result);
    }

    public function testCustomSlugField()
    {
        $Articles = TableRegistry::get('Muffin/Slug.Articles', ['table' => 'slug_articles']);
        $Articles->addBehavior('Muffin/Slug.Slug', [
            'displayField' => 'title',
            'field' => 'sub_title',
        ]);

        $data = ['title' => 'foo', 'slug' => ''];
        $tag = $Articles->newEntity($data);

        $result = $Articles->save($tag)->sub_title;
        $expected = 'foo';
        $this->assertEquals($expected, $result);
    }

    public function testMaxLength()
    {
        $this->Tags->removeBehavior('Slug');
        $this->Tags->addBehavior('Muffin/Slug.Slug');

        $data = ['name' => 'This is an extremely long title of more than 255 characters. This is an extremely long title of more than 255 characters. This is an extremely long title of more than 255 characters. This is an extremely long title of more than 255 characters. This is an extremely long title of more than 255 characters.'];
        $tag = $this->Tags->newEntity($data);

        $result = $this->Tags->save($tag)->slug;
        $expected = 'this-is-an-extremely-long-title-of-more-than-255-characters-this-is-an-extremely-long-title-of-more-than-255-characters-this-is-an-extremely-long-title-of-more-than-255-characters-this-is-an-extremely-long-title-of-more-than-255-characters-this-is-an-extr';
        $this->assertEquals($expected, $result);

        $tag = $this->Tags->newEntity($data);

        $result = $this->Tags->save($tag)->slug;
        $expected = 'this-is-an-extremely-long-title-of-more-than-255-characters-this-is-an-extremely-long-title-of-more-than-255-characters-this-is-an-extremely-long-title-of-more-than-255-characters-this-is-an-extremely-long-title-of-more-than-255-characters-this-is-an-ex-1';
        $this->assertEquals($expected, $result);
    }

    public function testCustomMaxLength()
    {
        $this->Tags->removeBehavior('Slug');
        $this->Tags->addBehavior('Muffin/Slug.Slug', [
            'maxLength' => 10,
        ]);

        $data = ['name' => 'This tag name is longer than the configured maxLength.'];
        $tag = $this->Tags->newEntity($data);

        $result = $this->Tags->save($tag)->slug;
        $expected = 'this-tag-n';
        $this->assertEquals($expected, $result);

        $data = ['name' => 'This tag name is not the same, but should cause a duplicate slug.'];
        $tag = $this->Tags->newEntity($data);

        $result = $this->Tags->save($tag)->slug;
        $expected = 'this-tag-1';
        $this->assertEquals($expected, $result);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSlugThrowsInvalidArgumentException()
    {
        $tag = $this->Tags->newEntity();
        $this->Behavior->slug($this->Tags->newEntity([]));
    }

    public function testSlugUnchanged()
    {
        $data = ['name' => 'foo', 'slug' => 'my-slug'];
        $tag = $this->Tags->newEntity($data);

        $result = $this->Tags->save($tag)->slug;
        $expected = 'my-slug';
        $this->assertEquals($expected, $result);

        $tag = $this->Tags->find('slugged', ['slug' => 'dark-color'])->first();
        $tag = $this->Tags->patchEntity($tag, ['name' => 'new name']);
        $result = $this->Tags->save($tag)->slug;
        $expected = 'dark-color';
        $this->assertEquals($expected, $result);
    }

    public function testSavingEntityWithErrors()
    {
        $data = ['name' => 'foo'];
        $tag = $this->Tags->newEntity($data);
        $tag->setError('name', ['error']);

        $result = $this->Tags->save($tag);
        $this->assertFalse($result);
        $this->assertNull($tag->get('slug'));
    }

    public function testFinder()
    {
        $result = $this->Tags->find('slugged', ['slug' => 'dark-color'])
            ->select(['slug', 'name'])
            ->first()
            ->toArray();

        $expected = [
            'slug' => 'dark-color',
            'name' => 'Dark Color',
        ];
        $this->assertEquals($expected, $result);

        $query = $this->Tags->find('slugged', ['slug' => 0]);
        $this->assertInstanceOf('Cake\ORM\Query', $query);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The `slug` key is required by the `slugged` finder
     */
    public function testFinderException()
    {
        $result = $this->Tags->find('slugged')->first();
    }

    public function testContainSluggedTables()
    {
        TableRegistry::get('Muffin/Slug.Articles', ['table' => 'slug_articles']);

        $this->Tags->belongsToMany('Muffin/Slug.Articles', [
            'foreignKey' => 'slug_tag_id',
            'joinTable' => 'slug_articles_tags',
            'through' => TableRegistry::get('Muffin/Slug.ArticlesTags', ['table' => 'slug_articles_tags']),
        ]);

        $result = $this->Tags->find('slugged', ['slug' => 'color'])
            ->contain(['Articles'])
            ->first()
            ->toArray();

        $this->assertArrayHasKey('articles', $result);
        $this->assertEquals(1, $result['id']);
        $this->assertCount(1, $result['articles']);
    }

    public function testSluggerConfig()
    {
        $this->Behavior->setConfig('slugger', [
            'className' => '\Muffin\Slug\Slugger\CakeSlugger',
            'lowercase' => false,
        ]);

        $this->assertFalse($this->Behavior->slugger()->config['lowercase']);
    }

    public function testCallableForSlugger()
    {
        $this->Behavior->setConfig('slugger', function ($string, $separator) {
            return strtolower($string);
        });

        $result = $this->Behavior->slug('FOO');
        $this->assertEquals('foo', $result);
    }

    public function testCallableForUnique()
    {
        $this->Behavior->setConfig('scope', function ($entity) {
            return ['namespace' => $entity->namespace];
        });

        $newEntity = $this->Tags->newEntity(['namespace' => 'foo', 'name' => 'Color']);

        $this->assertEquals('color', $this->Tags->slug($newEntity));
    }
}

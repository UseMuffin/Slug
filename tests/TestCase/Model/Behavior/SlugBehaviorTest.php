<?php
namespace Muffin\Slug\Test\TestCase\Model\Behavior;

use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Muffin\Slug\Model\Behavior\SlugBehavior;

class SlugBehaviorTest extends TestCase
{
    public $fixtures = [
        'plugin.Muffin/Slug.Tags',
        'plugin.Muffin/Slug.Articles',
        'plugin.Muffin/Slug.ArticlesTags'
    ];

    public function setUp()
    {
        parent::setUp();

        $this->Tags = TableRegistry::get('Muffin/Slug.Tags', ['table' => 'slug_tags']);
        $this->Tags->displayField('name');
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
        $result = $this->Behavior->config('displayField');
        $expected = 'name';
        $this->assertEquals($expected, $result);

        $result = $this->Behavior->config('maxLength');
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

    public function testSlug()
    {
        $result = $this->Behavior->slug('foo/bar');
        $expected = 'foo-bar';
        $this->assertEquals($expected, $result);

        $result = $this->Behavior->slug('foo/bar', '_');
        $expected = 'foo_bar';
        $this->assertEquals($expected, $result);
    }

    public function testBeforeSaveMultiField()
    {
        $Articles = TableRegistry::get('Muffin/Slug.Articles', ['table' => 'slug_articles']);
        $Articles->addBehavior('Muffin/Slug.Slug', ['displayField' => ['title', 'sub_title']]);

        $data = ['title' => 'foo', 'sub_title' => 'bar'];
        $tag = $Articles->newEntity($data);

        $result = $Articles->save($tag)->slug;
        $expected = 'foo-bar';
        $this->assertEquals($expected, $result);

        $data = ['title' => 'foo', 'sub_title' => 'bar'];
        $tag = $Articles->newEntity($data);

        $result = $Articles->save($tag)->slug;
        $expected = 'foo-bar-1';
        $this->assertEquals($expected, $result);

        $data = ['title' => 'foo', 'sub_title' => 'bar-1'];
        $tag = $Articles->newEntity($data);

        $result = $Articles->save($tag)->slug;
        $expected = 'foo-bar-1-1';
        $this->assertEquals($expected, $result);

        $entity = new Entity(['title' => 'ad', 'sub_title' => 'mad']);
        $result = $Articles->slug($entity);
        $expected = 'ad-mad';
        $this->assertEquals($expected, $result);
    }

    public function testCustomSlugField()
    {
        $Articles = TableRegistry::get('Muffin/Slug.Articles', ['table' => 'slug_articles']);
        $Articles->addBehavior('Muffin/Slug.Slug', [
            'displayField' => 'title',
            'field' => 'sub_title'
        ]);

        $data = ['title' => 'foo', 'slug' => ''];
        $tag = $Articles->newEntity($data);

        $result = $Articles->save($tag)->sub_title;
        $expected = 'foo';
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
        $tag->errors('name', ['error']);

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
            'through' => TableRegistry::get('Muffin/Slug.ArticlesTags', ['table' => 'slug_articles_tags'])
        ]);

        $result = $this->Tags->find('slugged', ['slug' => 'color'])
            ->contain(['Articles'])
            ->first()
            ->toArray();

        $this->assertArrayHasKey('articles', $result);
        $this->assertEquals(1, $result['id']);
        $this->assertCount(1, $result['articles']);
    }
}

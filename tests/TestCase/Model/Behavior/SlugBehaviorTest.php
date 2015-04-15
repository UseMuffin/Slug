<?php
namespace Muffin\Slug\Test\TestCase\Model\Behavior;

use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Muffin\Slug\Model\Behavior\SlugBehavior;

class SlugBehaviorTest extends TestCase
{
    public $fixtures = [
        'Muffin/Slug.Tags' => 'plugin.muffin/slug.tags',
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

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testInitializeThrowsUnexpectedValueException()
    {
        $table = $this->getMock('Cake\ORM\Table');
        new SlugBehavior($table, ['slugger' => 'Foo\Bar']);
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
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSlugThrowsInvalidArgumentException()
    {
        $tag = $this->Tags->newEntity();
        $this->Behavior->slug($this->Tags->newEntity([]));
    }
}

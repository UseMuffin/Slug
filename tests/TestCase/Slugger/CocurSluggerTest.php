<?php
namespace Muffin\Slug\Test\TestCase\Slugger;

use Cake\TestSuite\TestCase;
use Muffin\Slug\Slugger\CocurSlugger;

class CocurSluggerTest extends TestCase
{

    public function testSlug()
    {
        $slugger = new CocurSlugger();

        $this->assertEquals('hello-world', $slugger->slug('Hello World!'));

        $slugger->config['lowercase'] = false;
        $this->assertEquals('Hello-World', $slugger->slug('Hello World!'));
    }
}
<?php

namespace App\Tests\Api;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Post;
use Doctrine\ORM\Tools\SchemaTool;

class PostsTest extends ApiTestCase
{
    public function setUp(): void
    {
        $kernel = self::bootKernel();
        $manager = $kernel->getContainer()->get('doctrine')->getManager();
        $schema = new SchemaTool($manager);

        $schema->dropSchema($manager->getMetadataFactory()->getAllMetadata());
        $schema->createSchema($manager->getMetadataFactory()->getAllMetadata());


        parent::setUp();
    }

    public function testGetPosts(): void
    {
        $kernel = self::bootKernel();
        $manager = $kernel->getContainer()->get('doctrine')->getManager();



        $post = new Post();

        $post->setTitle('title');
        $post->setContent('content');

        $manager->persist($post);
        $manager->flush();


        static::createClient()->request('GET', '/api/posts',[
            'headers' => [
                'Accept' => 'application/json'
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([['title' => 'title', 'content' => 'content']]);
    }
}

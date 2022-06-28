<?php

namespace App\Tests\Api;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use Doctrine\ORM\Tools\SchemaTool;

class AuthenticationTest extends ApiTestCase
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

    public function testIndex(): void
    {
        $response = static::createClient()->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([]);
    }

    public function testRegistrationSuccessful(): void
    {
        static::createClient()->request(
            'POST',
            '/register',
            [
                'body' => json_encode([
                    'email' => 'user@gmail.com',
                    'password' => '12345678'
                ])
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['user' => []]);
    }

    public function testRegistrationWithWrongEmail(): void
    {
        static::createClient()->request(
            'POST',
            '/register',
            [
                'body' => json_encode([
                    'email' => 'user',
                    'password' => '12345678'
                ])
            ]
        );

        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonEquals(['errors' => ['[email]' => 'This value is not a valid email address.']]);
    }

    public function testLoginSuccessful(): void
    {
        static::createClient()->request(
            'POST',
            '/register',
            [
                'body' => json_encode([
                    'email' => 'user@gmail.com',
                    'password' => '12345678'
                ])
            ]
        );

        $this->assertResponseIsSuccessful();

        static::createClient()->request(
            'POST',
            '/login',
            [
                'headers' => [
                    'Content-type' => 'application/json',
                ],
                'body' => json_encode([
                    'email' => 'user@gmail.com',
                    'password' => '12345678'
                ])
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals(['user' => 'user@gmail.com']);
    }

    public function testLoginWithWrongPassword(): void
    {
        static::createClient()->request(
            'POST',
            '/register',
            [
                'body' => json_encode([
                    'email' => 'user@gmail.com',
                    'password' => '12345678'
                ])
            ]
        );

        $this->assertResponseIsSuccessful();

        static::createClient()->request(
            'POST',
            '/login',
            [
                'headers' => [
                    'Content-type' => 'application/json',
                ],
                'body' => json_encode([
                    'email' => 'user@gmail.com',
                    'password' => '123458'
                ])
            ]
        );

        $this->assertResponseStatusCodeSame(401);
        $this->assertJsonEquals(['error' => 'Invalid credentials.']);
    }
}

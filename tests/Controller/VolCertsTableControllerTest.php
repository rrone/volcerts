<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class VolCertsTableControllerTest extends WebTestCase
{
    public function testRoot()
    {
        $client = static::createClient();
        $client->followRedirects();
        $client->request('GET', '/');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testId()
    {
        $client = static::createClient();

        $client->request('GET', '/id/97815888');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testIds()
    {
        $client = static::createClient();

        $client->request('GET', '/id/97815888,96383440');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }


}

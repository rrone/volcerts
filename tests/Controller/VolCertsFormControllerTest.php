<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class VolCertTableControllerTest extends WebTestCase
{
    public function testRoute()
    {
        $client = static::createClient();

        $client->request('GET', '/ch');
        $client->followRedirect();

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        var_dump($client->getRequest()->getUri());
    }

}

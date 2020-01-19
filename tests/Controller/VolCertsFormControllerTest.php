<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class VolCertTableControllerTest extends WebTestCase
{
    public function testRoute()
    {
        $client = static::createClient();

        $client->request('GET', '/ch');

        var_dump($client->getRequest());die();

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

}

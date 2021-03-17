<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class VolCertsFormControllerTest extends WebTestCase
{
    public function testRoute()
    {
        $client = self::createClient();

        $client->request('GET', '/ch');

        $this->assertEquals(302, $client->getResponse()->getStatusCode());

    }

}

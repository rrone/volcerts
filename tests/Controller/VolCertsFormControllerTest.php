<?php

namespace Tests\Controller;

use Tests\Abstracts\WebTestCasePlus;

class VolCertsFormControllerTest extends WebTestCasePlus
{
    /**
     * @return void
     */
    public function testRoute()
    {
        $this->client->request('GET', '/ch');

        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());

    }

}

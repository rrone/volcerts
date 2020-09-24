<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class VolCertsTableControllerTest extends WebTestCase
{

    const VAR = '/var/www/volcerts.vhx.cloud/tests/var';

    public function testRoot()
    {
        $client = static::createClient();
        $client->followRedirects();
        $client->request('GET', '/response');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testEmptyId()
    {
        $client = static::createClient();

        $client->request('GET', '/id/');

        $this->assertEquals(301, $client->getResponse()->getStatusCode());
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

    public function testCSVUpload()
    {
        $client = static::createClient();

        copy(self::VAR . '/files/Book.3.csv', self::VAR . '/uploads/Book.3.csv');
        $file = new UploadedFile(
            self::VAR .'/uploads/Book.3.csv',
            'Book.3.csv',
            'text/csv',
            null
        );

        $client->request(
            'POST',
            '/ch',
            [],
            ['uploadFilename' => $file]
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Upload another file', $client->getResponse()->getContent());
    }

    public function testXLSUpload()
    {
        $client = static::createClient();

        copy(self::VAR . '/files/Book.3.xls', self::VAR . '/uploads/Book.3.xls');
        $file = new UploadedFile(
            self::VAR .'/uploads/Book.3.xls',
            'Book.3.xls',
            'application/vnd.ms-excel',
            null
        );

        $client->request(
            'POST',
            '/ch',
            [],
            ['uploadFilename' => $file]
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Upload another file', $client->getResponse()->getContent());
    }

    public function testXLSXUpload()
    {
        $client = static::createClient();

        copy(self::VAR . '/files/Book.3.xlsx', self::VAR . '/uploads/Book.3.xlsx');
        $file = new UploadedFile(
            self::VAR .'/uploads/Book.3.xlsx',
            'Book.3.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null
        );

        $client->request(
            'POST',
            '/ch',
            [],
            ['uploadFilename' => $file]
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Upload another file', $client->getResponse()->getContent());
    }


}

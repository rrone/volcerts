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
        $this->assertStringContainsString('"AYSOID":97815888', $client->getResponse()->getContent());
        $this->assertStringContainsString('"FullName":"Frederick Roberts"', $client->getResponse()->getContent());
        $this->assertStringContainsString('"CoachCertDate":"2018-08-29"', $client->getResponse()->getContent());
        $this->assertStringContainsString('"CoachCertDesc":"Z-Online U-10 Coach"', $client->getResponse()->getContent());
        $this->assertStringContainsString('"InstEvalCertDate":"2006-02-10"', $client->getResponse()->getContent());
        $this->assertStringContainsString('"InstEvalCertDesc":"Referee Instructor Evaluator"', $client->getResponse()->getContent());
        $this->assertStringContainsString('"InstCertDate":"2012-07-01"', $client->getResponse()->getContent());
        $this->assertStringContainsString('"InstCertDesc":"National Referee Instructor"', $client->getResponse()->getContent());
        $this->assertStringContainsString('"AssessorCertDate":"2004-04-09"', $client->getResponse()->getContent());
        $this->assertStringContainsString('"AssessorCertDesc":"National Referee Assessor"', $client->getResponse()->getContent());
        $this->assertStringContainsString('"RefCertDate":"2004-04-14"', $client->getResponse()->getContent());
        $this->assertStringContainsString('"RefCertDesc":"National Referee"', $client->getResponse()->getContent());
        $this->assertStringContainsString('"SCADate":"2020-06-17"', $client->getResponse()->getContent());
        $this->assertStringContainsString('"CDCDate":"2018-11-29"', $client->getResponse()->getContent());
        $this->assertStringContainsString('"SafeHavenDate":"2020-07-17"', $client->getResponse()->getContent());
        $this->assertStringContainsString('"MY":"MY202', $client->getResponse()->getContent());
        $this->assertStringContainsString('"SAR":"1\/D\/0092"', $client->getResponse()->getContent());
        $this->assertStringContainsString('"Type":"Adult"', $client->getResponse()->getContent());
        $this->assertStringContainsString('"DataSource":"e3"', $client->getResponse()->getContent());
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

    public function testBadUpload()
    {
        $client = static::createClient();

        copy(self::VAR . '/files/Book.3.bad.csv', self::VAR . '/uploads/Book.3.bad.csv');
        $file = new UploadedFile(
            self::VAR .'/uploads/Book.3.bad.csv',
            'Book.3.bad.csv',
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

<?php

namespace Tests\Service;

use App\Service\FileUploader;
use App\Service\VolCerts;
use App\Service\VolCertsTable;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

define('ROOT_DIR', realpath(__DIR__ . "/../../"));

/**
 * @method assertNotNull(FileUploader $fileUploader)
 * @method assertFileExists(string $fileName)
 */
class ServiceTest extends WebTestCase
{
    public function setUp(): void
    {
        global $kernel;

        parent::setUp();

        $this->client = static::createClient(
            [
                'environment' => 'test',
                'debug' => true,
            ]
        );

        $this->client->catchExceptions(false);

        $kernel = $this->client->getKernel();

        $this->c = self::getContainer();
    }


    public function testServices()
    {
        $src = ROOT_DIR . '/var/csv';
        $dest = ROOT_DIR . '/tests/var/files';
        copy($src . '/Book.50.csv', $dest . '/Book.50.csv');
        copy($src . '/Book.50.xls', $dest . '/Book.50.xls');
        copy($src . '/Book.50.xlsx', $dest . '/Book.50.xlsx');

        $fileUploader = new FileUploader(ROOT_DIR);
        $this->assertNotNull($fileUploader);

        $vcTable = new VolCertsTable(new VolCerts());

        $request = Request::create(
            '/ch',
            'POST'
        );

        try {
            $html = $vcTable->renderView();
            $this->assertStringContainsString('<b>ERROR: <em>', $html);
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        $csvFile = new UploadedFile($dest . '/Book.50.csv', 'Book.50.csv', "text/csv", null, true);
        $request->files->add(['uploadFilename' => $csvFile]);
        $file = $fileUploader->upload($request);
        $this->assertFileExists($file->fileName);

        $vcTable->retrieveVolCertData($file->fileName);
        $vcTable->getDataOut(true);
        if (file_exists($file->fileName)) {
            unlink($file->fileName);
        }
        try {
            $html = $vcTable->renderView();
        } catch (Exception $e) {
            $html = '<p>' . $e->getMessage() . '</p>';
        }
        $this->assertStringContainsString('<table id="vol_certs" class="display">', $html);
        $this->assertGreaterThan(50, $vcTable->getDataIn());

        $xlsFile = new UploadedFile($dest . '/Book.50.xls', 'Book.50.xls', "application/vnd.ms-excel", null, true);
        $request->files->add(['uploadFilename' => $xlsFile]);
        $file = $fileUploader->upload($request);
        $this->assertFileExists($file->fileName);

        $vcTable->retrieveVolCertData($file->fileName);
        $vcTable->getDataOut(true);
        if (file_exists($file->fileName)) {
            unlink($file->fileName);
        }
        try {
            $html = $vcTable->renderView();
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        $this->assertStringContainsString('<table id="vol_certs" class="display">', $html);
        $this->assertGreaterThan(50, $vcTable->getDataIn());

        $xlsxFile = new UploadedFile(
            $dest . '/Book.50.xlsx',
            'Book.50.xlsx',
            "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            null,
            true
        );
        $request->files->add(['uploadFilename' => $xlsxFile]);
        $file = $fileUploader->upload($request);
        $this->assertFileExists($file->fileName);

        $vcTable->retrieveVolCertData($file->fileName);
        $vcTable->getDataOut(false);

        try {
            $html = $vcTable->renderView();
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        $this->assertStringContainsString('<table id="vol_certs" class="display">', $html);
        $this->assertGreaterThan(50, $vcTable->getDataIn());
        if (isset($vcTable->getVolCertData()[94015164])) {
            $this->assertStringContainsString(
                '94015164', $vcTable->getVolCertData()[94015164]['AYSOID']
            );
        }

        $vcTable->retrieveVolCertData($file->fileName);
        $vcTable->getDataOut(true);

        try {
            $html = $vcTable->renderView();
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        $this->assertStringContainsString('<table id="vol_certs" class="display">', $html);
        $this->assertGreaterThan(50, $vcTable->getDataIn());
        if (isset($vcTable->getVolCertData()[94015164])) {
            $this->assertStringContainsString(
                '<th>Full Name</th>', $html
            );
        }

        if (file_exists($file->fileName)) {
            unlink($file->fileName);
        }

    }

}

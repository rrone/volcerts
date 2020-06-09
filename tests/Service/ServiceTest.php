<?php

namespace App\Tests\Service;

use App\Service\FileUploader;
use App\Service\VolCerts;
use App\Service\VolCertsTable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

define('ROOT_DIR', realpath(__DIR__."/../../"));

/**
 * @method assertNotNull(FileUploader $fileUploader)
 * @method assertFileExists(string $fileName)
 */
class ServiceTest extends TestCase
{
    private $appVersion;
    private $projectDir;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->appVersion = 'test 2020.06.09';
    }

    public function testServices()
    {
        $src = ROOT_DIR.'/var/csv';
        $dest = ROOT_DIR.'/tests/var/files';
        copy($src.'/Book.50.csv', $dest.'/Book.50.csv');
        copy($src.'/Book.50.xls', $dest.'/Book.50.xls');
        copy($src.'/Book.50.xlsx', $dest.'/Book.50.xlsx');

        $fileUploader = new FileUploader(ROOT_DIR);
        $this->assertNotNull($fileUploader);

        $vcTable = new VolCertsTable(new VolCerts());

        $request = Request::create(
            '/ch',
            'POST'
        );

        $csvFile = new UploadedFile($dest.'/Book.50.csv', 'Book.50.csv', "text/csv", null, true);
        $request->files->add(['uploadFilename' => $csvFile]);
        $fileName = $fileUploader->upload($request);
        $this->assertFileExists($fileName);

        $vcTable->setMerge(true);
        $content = $vcTable->retrieveVolCertData($fileName);
        if (file_exists($fileName)) {
            unlink($fileName);
        }
        try {
            $html = $vcTable->renderView($content);
        } catch (\Exception $e) {
        }
        $this->assertStringContainsString('<table id="vol_certs" class="display">', $html);
        $this->assertGreaterThan(50, $vcTable->getDataIn());

        $xlsFile = new UploadedFile($dest.'/Book.50.xls', 'Book.50.xls', "application/vnd.ms-excel", null, true);
        $request->files->add(['uploadFilename' => $xlsFile]);
        $fileName = $fileUploader->upload($request);
        $this->assertFileExists($fileName);

        $vcTable->setMerge(true);
        $content = $vcTable->retrieveVolCertData($fileName);
        if (file_exists($fileName)) {
            unlink($fileName);
        }
        try {
            $html = $vcTable->renderView($content);
        } catch (\Exception $e) {
        }
        $this->assertStringContainsString('<table id="vol_certs" class="display">', $html);
        $this->assertGreaterThan(50, $vcTable->getDataIn());

        $xlsxFile = new UploadedFile(
            $dest.'/Book.50.xlsx',
            'Book.50.xlsx',
            "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            null,
            true
        );
        $request->files->add(['uploadFilename' => $xlsxFile]);
        $fileName = $fileUploader->upload($request);
        $this->assertFileExists($fileName);

        $vcTable->setMerge(true);
        $content = $vcTable->retrieveVolCertData($fileName);
        if (file_exists($fileName)) {
            unlink($fileName);
        }
        try {
            $html = $vcTable->renderView($content);
        } catch (\Exception $e) {
        }
        $this->assertStringContainsString('<table id="vol_certs" class="display">', $html);

        $this->assertGreaterThan(50, $vcTable->getDataIn());
        $this->assertStringContainsString(
            '94015164', $vcTable->getVolCertData()[94015164]['AYSOID']
        );

    }

}

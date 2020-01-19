<?php

namespace App\Tests\Service;

use App\Service\FileUploader;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @method assertNotNull(FileUploader $fileUploader)
 * @method assertFileExists(string $filename)
 */
class ServiceTest extends TestCase
{
    public function testFileUpload()
    {
        define('ROOT_DIR', realpath(__DIR__."/../../"));

        $src = ROOT_DIR.'/var/csv';
        $dest = ROOT_DIR.'/tests/var/files';
        copy($src.'/Book.50.csv', $dest.'/Book.50.csv');
        copy($src.'/Book.50.xls', $dest.'/Book.50.xls');
        copy($src.'/Book.50.xlsx', $dest.'/Book.50.xlsx');

        $uploads = ROOT_DIR.'/var/uploads';
        $fileUploader = new FileUploader($uploads);
        $this->assertNotNull($fileUploader);

        $csvFile = new UploadedFile( $dest.'/Book.50.csv', 'Book.50.csv', "text/csv", null, true);
        $filename = $fileUploader->upload($csvFile);
        $this->assertFileExists($filename);

        $xlsFile = new UploadedFile( $dest.'/Book.50.xls', 'Book.50.xls', "application/vnd.ms-excel", null, true);
        $filename = $fileUploader->upload($xlsFile);
        $this->assertFileExists($filename);

        $xlsxFile = new UploadedFile( $dest.'/Book.50.xlsx', 'Book.50.xlsx', "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", null, true);
        $filename = $fileUploader->upload($xlsxFile);
        $this->assertFileExists($filename);

    }

}

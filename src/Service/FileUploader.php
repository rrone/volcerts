<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;

class FileUploader
{
    /**
     * @var
     */
    private $projectDir;

    /**
     * FileUploader constructor
     * @param $projectDir
     */
    public function __construct($projectDir)
    {
        $this->projectDir = $projectDir;

    }

    /**
     * @param Request $request
     * @return string
     */
    public function upload(Request $request)
    {
        $file = $request->files->get('uploadFilename');

        if(is_null($file)){
            return null;
        }

        $inputFileType = $file->getClientOriginalExtension();
        $fileName =  uniqid(rand(), true) . '.' . $inputFileType;

        try {
            $file->move($this->getTargetDirectory(), $fileName);
        } catch (FileException $e) {
            // ... handle exception if something happens during file upload
        }

        return $this->getTargetDirectory() . '/' . $fileName;
    }

    /**
     * @return mixed
     */
    public function getTargetDirectory()
    {
        return $this->projectDir . '/var/uploads';
    }
}
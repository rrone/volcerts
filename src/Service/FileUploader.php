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
     * @return object|null
     */
    public function upload(Request $request): ?object
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

        return (object) array(
            'fileName' => $this->getTargetDirectory() . '/' . $fileName,
            'originalName' => $file->getClientOriginalName()
        );
    }

    /**
     * @return string
     */
    public function getTargetDirectory(): string
    {
        return $this->projectDir . '/var/uploads';
    }
}
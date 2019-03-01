<?php

namespace App\Controller;

use App\Service\FileUploader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\VolCertsTable;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Controller\VolCertsFormController;

class VolCertTableController extends AbstractController
{
    /**
     * @var VolCertsTable $volCertsTable
     */
    private $volCertsTable;

    /**
     * @var string $appVersion
     */
    private $appVersion;

    /** @var FileUploader $fileUploader */
    private $fileUploader;

    /**
     * @var RequestStack $reqestStack
     */
    private $requestStack;

    /**
     * VolCertTableController constructor
     * @param VolCertsTable $volCertsTable
     * @param string $appVersion
     * @param FileUploader $fileUploader
     * @param RequestStack $requestStack
     */
    public function __construct(
        VolCertsTable $volCertsTable,
        string $appVersion,
        FileUploader $fileUploader,
        RequestStack $requestStack
        )
    {
        $this->volCertsTable = $volCertsTable;
        $this->appVersion = $appVersion;
        $this->fileUploader = $fileUploader;
        $this->requestStack = $requestStack;
    }

    /**
     * @Route("/ch", name="app_ch")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function index()
    {
        $request = $this->requestStack->getCurrentRequest();

        if(!$request->isMethod('POST')) {

            return $this->redirect('/');

        }

        $file = $request->files->get('csv_file');
        if(is_null($file)){

            return $this->redirect('/');

        }

        $fileName = $this->fileUploader->upload($file);

        $content = $this->volCertsTable->retrieveVolCertData($fileName);
        $html = $this->volCertsTable->renderView($content);
        $response = $this->render('view.html.twig', [
            'table' => $html,
            'appVersion' => $this->appVersion
        ]);

        unlink($fileName);

        return $response;

    }

}
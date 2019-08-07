<?php

namespace App\Controller;

use App\Service\FileUploader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\VolCertsTable;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Exception;

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
     * @var Request $reqest
     */
    private $request;

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
        $this->request = $requestStack->getCurrentRequest();;
    }

    /**
     * @Route("/ch", name="app_ch")
     * @return RedirectResponse | Response
     * @throws Exception
     */
    public function index()
    {
        if(!$this->request->isMethod('POST')) {

            return $this->redirect('/');

        }

        $file = $this->request->files->get('uploadFilename');
        if(is_null($file)){

            return $this->redirect('/');

        }

        $fileName = $this->fileUploader->upload($file);

        $title = preg_replace('/\\.[^.\\s]{3,4}$/', '', $file->getClientOriginalName());

        $content = $this->volCertsTable->retrieveVolCertData($fileName);
        $html = $this->volCertsTable->renderView($content);
        $response = $this->render('view.html.twig', [
            'title' => $title,
            'table' => $html,
            'appVersion' => $this->appVersion
        ]);

        unlink($fileName);

        return $response;

    }

}
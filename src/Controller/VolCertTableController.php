<?php

namespace App\Controller;

use App\Service\FileUploader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\VolCertsTable;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;

class VolCertTableController extends AbstractController
{
    /**
     * @var VolCertsTable $volCertsEntity
     */
    private $volCertsEntity;

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
     * ChromeHeadlessController constructor
     * @param VolCertsTable $volCertsEntity
     * @param string $appVersion
     * @param FileUploader $fileUploader
     * @param RequestStack $requestStack
     */
    public function __construct(
        VolCertsTable $volCertsEntity,
        string $appVersion,
        FileUploader $fileUploader,
        RequestStack $requestStack
        )
    {
        $this->volCertsEntity = $volCertsEntity;
        $this->appVersion = $appVersion;
        $this->fileUploader = $fileUploader;
        $this->requestStack = $requestStack;
    }

    /**
     * @Route("/ch", name="app_ch")
     * @throws \Exception
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function index()
    {
        $request = $this->requestStack->getCurrentRequest();


        if(!$request->isMethod('POST')) {
            $response = $this->redirect('/');

            return $response;
        }

        $file = $request->files->get('csv_file');
        if(is_null($file)){
            $response = $this->redirect('/');

            return $response;
        }

        $this->fileUploader->upload($file);


        $content = $this->volCertsEntity->retrieveVolCertData();
        $html = $this->volCertsEntity->renderView($content);
        $response = $this->render('view.html.twig', [
            'table' => $html,
            'appVersion' => $this->appVersion
        ]);

        return $response;

    }

}
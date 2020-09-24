<?php

namespace App\Controller;

use App\Service\FileUploader;
use App\Service\VolCerts;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\VolCertsTable;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Exception;

class VolCertsTableController extends AbstractController
{
    /**
     * @var VolCerts $volCerts
     */
    private $volCerts;

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
     * VolCertsTableController constructor
     * @param VolCerts $volCerts
     * @param VolCertsTable $volCertsTable
     * @param string $appVersion
     * @param FileUploader $fileUploader
     * @param RequestStack $requestStack
     */
    public function __construct(
        VolCerts $volCerts,
        VolCertsTable $volCertsTable,
        $appVersion,
        FileUploader $fileUploader,
        RequestStack $requestStack
        )
    {
        $this->volCerts = $volCerts;
        $this->volCertsTable = $volCertsTable;
        $this->appVersion = $appVersion;
        $this->fileUploader = $fileUploader;
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * @Route("/ch", name="response")
     * @return RedirectResponse | Response
     * @throws Exception
     */
    public function index()
    {
        if(!$this->request->isMethod('POST')) {
            return $this->home();
        }

        $file = $this->fileUploader->upload($this->request);
        if(is_null($file)) {
            return $this->home();
        }

        if(!file_exists($file->fileName)) {
            return $this->home();
        }

        $title = preg_replace('/\\.[^.\\s]{3,4}$/', '', $file->originalName);
        $this->volCertsTable->setMerge(!is_null($this->request->get('merge')));
        $content = $this->volCertsTable->retrieveVolCertData($file->fileName);

        unlink($file->fileName);

        $html = $this->volCertsTable->renderView($content);

        return $this->render('view.html.twig', [
            'title' => $title,
            'table' => $html,
            'appVersion' => $this->appVersion
        ]);

    }

    /**
     * @Route("/id/{ids}", name="app_get")
     * @param string|null $id
     * @return JsonResponse
     */
    public function get(string $id = null): object
    {
        $ids = explode(',', $this->request->get('ids'));

        if(empty($ids)) {

            return $this->home();
        }

        return new JsonResponse(
            $this->volCerts->retrieveVolsCertData($ids),
            JsonResponse::HTTP_OK
        );
    }

    /**
     * @Route("/home", name="app_home")
     */
    public function home()
    {
        return $this->redirect('/');
    }
}
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
    private VolCerts $volCerts;

    /**
     * @var VolCertsTable $volCertsTable
     */
    private VolCertsTable $volCertsTable;

    /**
     * @var string $appVersion
     */
    private string $appVersion;

    /** @var FileUploader $fileUploader */
    private FileUploader $fileUploader;

    /**
     * @var Request|null $request
     */
    private ?Request $request;

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
        string $appVersion,
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

        $this->volCertsTable->retrieveVolCertData($file->fileName, is_null($this->request->get('json')));
        $content = $this->volCertsTable->getDataOut(
            !is_null($this->request->get('merge')),
        );

        unlink($file->fileName);

        if (!is_null($this->request->get('json'))){
            return new JsonResponse(
                $content,
                Response::HTTP_OK
            );
        }

        $html = $this->volCertsTable->renderView();

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
            Response::HTTP_OK
        );
    }

    /**
     * @Route("/home", name="app_home")
     */
    public function home(): RedirectResponse
    {
        return $this->redirect('/');
    }
}
<?php

namespace App\Controller;

use App\Service\VolCerts;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;

class VolCertsFormController extends AbstractController
{
    /**
     * @var Request $request
     */
    private $request;

    /**
     * @var string $appVersion
     */
    private $appVersion;

    /**
     * @var string $projectDir
     */
    private $projectDir;

    /**
     * @var VolCerts $volCerts
     */
    private $volCerts;

    /**
     * VolCertsFormController constructor
     * @param RequestStack $requestStack
     * @param string $appVersion
     * @param string $projectDir
     * @param VolCerts $volCerts
     */

    public function __construct(RequestStack $requestStack, string $appVersion, string $projectDir, VolCerts $volCerts)
    {
        $this->request = $requestStack->getCurrentRequest();

        $this->appVersion = $appVersion;

        $this->projectDir = $projectDir;

        $this->volCerts = $volCerts;
    }

    /**
     * @Route("/", name="app_file_form")
     * @return Response
     */
    public function index()
    {
        $response = $this->render(
            'form.html.twig',
            [
                'appVersion' => $this->appVersion,
                'projectDir' => $this->projectDir,
            ]
        );

        return $response;
    }

    /**
     * @Route("/api/{id}", name="app_list")
     * @param string $id
     * @return JsonResponse
     */
    public function get(string $id)
    {
        $ids = explode(',', $this->request->get('id'));

        $response = new JsonResponse(
            $this->volCerts->retrieveVolsCertData($ids),
            JsonResponse::HTTP_OK
        );

        return $response;
    }

}
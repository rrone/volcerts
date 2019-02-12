<?php

namespace App\Controller;

use App\Service\VolCerts;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class VolCertsFormController extends AbstractController
{
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
     * @param string $appVersion
     * @param string $projectDir
     * @param VolCerts $volCerts
     */

    public function __construct(string $appVersion, string $projectDir, VolCerts $volCerts)
    {
        $this->appVersion = $appVersion;

        $this->projectDir = $projectDir;

        $this->volCerts = $volCerts;
    }

    /**
     * @Route("/", name="app_file_form")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index()
    {

        $response = $this->render('form.html.twig', [
            'appVersion' => $this->appVersion,
            'projectDir' => $this->projectDir
        ]);

        return $response;
    }

    /**
     * @Route("/{id}", name="app_json")
     * @param integer $id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function get($id)
    {
        $response = new JsonResponse(
            $this->volCerts->retrieveVolCertData($id),
            JsonResponse::HTTP_OK
        );

        return $response;
    }

}
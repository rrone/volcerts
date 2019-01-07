<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
     * VolCertsFormController constructor
     * @param string $appVersion
     * @param string $projectDir
     */

    public function __construct(string $appVersion, string $projectDir)
    {
        $this->appVersion = $appVersion;

        $this->projectDir = $projectDir;
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
}
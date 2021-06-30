<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class VolCertsFormController
 * @package App\Controller
 */
class VolCertsFormController extends AbstractController
{
    /**
     * @var string $appVersion
     */
    private string $appVersion;

    /**
     * @var string $projectDir
     */
    private string $projectDir;

    /**
     * VolCertsFormController constructor
     * @param  $appVersion
     * @param  $projectDir
     */

    public function __construct($appVersion, $projectDir)
    {
        $this->appVersion = $appVersion;

        $this->projectDir = $projectDir;

    }

    /**
     * @Route("/", name="home")
     * @return Response
     */
    public function index(): Response
    {
        return $this->render(
            'form.html.twig',
            [
                'appVersion' => $this->appVersion,
                'projectDir' => $this->projectDir,
            ]
        );
    }
}
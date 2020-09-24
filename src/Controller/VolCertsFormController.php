<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class VolCertsFormController
 * @package App\Controller
 */
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
     * VolCertsFormController constructor
     * @param RequestStack $requestStack
     * @param  $appVersion
     * @param  $projectDir
     */

    public function __construct(RequestStack $requestStack, $appVersion, $projectDir)
    {
        $this->request = $requestStack->getCurrentRequest();

        $this->appVersion = $appVersion;

        $this->projectDir = $projectDir;

    }

    /**
     * @Route("/", name="home")
     * @return Response
     */
    public function index()
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
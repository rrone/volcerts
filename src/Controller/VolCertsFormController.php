<?php

namespace App\Controller;


use App\Entity\VolCertsForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;


class VolCertsFormController extends AbstractController
{
    /**
     * @var $volCertsForm
     */
    private $volCertsForm;

    /**
     * @var string
     */
    private $appVersion;

    /**
     * @var string
     */
    private $projectDir;
    /**
     * VolCertsFormController constructor.
     * @param VolCertsForm $volCertsForm
     * @param string $appVersion
     * @param string $projectDir
     */
    public function __construct(VolCertsForm $volCertsForm, string $appVersion, string $projectDir)
    {
        $this->volCertsForm = $volCertsForm;

        $this->appVersion = $appVersion;

        $this->projectDir = $projectDir;

    }

    /**
     *  @Route("/", name="app_file_form")
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
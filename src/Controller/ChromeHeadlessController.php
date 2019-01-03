<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\VolCertsEntity;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ChromeHeadlessAction
 * @package App\Controller
 */
class ChromeHeadlessController extends AbstractController
{
    /**
     * @var VolCertsEntity
     */
    private $volCertsEntity;

    /**
     * @var string
     */
    private $appVersion;

    /**
     * ChromeHeadlessController constructor.
     * @param VolCertsEntity $volCertsEntity
     * @param string $appVersion
     */
    public function __construct(VolCertsEntity $volCertsEntity, string $appVersion)
    {
        $this->volCertsEntity = $volCertsEntity;
        $this->appVersion = $appVersion;
    }

    /**
     * @Route("/ch", name="app_hch")
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\CommunicationException\CannotReadResponse
     * @throws \HeadlessChromium\Exception\CommunicationException\InvalidResponse
     * @throws \HeadlessChromium\Exception\CommunicationException\ResponseHasError
     * @throws \HeadlessChromium\Exception\EvaluationFailed
     * @throws \HeadlessChromium\Exception\NavigationExpired
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     */
    public function index()
    {
        $content = $this->volCertsEntity->retrieveVolCertData();
        $html = $this->volCertsEntity->renderView($content);
        $response = $this->render('view.html.twig', [
            'table' => $html,
            'appVersion' => $this->appVersion
        ]);

        return $response;

    }

}
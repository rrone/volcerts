<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\VolCertsEntity;

/**
 * Class ChromeHeadlessAction
 * @package App\Controller
 */
class ChromeHeadlessAction extends AbstractController
{
    /**
     * @var VolCertsEntity
     */
    private $volCertsEntity;

    /**
     * ChromeHeadlessAction constructor.
     * @param $volCertsEntity
     */
    public function __construct(VolCertsEntity $volCertsEntity)
    {
        $this->volCertsEntity = $volCertsEntity;
    }

    /**
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
        $vs = $this->volCertsEntity->retrieveVolCertData();

        $content = [];
        foreach ($vs as $k => $v) {
            foreach ($v as $j => $a) {
                $content[] = (array)$a;
            }
        }

        $this->volCertsEntity->writeCSV($content);

        $html = $this->volCertsEntity->renderTable($content);
        $response = $this->render('view.html.twig', ['table' => $html]);

        return $response;

    }

}
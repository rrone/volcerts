<?php

namespace App\Controller;

use HeadlessChromium\Browser;
use HeadlessChromium\BrowserFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DomCrawler\Crawler;

class ChromeHeadlessAction
{
    private $projectDir;

    private $urlCert;
    private $urlHome;

    private $arrIds;

    /**
     * ChromeHeadlessAction constructor.
     * @param $projectDir
     */
    public function __construct($projectDir)
    {
        $this->projectDir = $projectDir;

        $this->urlCert = "https://national.ayso.org/Volunteers/SelectViewCertificationInitialData?AYSOID=";
        $this->urlHome = "https://national.ayso.org/Volunteers/ViewCertification?UserName=";

        $this->arrIds = [];
        $file = dirname(dirname(__DIR__)).'/var/csv/Book1.csv';
        $fileData = fopen($file, 'r');
        while ($row = fgets($fileData)) {
            $row = (int)$row;
            if ($row > 0) {
                $this->arrIds[] = $row;
            };
        }
    }

    /**
     * @return JsonResponse
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
        $volCerts = $this->retreiveVolCertData($this->arrIds);

        return JsonResponse::fromJsonString($volCerts);

    }

    /**
     * @param array $arrIds
     * @return null
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\CommunicationException\CannotReadResponse
     * @throws \HeadlessChromium\Exception\CommunicationException\InvalidResponse
     * @throws \HeadlessChromium\Exception\CommunicationException\ResponseHasError
     * @throws \HeadlessChromium\Exception\EvaluationFailed
     * @throws \HeadlessChromium\Exception\NavigationExpired
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     */
    private function retreiveVolCertData(array $arrIds)
    {
        $browserFactory = new BrowserFactory('google-chrome');

        // starts headless chrome
        /* @var Browser */
        $browser = $browserFactory->createBrowser();

        // creates a new page and navigate to an url
        $page = $browser->createPage();

        $volCerts = [];
        foreach ($arrIds as $id) {
            $page->navigate($this->urlCert.$id)->waitForNavigation();
            $volCerts[$id] = $this->parseCertData(
                $id,
                $page->evaluate('document.documentElement.outerHTML')
                    ->getReturnValue()
            );
        }

        // bye
        $browser->close();

        return json_encode($volCerts);
    }

    /**
     * @param $id
     * @param $certData
     * @return array|string
     */
    private function parseCertData($id, $certData)
    {
        $crawler = new Crawler($certData);
        $nodeValue = $crawler->filter('pre')->text();

        if (!is_null($nodeValue)) {
            return $this->parseNodeValue($id, $nodeValue);
        } else {
            return '{}';
        }
    }

    /**
     * @param $id
     * @param $nodeValue
     * @return array
     */
    private function parseNodeValue($id, $nodeValue)
    {
        $nv = json_decode($nodeValue)->VolunteerCertificationDetails;

        $certs = [];
        try {
            $certs['VolunteerAYSOID'] = $nv->VolunteerAYSOID;
            $certs['VolunteerFullName'] = $nv->VolunteerFullName;
            $certs['VolunteerType'] = $nv->VolunteerType;
            $certs['VolunteerSAR'] = $nv->VolunteerSAR;
            $certs['VolunteerMembershipYear'] = $nv->VolunteerMembershipYear;
        } catch (\Exception $e) {
            $certs['VolunteerAYSOID'] = $id;
            $certs['VolunteerFullName'] = 'Volunteer not found';
            $certs['VolunteerType'] = '';
            $certs['VolunteerSAR'] = '';
            $certs['VolunteerMembershipYear'] = '';
        }

        return $certs;
    }
}
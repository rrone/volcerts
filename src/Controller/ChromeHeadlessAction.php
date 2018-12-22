<?php

namespace App\Controller;

use HeadlessChromium\Browser;
use HeadlessChromium\BrowserFactory;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ChromeHeadlessAction extends AbstractController
{
    /**
     * @var string
     */
    private $projectDir;
    /**
     * @var string
     */
    private $appVersion;

    /**
     * @var string
     */
    private $urlCert;
    /**
     * @var string
     */
    private $urlHome;

    /**
     * @var array
     */
    private $arrIds;

    /**
     * ChromeHeadlessAction constructor.
     * @param $projectDir
     * @param $appVersion
     */
    public function __construct($projectDir, $appVersion)
    {
        $this->projectDir = $projectDir;
        $this->appVersion = $appVersion;

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

        $this->arrIds = array_slice($this->arrIds, 0, 200);
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
        $volCerts = $this->retreiveVolCertData($this->arrIds);
        $vs = (array)json_decode($volCerts);

        foreach($vs as $k => $v){
            $content[] = (array) $v;
        }

        $html = $this->renderTable($content);
        $response = $this->render('view.html.twig', ['table' => $html]);

        return $response;

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
        $nv = json_decode($nodeValue);
        if($nv->ReturnStatus > 0) {
            return null;
        }

        $nv = $nv->VolunteerCertificationDetails;
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

    /**
     * @param array $content
     * @return array|string
     */
    private function renderTable(array $content)
    {
        if(is_null($content)) {
            return $content;
        }

        $hdrs = array_keys($content[0]);

        $html = <<<EOD
<table>
EOD;
        foreach($hdrs as $hdr){
            $html .= <<<EOD
<th>$hdr</th>
EOD;
        }

        foreach($content as $i => $certs) {
            $html .= <<<EOD
<tr>
EOD;
            foreach(array_values($certs) as $k => $v) {
                $html .= <<<EOD
<td>$v</td>
EOD;
            }
            $html .= <<<EOD
</tr>
EOD;
        }

        $html .= <<<EOD
</table>      
<div class="footer">
<br />
<hr>
<p>Version $this->appVersion</p>
</div>  
EOD;

        return $html;
    }

    /**
     * @param array $arrCert
     * @return false|string
     */
    private function phpDate(array $arrCert)
    {
        $ts = preg_replace( '/[^0-9]/', '', $arrCert['CertificationDate']);
        $date = date("Y-m-d", $ts / 1000);

        return $date;

    }

    /**
     * @param $jsCert
     * @return |null
     */
    private function getCertificationsReferee($jsCert)
    {
        if(is_null($jsCert)) {
            return null;
        }

        return $jsCert['VolunteerCertificationsReferee'];

    }
}
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
        $volCerts = $this->retrieveVolCertData($this->arrIds);
        $vs = (array)json_decode($volCerts);

        $content = null;
        foreach ($vs as $k => $v) {
            foreach ($v as $j => $a) {
                $content[] = (array)$a;
            }
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
    private function retrieveVolCertData(array $arrIds)
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
        if (is_null($nodeValue)) {
            return null;
        }

        $certs = null;
        $nv = json_decode($nodeValue);
        if ($nv->ReturnStatus == 0) {
            $certDetails = $nv->VolunteerCertificationDetails;
            $certRef = $this->getCertificationsReferee($certDetails);
            if (!is_null($certRef)) {
                foreach ($certRef as $cert) {
                    $certs[] = $cert;
                }
            }

            $certSH = $this->getCertificationsSafeHaven($certDetails);
            if (!is_null($certSH)) {
                foreach ($certSH as $cert) {
                    $certs[] = $cert;
                }
            }

            if (!is_null($certs)) {
                foreach ($certs as $k => $cert) {
                    $certs[$k]['VolunteerAYSOID'] = $certDetails->VolunteerAYSOID;
                    $certs[$k]['VolunteerFullName'] = $certDetails->VolunteerFullName;
                    $certs[$k]['VolunteerType'] = $certDetails->VolunteerType;
                    $certs[$k]['VolunteerSAR'] = $certDetails->VolunteerSAR;
                    $certs[$k]['VolunteerMembershipYear'] = $certDetails->VolunteerMembershipYear;
                    $certs[$k]['CertificationDesc'] = $cert['CertificationDesc'];
                    $certs[$k]['CertificationDate'] = $cert['CertificationDate'];
                    $certs[$k]['UpdatedBy'] = $cert['UpdatedBy'];
                }
            } else {
                $certs[0]['VolunteerAYSOID'] = $certDetails->VolunteerAYSOID;
                $certs[0]['VolunteerFullName'] = $certDetails->VolunteerFullName;
                $certs[0]['VolunteerType'] = $certDetails->VolunteerType;
                $certs[0]['VolunteerSAR'] = $certDetails->VolunteerSAR;
                $certs[0]['VolunteerMembershipYear'] = $certDetails->VolunteerMembershipYear;
                $certs[0]['CertificationDesc'] = '';
                $certs[0]['CertificationDate'] = '';
                $certs[0]['UpdatedBy'] = '';
            }
        } else {
            $certs[0]['VolunteerAYSOID'] = $id;
            $certs[0]['VolunteerFullName'] = '***'.$nv->ReturnMessage.'***';
            $certs[0]['VolunteerType'] = '';
            $certs[0]['VolunteerSAR'] = '';
            $certs[0]['VolunteerMembershipYear'] = '';
            $certs[0]['CertificationDesc'] = '';
            $certs[0]['CertificationDate'] = '';
            $certs[0]['UpdatedBy'] = '';
        }

        return $certs;
    }

    /**
     * @param array $content
     * @return array|string
     */
    private function renderTable(array $content)
    {
        if (is_null($content)) {
            return $content;
        }

        $hdrs = [
            'VolunteerAYSOID',
            'VolunteerFullName',
            'VolunteerType',
            'VolunteerSAR',
            'VolunteerMembershipYear',
            'CertificationDesc',
            'CertificationDate',
            'UpdatedBy',
        ];

        $html = <<<EOD
<table>
EOD;
        foreach ($hdrs as $hdr) {
            $html .= <<<EOD
<th>$hdr</th>
EOD;
        }

        foreach ($content as $i => $cert) {
            $html .= <<<EOD
<tr>
EOD;
            $html .= <<<EOD
<td>{$cert['VolunteerAYSOID']}</td>
<td>{$cert['VolunteerFullName']}</td>
<td>{$cert['VolunteerType']}</td>
<td>{$cert['VolunteerSAR']}</td>
<td>{$cert['VolunteerMembershipYear']}</td>
<td>{$cert['CertificationDesc']}</td>
<td>{$cert['CertificationDate']}</td>
<td>{$cert['UpdatedBy']}</td>
EOD;
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
 * @param string $certDate
 * @return false|string
 */
private
function phpDate(string $certDate)
{
    $ts = preg_replace('/[^0-9]/', '', $certDate);
    $date = date("Y-m-d", $ts / 1000);

    return $date;

}

/**
 * @param \stdClass $jsCert
 * @return array|null
 */
private
function getCertificationsReferee(\stdClass $jsCert)
{
    if (empty($jsCert)) {
        return null;
    }

    $certsRef = [];
    foreach ($jsCert->VolunteerCertificationsReferee as $k => $cls) {
        $certsRef[$k]['CertificationDesc'] = $cls->CertificationDesc;
        $certsRef[$k]['CertificationDate'] = $this->phpDate($cls->CertificationDate);
        $certsRef[$k]['UpdatedBy'] = $cls->UpdatedBy;
    }

    return $certsRef;

}

/**
 * @param $jsCert
 * @return string|null
 */
private
function getCertificationsCoach($jsCert)
{
    if (is_null($jsCert)) {
        return null;
    }

    return $jsCert['VolunteerCertificationsCoach'];

}

/**
 * @param $jsCert
 * @return array|null
 */
private
function getCertificationsSafeHaven($jsCert)
{
    if (is_null($jsCert)) {
        return null;
    }

    $certsSH = [];
    foreach ($jsCert->VolunteerCertificationsSafeHaven as $k => $cls) {
        $certsSH[$k]['CertificationDesc'] = $cls->CertificationDesc;
        $certsSH[$k]['CertificationDate'] = $this->phpDate($cls->CertificationDate);
        $certsSH[$k]['UpdatedBy'] = $cls->UpdatedBy;
    }

    return $certsSH;

}
}
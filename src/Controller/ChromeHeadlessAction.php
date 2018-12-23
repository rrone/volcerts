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
    private $urlDetails;
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

        $this->urlDetails = "https://national.ayso.org/Volunteers/SelectVolunteerDetails?AYSOID=";
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

        $this->writeCSV($content);

        $html = $this->renderTable($content);
        $response = $this->render('view.html.twig', ['table' => $html]);

        return $response;

    }

    private $hdrs = [
        'AYSOID',
        'FullName',
        'Type',
        'MY',
        'SafeHaven',
        'CDC',
        'CertificationDesc',
        'CertificationDate',
        'SAR',
    ];


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

        $certs = [];
        $certList = [];
        $nv = json_decode($nodeValue);
        if ($nv->ReturnStatus == 0) {
            $certDetails = $nv->VolunteerCertificationDetails;
            $certRef = $this->getCertificationsReferee($certDetails);
            if (!is_null($certRef)) {
                foreach ($certRef as $cert) {
                    $certList[] = $cert;
                }
            }

            $certSH = $this->getCertificationsSafeHaven($certDetails);
            if (!is_null($certSH)) {
                foreach ($certSH as $cert) {
                    $certList[] = $cert;
                }
            }

            if (!is_null($certList)) {
                $newCert = [];
                foreach ($certList as $k => $cert) {
                    $newCert[$k]['AYSOID'] = $certDetails->VolunteerAYSOID;
                    $fullName = explode(",", $certDetails->VolunteerFullName);
                    $newCert[$k]['FullName'] = ucwords(strtolower($fullName[1].' '.$fullName[0]));
                    $newCert[$k]['Type'] = $certDetails->VolunteerType;
                    $newCert[$k]['SAR'] = $certDetails->VolunteerSAR;
                    $newCert[$k]['MY'] = $certDetails->VolunteerMembershipYear;
                    if (isset($cert['SafeHaven'])) {
                        $newCert[$k]['SafeHaven'] = $cert['SafeHaven'];
                    } else {
                        $newCert[$k]['SafeHaven'] = '';
                    }
                    if (isset($cert['CDC'])) {
                        $newCert[$k]['CDC'] = $cert['CDC'];
                    } else {
                        $newCert[$k]['CDC'] = '';
                    }
                    if (isset($cert['CertificationDesc'])) {
                        $newCert[$k]['CertificationDesc'] = $cert['CertificationDesc'];
                    } else {
                        $newCert[$k]['CertificationDesc'] = '';
                    }
                    if (isset($cert['CertDate'])) {
                        $newCert[$k]['CertDate'] = $cert['CertDate'];
                    } else {
                        $newCert[$k]['CertDate'] = '';
                    }
                }

                foreach ($newCert as $k => $cert) {
                    if (!in_array($cert, $certs)) {
                        array_push($certs, $cert);
                    }
                }
            } else {
                $certs[0]['AYSOID'] = $certDetails->VolunteerAYSOID;
                $fullName = explode(",", $certDetails->VolunteerFullName);
                $certs[0]['FullName'] = ucwords(strtolower($fullName[1].' '.$fullName[0]));
                $certs[0]['Type'] = $certDetails->VolunteerType;
                $certs[0]['SAR'] = $certDetails->VolunteerSAR;
                $certs[0]['MY'] = $certDetails->VolunteerMembershipYear;
                $certs[0]['SafeHaven'] = '';
                $certs[0]['CDC'] = '';
                $certs[0]['CertificationDesc'] = '';
                $certs[0]['CertDate'] = '';
            }
        } else {
            $certs[0]['AYSOID'] = $id;
            $certs[0]['FullName'] = '***'.$nv->ReturnMessage.'***';
            $certs[0]['Type'] = '';
            $certs[0]['SAR'] = '';
            $certs[0]['MY'] = '';
            $certs[0]['SafeHaven'] = '';
            $certs[0]['CDC'] = '';
            $certs[0]['CertificationDesc'] = '';
            $certs[0]['CertDate'] = '';
        }

        return $certs;
    }


    private function writeCSV($content)
    {
        $filename = dirname(dirname(__DIR__)).'/var/downloads/results.csv';
        $file = fopen($filename, "w");

        fputcsv($file, $this->hdrs);

        foreach ($content as $line) {
            fputcsv($file, $line);
        }

        fclose($file);

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

        $html = <<<EOD
<table>
EOD;
        foreach ($this->hdrs as $hdr) {
            $html .= <<<EOD
<th>$hdr</th>
EOD;
        }

        foreach ($content as $i => $cert) {
            $html .= <<<EOD
<tr>
EOD;
            $html .= <<<EOD
<td>{$cert['AYSOID']}</td>
<td>{$cert['FullName']}</td>
<td>{$cert['Type']}</td>
<td>{$cert['MY']}</td>
<td>{$cert['SafeHaven']}</td>
<td>{$cert['CDC']}</td>
<td>{$cert['CertificationDesc']}</td>
<td>{$cert['CertDate']}</td>
<td>{$cert['SAR']}</td>
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
    function phpDate(
        string $certDate
    ) {
        $ts = preg_replace('/[^0-9]/', '', $certDate);
        $date = date("Y-m-d", $ts / 1000);

        return $date;

    }

    /**
     * @param \stdClass $jsCert
     * @return array|null
     */
    private
    function getCertificationsReferee(
        \stdClass $jsCert
    ) {
        if (empty($jsCert)) {
            return null;
        }

        $certsRef = [];
        foreach ($jsCert->VolunteerCertificationsReferee as $k => $cls) {
            $certsRef[$k]['CertificationDesc'] = $cls->CertificationDesc;
            $certsRef[$k]['CertDate'] = $this->phpDate($cls->CertificationDate);
            $certsRef[$k]['UpdatedBy'] = $cls->UpdatedBy;
        }

        return $certsRef;

    }

///**
// * @param $jsCert
// * @return string|null
// */
//private
//function getCertificationsCoach($jsCert)
//{
//    if (is_null($jsCert)) {
//        return null;
//    }
//
//    return $jsCert['VolunteerCertificationsCoach'];
//
//}

    /**
     * @param $jsCert
     * @return array|null
     */
    private
    function getCertificationsSafeHaven(
        $jsCert
    ) {
        if (is_null($jsCert)) {
            return null;
        }

        $certsSH = [];
        foreach ($jsCert->VolunteerCertificationsSafeHaven as $k => $cls) {
            if (strpos($cls->CertificationDesc, 'CDC')) {
                $certsSH[$k]['CDC'] = $this->phpDate($cls->CertificationDate);
                $certsSH[$k]['CertificationDesc'] = $cls->CertificationDesc;
            }
            if (strpos($cls->CertificationDesc, 'Safe Haven')) {
                $certsSH[$k]['SafeHaven'] = $this->phpDate($cls->CertificationDate);
                $certsSH[$k]['CertificationDesc'] = $cls->CertificationDesc;
            }
        }

        return $certsSH;

    }
}
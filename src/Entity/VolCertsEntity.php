<?php

namespace App\Entity;

use HeadlessChromium\Browser;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Page;
use Symfony\Component\DomCrawler\Crawler;

class VolCertsEntity
{
    /**
     * @var BrowserFactory
     */
    private $browserFactory;

    /**
     * @var Browser
     */
    private $browser;

    /**
     * @var Page
     */
    private $page;

    /**
     * @var string
     */
    private $urlCert;

    /**
     * @var string
     */
    private $filename;

    /**
     * @const integer
     */
    CONST NoIDS = 20;

    /**
     * VolCertsEntity constructor.
     * @param string $projectDir
     */
    public function __construct($projectDir)
    {
        $this->browserFactory = new BrowserFactory('google-chrome');

        set_time_limit(0);

//        $this->urlDetails = "https://national.ayso.org/Volunteers/SelectVolunteerDetails?AYSOID=";
        $this->urlCert = "https://national.ayso.org/Volunteers/SelectViewCertificationInitialData?AYSOID=";

        $this->filename = $projectDir.'/var/csv/Book1.csv';
    }

    /**
     * @var array
     */
    private $hdrs = [
        'AYSOID',
        'FullName',
        'Type',
        'SAR',
        'MY',
        'SafeHavenDate',
        'CDCDate',
        'RefCertificationDesc',
        'RefCertDate',
        'InstCertificationDesc',
        'InstCertDate',
        'AssessorCertificationDesc',
        'AssessorCertDate',
        'DataSource',
    ];

    /**
     * @var array
     */
    private $refMeta = [
        'U8 Official',
        'Assistant Referee',
        'Z-Online Regional Referee Course',
        'Regional Referee Online Companion Course',
        'Regional Referee',
        'Intermediate Referee Course',
        'Intermediate Referee',
        'Advanced Referee Course',
        'Advanced Referee',
        'National 2 Referee',
        'National Referee Course',
        'National Referee',
    ];

    /**
     * @var array
     */
    private $instMeta = [
        'Referee Instructor Course',
        'Regional Referee Instructor',
        'Intermediate Referee Instructor',
        'Referee Instructor',
        'Advanced Referee Instructor Course',
        'Advanced Referee Instructor',
        'National Referee Instructor',
    ];

    /**
     * @var array
     */
    private $asseMeta = [
        'Referee Assessor Course',
        'Referee Assessor',
        'National Referee Assessor Course',
        'National Referee Assessor',
    ];

    /**
     * @param $filename
     * @return array
     */
    private function loadFile($filename)
    {
        $arrIds = [];

        $fileData = fopen($filename, 'r');
        while ($row = fgets($fileData)) {
            $row = (int)$row;
            if ($row > 0) {
                $arrIds[] = $row;
            };
        }

        $arrIds = array_slice($arrIds,0,self::NoIDS);

        return $arrIds;
    }

    /**
     * @return array
     * @throws \Exception
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\CommunicationException\CannotReadResponse
     * @throws \HeadlessChromium\Exception\CommunicationException\InvalidResponse
     * @throws \HeadlessChromium\Exception\CommunicationException\ResponseHasError
     * @throws \HeadlessChromium\Exception\EvaluationFailed
     * @throws \HeadlessChromium\Exception\NavigationExpired
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     */
    public function retrieveVolCertData()
    {
        $arrIds = $this->loadFile($this->filename);
        $volCerts = [];
        $this->page = $this->newPage();
        foreach ($arrIds as $id) {
            $this->page->navigate($this->urlCert.$id)->waitForNavigation();
            $volCerts[$id] = $this->parseCertData(
                $id,
                $this->page->evaluate('document.documentElement.outerHTML')
                    ->getReturnValue()
            );
        }

        $this->browserClose();

        return $volCerts;
    }

    /**
     * @param array $options
     * @return Page
     * @throws \Exception
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     */
    private function newPage($options = ['startupTimeout' => 60])
    {
        $this->browserClose();

        /* @var Browser */
        $this->browser = $this->browserFactory->createBrowser($options);

        // creates a new page and navigate to an url

        return $this->browser->createPage();
    }

    /**
     * @throws \Exception
     */
    private function browserClose()
    {
        if (!is_null($this->browser)) {
            $this->browser->close();
        }

        return;
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
            $nv = $this->parseNodeValue($id, $nodeValue);
            $nv['DataSource'] = 'e3';

            return $nv;
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

        $certList = [];

        $nv = json_decode($nodeValue);
        if ($nv->ReturnStatus == 0) {
            $certDetails = $nv->VolunteerCertificationDetails;
            $certRef = $this->getCertificationsReferee($certDetails);
            if (!is_null($certRef)) {
                foreach ($certRef as $k => $cert) {
                    $certList[$k] = $cert;
                }
            }

            $certInstructor = $this->getCertificationsInstructor($certDetails);
            if (!is_null($certInstructor)) {
                foreach ($certInstructor as $k => $cert) {
                    $certList[$k] = $cert;
                }
            }

            $certSH = $this->getCertificationsSafeHaven($certDetails);
            if (!is_null($certSH)) {
                foreach ($certSH as $k => $cert) {
                    $certList[$k] = $cert;
                }
            }

            if (!empty($certList)) {
                $c = $certList;
                $cert = [];
                $cert['AYSOID'] = $certDetails->VolunteerAYSOID;
                $fullName = explode(",", $certDetails->VolunteerFullName);
                $cert['FullName'] = ucwords(strtolower($fullName[1].' '.$fullName[0]));
                $cert['Type'] = $certDetails->VolunteerType;
                $cert['SAR'] = $certDetails->VolunteerSAR;
                $cert['MY'] = $certDetails->VolunteerMembershipYear;
                if (isset($c['SafeHaven'])) {
                    $cert['SafeHaven'] = $c['SafeHaven'];
                } else {
                    $cert['SafeHaven'] = '';
                }
                if (isset($c['CDC'])) {
                    $cert['CDC'] = $c['CDC'];
                } else {
                    $cert['CDC'] = '';
                }
                if (isset($c['RefCertificationDesc'])) {
                    $cert['RefCertificationDesc'] = $c['RefCertificationDesc'];
                } else {
                    $cert['RefCertificationDesc'] = '';
                }
                if (isset($c['RefCertDate'])) {
                    $cert['RefCertDate'] = $c['RefCertDate'];
                } else {
                    $cert['RefCertDate'] = '';
                }
                if (isset($c['InstCertificationDesc'])) {
                    $cert['InstCertificationDesc'] = $c['InstCertificationDesc'];
                } else {
                    $cert['InstCertificationDesc'] = '';
                }
                if (isset($c['InstCertDate'])) {
                    $cert['InstCertDate'] = $c['InstCertDate'];
                } else {
                    $cert['InstCertDate'] = '';
                }
                if (isset($c['AssessorCertificationDesc'])) {
                    $cert['AssessorCertificationDesc'] = $c['AssessorCertificationDesc'];
                } else {
                    $cert['AssessorCertificationDesc'] = '';
                }
                if (isset($c['AssessorCertDate'])) {
                    $cert['AssessorCertDate'] = $c['AssessorCertDate'];
                } else {
                    $cert['AssessorCertDate'] = '';
                }
            } else {
                $cert['AYSOID'] = $certDetails->VolunteerAYSOID;
                $fullName = explode(",", $certDetails->VolunteerFullName);
                $cert['FullName'] = trim(ucwords(strtolower($fullName[1].' '.$fullName)));
                $cert['Type'] = $certDetails->VolunteerType;
                $cert['SAR'] = $certDetails->VolunteerSAR;
                $cert['MY'] = $certDetails->VolunteerMembershipYear;
                $cert['SafeHaven'] = '';
                $cert['CDC'] = '';
                $cert['RefCertificationDesc'] = '';
                $cert['RefCertDate'] = '';
                $cert['InstCertificationDesc'] = '';
                $cert['InstCertDate'] = '';
                $cert['AssessorCertificationDesc'] = '';
                $cert['AssessorCertDate'] = '';
            }
        } else {
            $cert['AYSOID'] = $id;
            $cert['FullName'] = '***'.$nv->ReturnMessage.'***';
            $cert['Type'] = '';
            $cert['SAR'] = '';
            $cert['MY'] = '';
            $cert['SafeHaven'] = '';
            $cert['CDC'] = '';
            $cert['RefCertificationDesc'] = '';
            $cert['RefCertDate'] = '';
            $cert['InstCertificationDesc'] = '';
            $cert['InstCertDate'] = '';
            $cert['AssessorCertificationDesc'] = '';
            $cert['AssessorCertDate'] = '';
        }

        return $cert;
    }


    /**
     * @param array $content
     */

    /**
     * @param array $content
     * @return array|string
     */
    public function renderView(array $content)
    {
        if (is_null($content)) {
            return $content;
        }

        $html = <<<EOD
<table id="vol_certs" class="display">
<thead>
<tr>
EOD;
        foreach ($this->hdrs as $hdr) {
            $html .= <<<EOD
<th>$hdr</th>
EOD;
        }

        $html .= <<<EOD
</tr>
</thead>
<tbody>
EOD;

        foreach ($content as $i => $cert) {
            $html .= <<<EOD
<tr>
EOD;
            $html .= <<<EOD
<td>{$cert['AYSOID']}</td>
<td>{$cert['FullName']}</td>
<td>{$cert['Type']}</td>
<td>{$cert['MY']}</td>
<td>{$cert['SAR']}</td>
<td>{$cert['SafeHaven']}</td>
<td>{$cert['CDC']}</td>
<td>{$cert['RefCertificationDesc']}</td>
<td>{$cert['RefCertDate']}</td>
<td>{$cert['InstCertificationDesc']}</td>
<td>{$cert['InstCertDate']}</td>
<td>{$cert['AssessorCertificationDesc']}</td>
<td>{$cert['AssessorCertDate']}</td>
<td>{$cert['DataSource']}</td>
EOD;
            $html .= <<<EOD
</tr>
EOD;
        }

        $createDate = date('d M Y') . ' at ' . date('H:i') . ' UCT';
        $html .= <<<EOD
</tbody>
</table> 

<p class="createdOn">Created at $createDate</p>     
EOD;

        return $html;
    }

    /**
     * @param string $certDate
     * @return false|string
     */
    private function phpDate(string $certDate)
    {
        $ts = preg_replace('/[^0-9]/', '', $certDate);
        $date = date("Y-m-d", $ts / 1000);

        return $date;
    }

    /**
     * @param \stdClass $jsCert
     * @return array|null
     */
    private function getCertificationsReferee(\stdClass $jsCert)
    {
        if (empty($jsCert)) {
            return null;
        }

        $certs['RefCertificationDesc'] = '';
        $certs['RefCertDate'] = '';
        $certs['AssessorCertificationDesc'] = '';
        $certs['AssessorCertDate'] = '';
        foreach ($jsCert->VolunteerCertificationsReferee as $k => $cls) {
            if (!is_bool(strpos($cls->CertificationDesc, 'Referee Assessor'))) {
                if (array_search($cls->CertificationDesc, $this->asseMeta) > array_search(
                        $certs['AssessorCertificationDesc'],
                        $this->asseMeta
                    )) {
                    $certs['AssessorCertificationDesc'] = $cls->CertificationDesc;
                    $certs['AssessorCertDate'] = $this->phpDate($cls->CertificationDate);
                }
            } else {
                if (array_search($cls->CertificationDesc, $this->refMeta) > array_search(
                        $certs['RefCertificationDesc'],
                        $this->refMeta
                    )) {
                    $certs['RefCertificationDesc'] = $cls->CertificationDesc;
                    $certs['RefCertDate'] = $this->phpDate($cls->CertificationDate);
                }
            }
        }

        return $certs;
    }

    /**
     * @param \stdClass $jsCert
     * @return array|null
     */
    private function getCertificationsInstructor(\stdClass $jsCert)
    {
        if (empty($jsCert)) {
            return null;
        }

        $certs['InstCertificationDesc'] = '';
        $certs['InstCertDate'] = '';
        foreach ($jsCert->VolunteerCertificationsInstructor as $k => $cls) {
            if (!is_bool(strpos($cls->CertificationDesc, 'Referee Instructor'))) {
                if (array_search($cls->CertificationDesc, $this->instMeta) > array_search(
                        $certs['InstCertificationDesc'],
                        $this->instMeta
                    )) {
                    $certs['InstCertificationDesc'] = $cls->CertificationDesc;
                    $certs['InstCertDate'] = $this->phpDate($cls->CertificationDate);
                }
            }
        }

        return $certs;
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
    private function getCertificationsSafeHaven($jsCert)
    {
        if (is_null($jsCert)) {
            return null;
        }

        $certs['CDC'] = '';
        $certs['SafeHaven'] = '';
        foreach ($jsCert->VolunteerCertificationsSafeHaven as $k => $cls) {
            if (strpos($cls->CertificationDesc, 'CDC')) {
                if ($this->phpDate($cls->CertificationDate) > $certs['CDC']) {
                    $certs['CDC'] = $this->phpDate($cls->CertificationDate);
                }
            }
            if (strpos($cls->CertificationDesc, 'Safe Haven')) {
                if ($this->phpDate($cls->CertificationDate) > $certs['SafeHaven']) {
                    $certs['SafeHaven'] = $this->phpDate($cls->CertificationDate);
                }
            }
        }

        return $certs;
    }
}
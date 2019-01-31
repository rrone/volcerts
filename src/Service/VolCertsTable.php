<?php /** @noinspection SpellCheckingInspection */

namespace App\Service;

use Symfony\Component\DomCrawler\Crawler;
use DateTime;
use DateTimeZone;

class VolCertsTable
{
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
    CONST MaxIDS = 2000;

    /**
     * @const string
     */
    CONST TZ = 'PST';

    /**
     * VolCertsEntity constructor.
     * @param string $projectDir
     */
    public function __construct($projectDir)
    {
//        $this->browserFactory = new BrowserFactory('google-chrome');

        set_time_limit(0);

//        $this->urlDetails = "https://national.ayso.org/Volunteers/SelectVolunteerDetails?AYSOID=";
        $this->urlCert = "https://national.ayso.org/Volunteers/SelectViewCertificationInitialData";

        $this->filename = $projectDir.'/var/uploads/csv.csv';
    }

    /**
     * @var array
     */
    private $hdrs = [
        'AYSOID',
        'FullName',
        'Type',
        'MY',
        'SAR',
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
        'Regional Referee',
        'Intermediate Referee',
        'Advanced Referee',
        'National 2 Referee',
        'National Referee'
    ];

//    /**
//     * @var array
//     */
//    private $refCourseMeta = [
//        'Z-Online Regional Referee Course',
//        'Regional Referee Online Companion Course',
//        'Intermediate Referee Course',
//        'Advanced Referee Course',
//        'National Referee Course'
//    ];

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
        'National Referee Instructor'
    ];

    /**
     * @var array
     */
    private $assessMeta = [
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

        $arrIds = array_slice($arrIds, 0, self::MaxIDS);

        return $arrIds;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function retrieveVolCertData()
    {
        $arrIds = $this->loadFile($this->filename);
        $volCerts = [];

        foreach ($arrIds as $id) {
            $volCerts[$id] = $this->parseCertData(
                $id,
                $this->curl_get($this->urlCert, ['AYSOID' => $id])
            );

        }

        return $volCerts;
    }


    /**
     * @param $id
     * @param $certData
     * @return array|string
     */
    private function parseCertData($id, $certData)
    {
        $crawler = new Crawler($certData);
        $nodeValue = $crawler->filter('body')->text();

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
                $cert['MY'] = $certDetails->VolunteerMembershipYear;
                $cert['SAR'] = $certDetails->VolunteerSAR;
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
                $cert['MY'] = $certDetails->VolunteerMembershipYear;
                $cert['SAR'] = $certDetails->VolunteerSAR;
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
            $cert['MY'] = '';
            $cert['SAR'] = '';
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
     * @return array|string
     * @throws \Exception
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

        $createDate = $this->getTimestamp().' '.self::TZ;
        $html .= <<<EOD
</tbody>
</table> 

<p class="createdOn">Created at $createDate </p>     
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
                if (array_search($cls->CertificationDesc, $this->assessMeta) > array_search(
                        $certs['AssessorCertificationDesc'],
                        $this->assessMeta
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

    /**
     * @return string
     * @throws \Exception
     */
    protected function getTimestamp()
    {
        $utc = date("Y-m-d H:i:s");;

        $ts = new DateTime($utc, new DateTimeZone('UTC'));
        $ts->setTimezone(new DateTimeZone(self::TZ));

        return $ts->format('Y-m-d H:i');
    }


    /**
     * Send a GET request using cURL
     * @param string $url to request
     * @param array $get values to send
     * @param array $options for cURL
     * @return string
     */
    function curl_get($url, array $get = null, array $options = array())
    {
        $defaults = array(
            CURLOPT_URL => $url.(strpos($url, '?') === false ? '?' : '').http_build_query($get),
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 4,
        );

        $ch = curl_init();
        curl_setopt_array($ch, ($options + $defaults));
        if (!$result = curl_exec($ch)) {
            trigger_error(curl_error($ch));
        }
        curl_close($ch);

        return $result;
    }

}
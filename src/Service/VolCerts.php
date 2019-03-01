<?php

namespace App\Service;

use Symfony\Component\DomCrawler\Crawler;

class VolCerts
{

    /**
     * @var string
     */
    private $urlCert;

    /**
     * VolCertsEntity constructor
     */
    public function __construct()
    {
//        $this->urlDetails = "https://national.ayso.org/Volunteers/SelectVolunteerDetails?AYSOID=";
        $this->urlCert = "https://national.ayso.org/Volunteers/SelectViewCertificationInitialData";
    }

    /**
     * @var array
     */
    private $hdrs = [
        'AYSOID',
        'Full Name',
        'Type',
        'MY',
        'SAR',
        'Safe Haven Date',
        'CDC Date',
        'Ref Cert Desc',
        'Ref Cert Date',
        'Inst Cert Desc',
        'Inst Cert Date',
        'Assessor Cert Desc',
        'Assessor Cert Date',
        'Data Source',
    ];

    /**
     * @var array
     */
    private $keys = [
        'AYSOID',
        'FullName',
        'Type',
        'MY',
        'SAR',
        'SafeHavenDate',
        'CDCDate',
        'RefCertDesc',
        'RefCertDate',
        'InstCertDesc',
        'InstCertDate',
        'AssessorCertDesc',
        'AssessorCertDate',
        'DataSource',
    ];


    /**
     * @var array
     */
    private $refMeta = [
        'U-8 Official',
        'U8 Official',
        '8U Official',
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
    private $refCertMeta = [
        'U-8 Official',
        'U8 Official',
        '8U Official',
        'Assistant Referee',
        'Regional Referee',
        'Intermediate Referee',
        'Advanced Referee',
        'National 2 Referee',
        'National Referee',
    ];

    /**
     * @var array
     */
    private $refCourseMeta = [
        'Z-Online Regional Referee Course',
        'Regional Referee Online Companion Course',
        'Intermediate Referee Course',
        'Advanced Referee Course',
        'National Referee Course',
    ];

    /**
     * @var array
     */
    private $instMeta = [
        'Regional Referee Instructor',
        'Intermediate Referee Instructor',
        'Referee Instructor',
        'Advanced Referee Instructor',
        'National Referee Instructor',
    ];

//    /**
//     * @var array
//     */
//    private $instCourseMeta = [
//        'Referee Instructor Course',
//        'Advanced Referee Instructor Course',
//    ];

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
     * @return array
     */
    public function getHdrs()
    {
        return $this->hdrs;
    }

    /**
     * @return array
     */
    public function getKeys()
    {
        return $this->keys;
    }


    /**
     * @param array $idList
     * @return array|string
     */
    public function retrieveVolsCertData(array $idList)
    {
        $groupSize = 50;
        $certsGroup = [];
        for ($k = 0; $k < count($idList); $k += $groupSize) {
            $certsGroup[] = array_slice($idList, $k, 50);
        }

        $certs = [];
        foreach( $certsGroup as $group){
            $certs = array_merge($certs, $this->curl_multi_get($this->urlCert, $group));
        }

        $certData = [];
        foreach ($idList as $i => $id) {
            $certData[] = $this->parseCertData($id, $certs[$i]);
        }

        return $certData;
    }


    /**
     * @param $url
     * @param array|null $get
     * @return array
     */
//    Reference: https://www.toni-develops.com/2017/09/05/curl-multi-fetch/

    private function curl_multi_get($url, array $get = null)
    {
        if (is_null($get)) {
            return array();
        }

        $ch = array();
        $mh = curl_multi_init();

        foreach ($get as $i => $id) {

            $ch[$i] = curl_init();
            curl_setopt($ch[$i], CURLOPT_URL, $url.'?'.http_build_query(['AYSOID' => $id]));
            curl_setopt($ch[$i], CURLOPT_HEADER, 0);
            curl_setopt($ch[$i], CURLOPT_RETURNTRANSFER, true);
            curl_multi_add_handle($mh, $ch[$i]);
        }

        $active = null;
        do {
            curl_multi_exec($mh, $active);
            usleep(100); // May needed to limit CPU load
        } while ($active);

        $content = array();
        foreach ($ch AS $i => $c) {
            $content[$i] = curl_multi_getcontent($c);
            curl_multi_remove_handle($mh, $c);
        }

        curl_multi_close($mh);

        return $content;
    }

    /**
     * @param $id
     * @return string
     */
    public function retrieveVolCertData($id)
    {

        return $this->parseCertData($id, $this->curl_get($this->urlCert, ['AYSOID' => $id]));
    }

    /**
     * Send a GET request using cURL
     * @param string $url to request
     * @param array $get values to send
     * @param array $options for cURL
     * @return string
     */
    private function curl_get($url, array $get = null, array $options = array())
    {
        $defaults = array(
            CURLOPT_URL => $url.(strpos($url, '?') === false ? '?' : '').http_build_query($get),
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => true,
        );

        $ch = curl_init();
        curl_setopt_array($ch, ($options + $defaults));
        if (!$result = curl_exec($ch)) {
            trigger_error(curl_error($ch));
        }
        curl_close($ch);

        return $result;
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
     * @param $id
     * @param $certData
     * @return array|string
     */
    private function parseCertData($id, $certData)
    {
        if (empty($certData)) {
            return '{}';
        }

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

            $certRef = $this->getCertificationsAssessor($certDetails);
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
                $cert['FullName'] = trim(ucwords(strtolower($fullName[1].' '.$fullName[0])));
                $cert['Type'] = $certDetails->VolunteerType;
                $cert['MY'] = $certDetails->VolunteerMembershipYear;

                $sar = explode('/', $certDetails->VolunteerSAR);
                $s = isset($sar[0]) ? ltrim($sar[0], '0') : null;
                $a = isset($sar[1]) ? ltrim($sar[1], '0') : null;
                $r = isset($sar[2]) ? ltrim($sar[2], '0') : null;
                if (!is_null($s)) {
                    $sar = $s;
                }
                if (!is_null($a)) {
                    $sar .= '/'.$a;
                }
                if (!is_null($r)) {
                    $sar .= '/'.$r;
                }
                $cert['SAR'] = $sar;

                if (isset($c['SafeHavenDate'])) {
                    $cert['SafeHavenDate'] = $c['SafeHavenDate'];
                } else {
                    $cert['SafeHavenDate'] = '';
                }
                if (isset($c['CDCDate'])) {
                    $cert['CDCDate'] = $c['CDCDate'];
                } else {
                    $cert['CDCDate'] = '';
                }
                if (isset($c['RefCertDesc'])) {
                    $cert['RefCertDesc'] = $c['RefCertDesc'];
                } else {
                    $cert['RefCertDesc'] = '';
                }
                if (isset($c['RefCertDate'])) {
                    $cert['RefCertDate'] = $c['RefCertDate'];
                } else {
                    $cert['RefCertDate'] = '';
                }
                if (isset($c['InstCertDesc'])) {
                    $cert['InstCertDesc'] = $c['InstCertDesc'];
                } else {
                    $cert['InstCertDesc'] = '';
                }
                if (isset($c['InstCertDate'])) {
                    $cert['InstCertDate'] = $c['InstCertDate'];
                } else {
                    $cert['InstCertDate'] = '';
                }
                if (isset($c['AssessorCertDesc'])) {
                    $cert['AssessorCertDesc'] = $c['AssessorCertDesc'];
                } else {
                    $cert['AssessorCertDesc'] = '';
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
                $cert['SafeHavenDate'] = '';
                $cert['CDCDate'] = '';
                $cert['RefCertDesc'] = '';
                $cert['RefCertDate'] = '';
                $cert['InstCertDesc'] = '';
                $cert['InstCertDate'] = '';
                $cert['AssessorCertDesc'] = '';
                $cert['AssessorCertDate'] = '';
            }
        } else {
            $cert['AYSOID'] = $id;
            $cert['FullName'] = '*** '.trim($nv->ReturnMessage).' ***';
            $cert['Type'] = '';
            $cert['MY'] = '';
            $cert['SAR'] = '';
            $cert['SafeHavenDate'] = '';
            $cert['CDCDate'] = '';
            $cert['RefCertDesc'] = '';
            $cert['RefCertDate'] = '';
            $cert['InstCertDesc'] = '';
            $cert['InstCertDate'] = '';
            $cert['AssessorCertDesc'] = '';
            $cert['AssessorCertDate'] = '';
        }

        return $cert;
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

        $certs['RefCertDesc'] = '';
        $certs['RefCertDate'] = '';
        $certDesc = '';
        $certDate = '';
        $courseDesc = '';
        $courseDate = '';
        $and = '';
        foreach ($jsCert->VolunteerCertificationsReferee as $k => $cls) {
            if (is_bool(strpos($cls->CertificationDesc, 'Assessor'))) {
                if (array_search($cls->CertificationDesc, $this->refCertMeta) > array_search(
                        $certDesc,
                        $this->refCertMeta
                    )) {
                    $certDesc = $cls->CertificationDesc;
                    $certDate = $this->phpDate($cls->CertificationDate);
                }
                if (array_search($cls->CertificationDesc, $this->refCourseMeta) > array_search(
                        $courseDesc,
                        $this->refCourseMeta
                    )) {
                    $courseDesc = $cls->CertificationDesc;
                    $courseDate = $this->phpDate($cls->CertificationDate);
                }
            }
        }

        if (array_search($courseDesc, $this->refMeta) > array_search($certDesc, $this->refMeta)) {
            if (!empty($certDesc)) {
                $and = ' / ';
            }
            $certs['RefCertDesc'] = $certDesc.$and.$courseDesc;
            $certs['RefCertDate'] = $certDate.$and.$courseDate;
        } else {
            $certs['RefCertDesc'] = $certDesc;
            $certs['RefCertDate'] = $certDate;
        }

        return $certs;
    }

    /**
     * @param \stdClass $jsCert
     * @return array|null
     */
    private function getCertificationsAssessor(\stdClass $jsCert)
    {
        if (empty($jsCert)) {
            return null;
        }

        $certs['AssessorCertDesc'] = '';
        $certs['AssessorCertDate'] = '';
        foreach ($jsCert->VolunteerCertificationsReferee as $k => $cls) {
            if (!is_bool(strpos($cls->CertificationDesc, 'Assessor'))) {
                if (array_search($cls->CertificationDesc, $this->assessMeta) > array_search(
                        $certs['AssessorCertDesc'],
                        $this->assessMeta
                    )) {
                    $certs['AssessorCertDesc'] = $cls->CertificationDesc;
                    $certs['AssessorCertDate'] = $this->phpDate($cls->CertificationDate);
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

        $certs['InstCertDesc'] = '';
        $certs['InstCertDate'] = '';
        foreach ($jsCert->VolunteerCertificationsInstructor as $k => $cls) {
            if (array_search($cls->CertificationDesc, $this->instMeta) > array_search(
                    $certs['InstCertDesc'],
                    $this->instMeta
                )) {
                $certs['InstCertDesc'] = $cls->CertificationDesc;
                $certs['InstCertDate'] = $this->phpDate($cls->CertificationDate);
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

        $certs['CDCDate'] = '';
        $certs['SafeHavenDate'] = '';
        foreach ($jsCert->VolunteerCertificationsSafeHaven as $k => $cls) {
            if (strpos($cls->CertificationDesc, 'CDC') !== false) {
                if ($this->phpDate($cls->CertificationDate) > $certs['CDCDate']) {
                    $certs['CDCDate'] = $this->phpDate($cls->CertificationDate);
                }
            }
            if (strpos($cls->CertificationDesc, 'Safe Haven') !== false) {
                if ($this->phpDate($cls->CertificationDate) > $certs['SafeHavenDate']) {
                    $certs['SafeHavenDate'] = $this->phpDate($cls->CertificationDate);
                }
            }
        }

        return $certs;
    }

}
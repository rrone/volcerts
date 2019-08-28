<?php

namespace App\Service;

use Symfony\Component\DomCrawler\Crawler;
use stdClass;

class VolCerts
{

    /**
     * @var string
     */
    private $urlCert;

    /**
     * @var string
     */
    private $and;

    /**
     * VolCertsEntity constructor
     */
    public function __construct()
    {
//        $this->urlDetails = "https://national.ayso.org/Volunteers/SelectVolunteerDetails?AYSOID=";
        $this->urlCert = "https://national.ayso.org/Volunteers/SelectViewCertificationInitialData";

        $this->and = '';
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
        'Assessor Cert Desc',
        'Assessor Cert Date',
        'Inst Cert Desc',
        'Inst Cert Date',
        'Inst Eval Cert Desc',
        'Inst Eval Cert Date',
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
        'AssessorCertDesc',
        'AssessorCertDate',
        'InstCertDesc',
        'InstCertDate',
        'InstEvalCertDesc',
        'InstEvalCertDate',
        'DataSource',
    ];

    /**
     * @var array
     */
    private $refMeta = [
        '',     //blank required to match first certification
        'U-8 Official',
        'U8 Official',
        '8U Official',
        'Assistant Referee',
        'Z-Online Regional Referee Course',
        'Regional Referee Online Companion Course',
        'Regional Referee & Safe Haven Referee',
        'Regional Referee',
        'Intermediate Referee Course',
        'Intermediate Referee',
        'Advanced Referee Course',
        'Advanced Referee',
        'National 2 Referee',
        'National Referee Course',
        'National Referee',
        'National 1 Referee',
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
    private $instEvalMeta = [
        'Referee Instructor Evaluator Course',
        'Referee Instructor Evaluator',
    ];

//
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
        //strip out duplicates
        $idList = array_values(array_unique($idList, SORT_NUMERIC));

        //size the groups to avoid gateway timeouts -- SiteGround limitation
        $groupSize = 30;
        $certsGroup = [];
        for ($k = 0; $k < count($idList); $k += $groupSize) {
            $certsGroup[] = array_slice($idList, $k, $groupSize);
        }

        $certs = [];
        foreach ($certsGroup as $group) {
            $certs = array_merge($certs, $this->curl_multi_get($this->urlCert, $group));
        }

        $certData = [];
        foreach ($certs as $id => $cert) {
            $certData[] = $this->parseCertData($idList[$id], $cert);
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
        if($certDate == '/Date(-62135568000000)/') {
            return '1964-09-15';
        }

        $ts = preg_replace('/[^0-9]/', '', $certDate);
        $date = date("Y-m-d", $ts / 1000);

        return $date;
    }

    /**
     * @param int $id
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
     * @param int $id
     * @param int $nodeValue
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

            $certInstructor = $this->getCertificationsInstructorEvaluator($certDetails);
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
                $cert['Type'] = $certDetails->Type;
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

                $cert['SafeHavenDate'] = isset($c['SafeHavenDate']) ? $c['SafeHavenDate'] : $cert['SafeHavenDate'];
                $cert['CDCDate'] = isset($c['CDCDate']) ? $c['CDCDate'] : $cert['CDCDate'];

                $cert['RefCertDesc'] = isset($c['RefCertDesc']) ? $c['RefCertDesc'] : $cert['RefCertDesc'];
                $cert['RefCertDate'] = isset($c['RefCertDate']) ? $c['RefCertDate'] : $cert['RefCertDate'];

                $cert['AssessorCertDesc'] = isset($c['AssessorCertDesc']) ? $c['AssessorCertDesc'] : $cert['AssessorCertDesc'];
                $cert['AssessorCertDate'] = isset($c['AssessorCertDate']) ? $c['AssessorCertDate'] : $cert['AssessorCertDate'];

                $cert['InstCertDesc'] = isset($c['InstCertDesc']) ? $c['InstCertDesc'] : $cert['InstCertDesc'];
                $cert['InstCertDate'] = isset($c['InstCertDate']) ? $c['InstCertDate'] : $cert['InstCertDate'];

                $cert['InstEvalCertDesc'] = isset($c['InstEvalCertDesc']) ? $c['InstEvalCertDesc'] : $cert['InstEvalCertDesc'];
                $cert['InstEvalCertDate'] = isset($c['InstEvalCertDate']) ? $c['InstEvalCertDate'] : $cert['InstEvalCertDate'];

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
                $cert['AssessorCertDesc'] = '';
                $cert['AssessorCertDate'] = '';
                $cert['InstCertDesc'] = '';
                $cert['InstCertDate'] = '';
                $cert['InstEvalCertDesc'] = '';
                $cert['InstEvalCertDate'] = '';
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
            $cert['AssessorCertDesc'] = '';
            $cert['AssessorCertDate'] = '';
            $cert['InstCertDesc'] = '';
            $cert['InstCertDate'] = '';
            $cert['InstEvalCertDesc'] = '';
            $cert['InstEvalCertDate'] = '';
        }

        return $cert;
    }

    /**
     * @param stdClass $jsCert
     * @return array|null
     */
    private function getCertificationsReferee(stdClass $jsCert)
    {
        if (empty($jsCert)) {
            return null;
        }

        $certs = $this->parseCerts($jsCert, 'Official');
        $this->and = '';

        if (array_search($certs->courseDesc, $this->refMeta) > array_search($certs->certDesc, $this->refMeta)) {
            if (!empty($certs->certDesc)) {
                $this->and = ' / ';
            }
            $cert['RefCertDesc'] = $certs->certDesc.$this->and.$certs->courseDesc;
            $cert['RefCertDate'] = $certs->certDate.$this->and.$certs->courseDate;
        } else {
            $cert['RefCertDesc'] = $certs->certDesc;
            $cert['RefCertDate'] = $certs->certDate;
        }

        $certs = $this->parseCerts($jsCert, 'Referee');
        $this->and = '';

        if (array_search($certs->courseDesc, $this->refMeta) > array_search($certs->certDesc, $this->refMeta)) {
            if (!empty($certs->certDesc)) {
                $this->and = ' / ';
            }
            $cert['RefCertDesc'] = $certs->certDesc.$this->and.$certs->courseDesc;
            $cert['RefCertDate'] = $certs->certDate.$this->and.$certs->courseDate;
        } else {
            $cert['RefCertDesc'] = $certs->certDesc;
            $cert['RefCertDate'] = $certs->certDate;
        }

        return $cert;
    }

    /**
     * @param stdClass $jsCert
     * @return array|null
     */
    private function getCertificationsAssessor(stdClass $jsCert)
    {
        if (empty($jsCert)) {
            return null;
        }

        $certs = $this->parseCerts($jsCert, 'Assessor');
        $this->and = '';

        if (array_search($certs->courseDesc, $this->assessMeta) > array_search($certs->certDesc, $this->assessMeta)) {
            if (!empty($certs->certDesc)) {
                $this->and = ' / ';
            }
            $cert['AssessorCertDesc'] = $certs->certDesc.$this->and.$certs->courseDesc;
            $cert['AssessorCertDate'] = $certs->certDate.$this->and.$certs->courseDate;
        } else {
            $cert['AssessorCertDesc'] = $certs->certDesc;
            $cert['AssessorCertDate'] = $certs->certDate;
        }

        return $cert;
    }

    /**
     * @param stdClass $jsCert
     * @return array|null
     */
    private function getCertificationsInstructor(stdClass $jsCert)
    {
        if (empty($jsCert)) {
            return null;
        }

        $certs = $this->parseCerts($jsCert, 'Instructor');

        if (array_search($certs->courseDesc, $this->instMeta) > array_search($certs->certDesc, $this->instMeta)) {
            if (!empty($certs->certDesc)) {
                $this->and = ' / ';
            }
            $cert['InstCertDesc'] = $certs->certDesc.$this->and.$certs->courseDesc;
            $cert['InstCertDate'] = $certs->certDate.$this->and.$certs->courseDate;
        } else {
            $cert['InstCertDesc'] = $certs->certDesc;
            $cert['InstCertDate'] = $certs->certDate;
        }

        return $cert;

    }

    /**
     * @param stdClass $jsCert
     * @return array|null
     */
    private function getCertificationsInstructorEvaluator(stdClass $jsCert)
    {
        if (empty($jsCert)) {
            return null;
        }

        $certs = $this->parseCerts($jsCert, 'Evaluator');

        if (array_search($certs->courseDesc, $this->instEvalMeta) > array_search(
                $certs->certDesc,
                $this->instEvalMeta
            )) {
            if (!empty($certs->certDesc)) {
                $this->and = ' / ';
            }
            $cert['InstEvalCertDesc'] = $certs->certDesc.$this->and.$certs->courseDesc;
            $cert['InstEvalCertDate'] = $certs->certDate.$this->and.$certs->courseDate;
        } else {
            $cert['InstEvalCertDesc'] = $certs->certDesc;
            $cert['InstEvalCertDate'] = $certs->certDate;
        }

        return $cert;

    }

    private function parseCerts(stdClass $jsCert, string $type)
    {
        $certs = (object)[
            'certDesc' => '',
            'certDate' => '',
            'courseDesc' => '',
            'courseDate' => '',
        ];

        switch ($type) {
            case 'Official':
            case 'Referee':
                $jsKey = $jsCert->VolunteerCertificationsReferee;
                $meta = $this->refMeta;
                break;
            case 'Assessor':
                $jsKey = $jsCert->VolunteerCertificationsReferee;
                $meta = $this->assessMeta;
                break;
            case 'Instructor':
                $jsKey = $jsCert->VolunteerCertificationsInstructor;
                $meta = $this->instMeta;
                break;
            case 'Evaluator':
                $jsKey = $jsCert->VolunteerCertificationsInstructor;
                $meta = $this->instEvalMeta;
                break;
            default:
                $jsKey = null;
                $meta = null;
        }

        if (!is_null($jsKey)) {
            foreach ($jsKey as $k => $cls) {
                if (is_bool(strpos($cls->CertificationDesc, 'Course'))) {
                    if (array_search($cls->CertificationDesc, $meta) > array_search(
                            $certs->certDesc,
                            $meta
                        )) {
                        $certs->certDesc = $cls->CertificationDesc;
                        $certs->certDate = $this->phpDate($cls->CertificationDate);

//                        if($certs->certDesc == 'National Referee' AND $certs->certDate < '2004-07-01') {
//                            $certs->certDesc = 'National 1 Referee';
//                        }
                    }
                } else {
                    if (array_search($cls->CertificationDesc, $meta) > array_search(
                            $certs->courseDesc,
                            $meta
                        )) {
                        $certs->courseDesc = $cls->CertificationDesc;
                        $certs->courseDate = $this->phpDate($cls->CertificationDate);
                    }
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

        $certs['CDCDate'] = '';
        $certs['SafeHavenDate'] = '';
        foreach ($jsCert->VolunteerCertificationsSafeHaven as $k => $cls) {
            if (strpos($cls->CertificationDesc, 'CDC Concussion Awareness') !== false) {
                if ($this->phpDate($cls->CertificationDate) > $certs['CDCDate']) {
                    $certs['CDCDate'] = $this->phpDate($cls->CertificationDate);
                }
            }
            if (strpos($cls->CertificationDesc, 'AYSOs Safe Haven') !== false OR
                strpos($cls->CertificationDesc, 'AYSOs Refugio Seguro') !== false) {
                if ($this->phpDate($cls->CertificationDate) > $certs['SafeHavenDate']) {
                    $certs['SafeHavenDate'] = $this->phpDate($cls->CertificationDate);
                }
            }
        }

        return $certs;
    }

}
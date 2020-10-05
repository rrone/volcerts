<?php

namespace App\Service;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Form\ClearableErrorsInterface;

class VolCerts
{

    /**
     * @var array
     */
    private $hdrs = [
        'AYSOID',
        'Full Name',
        'Type',
        'SAR',
        'MY',
        'Safe Haven Date',
        'CDC Date',
        'SCA Date',
        'Ref Cert Desc',
        'Ref Cert Date',
        'Assessor Cert Desc',
        'Assessor Cert Date',
        'Inst Cert Desc',
        'Inst Cert Date',
        'Inst Eval Cert Desc',
        'Inst Eval Cert Date',
        'Coach Cert Desc',
        'Coach Cert Date',
        'Data Source',
    ];

    /**
     * @var array
     */
    private $keys = [
        'AYSOID',
        'FullName',
        'Type',
        'SAR',
        'MY',
        'SafeHavenDate',
        'CDCDate',
        'SCADate',
        'RefCertDesc',
        'RefCertDate',
        'AssessorCertDesc',
        'AssessorCertDate',
        'InstCertDesc',
        'InstCertDate',
        'InstEvalCertDesc',
        'InstEvalCertDate',
        'CoachCertDesc',
        'CoachCertDate',
        'DataSource',
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
        foreach ($idList as $id) {
            $id = intval($id);
            if (10000000 < $id && $id < 999999999) {
                $ids[] = $id;
            }
        }
        if (empty($ids)) {
            return null;
        }

        $idList = $ids;

        //size the groups to avoid gateway timeouts -- SiteGround limitation
        $groupSize = 30;
        $certsGroup = [];
        for ($k = 0; $k < count($idList); $k += $groupSize) {
            $certsGroup[] = array_slice($idList, $k, $groupSize);
        }

        $certs = [];
        foreach ($certsGroup as $group) {
            $certs = array_merge($certs, (new CurlWorker)->curl_multi_get(VIEW_CERT_URL, $group));
        }

        $certData = [];
        foreach ($certs as $id => $cert) {
            $certData[] = $this->parseCertData($idList[$id], $cert);
        }

        return $certData;
    }


    /**
     * @param int $id
     * @param $certData
     * @return array|string
     */
    private function parseCertData(int $id, $certData)
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
     * @param string $nodeValue
     * @return array
     */
    private function parseNodeValue(int $id, string $nodeValue)
    {
        if (is_null($nodeValue)) {
            return null;
        }

        $cert['AYSOID'] = $id;
        $cert['FullName'] = '*** Volunteer not found ***';
        $cert['Type'] =
        $cert['SAR'] =
        $cert['MY'] =
        $cert['SafeHavenDate'] =
        $cert['CDCDate'] =
        $cert['SCADate'] =
        $cert['RefCertDesc'] =
        $cert['RefCertDate'] =
        $cert['AssessorCertDesc'] =
        $cert['AssessorCertDate'] =
        $cert['InstCertDesc'] =
        $cert['InstCertDate'] =
        $cert['InstEvalCertDesc'] =
        $cert['InstEvalCertDate'] =
        $cert['CoachCertDesc'] =
        $cert['CoachCertDate'] = '';

        $certList = [];

        $nv = json_decode($nodeValue);
        if ($nv->ReturnStatus == 0) {
            $certDetails = $nv->VolunteerCertificationDetails;

            $certRef = (new RefCerts($certDetails))->getCertifications();
            $certList = array_merge($certList, $certRef);

            $certRef = (new AssessorCerts($certDetails))->getCertifications();
            $certList = array_merge($certList, $certRef);

            $certInstructor = (new InstructorCerts($certDetails))->getCertifications();
            $certList = array_merge($certList, $certInstructor);

            $certInstructor = (new InstructorEvaluatorCerts($certDetails))->getCertifications();
            $certList = array_merge($certList, $certInstructor);

            $certCoach = (new CoachCerts($certDetails))->getCertifications();
            $certList = array_merge($certList, $certCoach);

            $certSH = (new SafeHavenCerts($certDetails))->getCertifications();
            $certList = array_merge($certList, $certSH);

            $cert['AYSOID'] = $certDetails->VolunteerAYSOID;
            $fullName = explode(",", $certDetails->VolunteerFullName);
            $cert['FullName'] = trim(ucwords(strtolower($fullName[1].' '.$fullName[0])));
            $cert['Type'] = $certDetails->Type;

            $sar = explode('<br>---<br>', $certDetails->VolunteerSAR);
            $s = isset($sar[0]) ? ltrim($sar[0], '0') : null;
            $a = isset($sar[1]) ? ltrim($sar[1], '0') : null;
            $r = isset($sar[2]) ? ltrim($sar[2], '0') : null;
            if (!is_null($s)) {
                $sar = $s;
            }
            if (!is_null($a)) {
                $sar .= '<br>---<br>'.$a;
            }
            if (!is_null($r)) {
                $sar .= '<br>---<br>'.$r;
            }
            $cert['SAR'] = $sar;
            $cert['MY'] = $certDetails->VolunteerMembershipYear;

            if (!empty($certList)) {
                $c = $certList;

                $cert['SafeHavenDate'] = isset($c['SafeHavenDate']) ? $c['SafeHavenDate'] : $cert['SafeHavenDate'];
                $cert['CDCDate'] = isset($c['CDCDate']) ? $c['CDCDate'] : $cert['CDCDate'];
                $cert['SCADate'] = isset($c['SCADate']) ? $c['SCADate'] : $cert['SCADate'];

                $cert['RefCertDesc'] = isset($c['RefCertDesc']) ? $c['RefCertDesc'] : $cert['RefCertDesc'];
                $cert['RefCertDate'] = isset($c['RefCertDate']) ? $c['RefCertDate'] : $cert['RefCertDate'];

                $cert['AssessorCertDesc'] = isset($c['AssessorCertDesc']) ? $c['AssessorCertDesc'] : $cert['AssessorCertDesc'];
                $cert['AssessorCertDate'] = isset($c['AssessorCertDate']) ? $c['AssessorCertDate'] : $cert['AssessorCertDate'];

                $cert['InstCertDesc'] = isset($c['InstCertDesc']) ? $c['InstCertDesc'] : $cert['InstCertDesc'];
                $cert['InstCertDate'] = isset($c['InstCertDate']) ? $c['InstCertDate'] : $cert['InstCertDate'];

                $cert['InstEvalCertDesc'] = isset($c['InstEvalCertDesc']) ? $c['InstEvalCertDesc'] : $cert['InstEvalCertDesc'];
                $cert['InstEvalCertDate'] = isset($c['InstEvalCertDate']) ? $c['InstEvalCertDate'] : $cert['InstEvalCertDate'];

                $cert['CoachCertDesc'] = isset($c['CoachCertDesc']) ? $c['CoachCertDesc'] : $cert['CoachCertDesc'];
                $cert['CoachCertDate'] = isset($c['CoachCertDate']) ? $c['CoachCertDate'] : $cert['CoachCertDate'];
            }
        }

        return $cert;
    }

}
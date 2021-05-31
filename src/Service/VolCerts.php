<?php

namespace App\Service;

use Symfony\Component\DomCrawler\Crawler;

class VolCerts
{

    /**
     * @var array
     */
    private array $hdrs = [
        'AYSOID' => 'AYSOID',
        'FullName' => 'Full Name',
        'Type' => 'Type',
        'SAR' => 'SAR',
        'MY' => 'MY',
        'SafeHavenDate' => 'Safe Haven Date',
        'CDCDate' => 'CDC Date',
        'SCADate' => 'SCA Date',
        'RefCertDesc' => 'Ref Cert Desc',
        'RefCertDate' => 'Ref Cert Date',
        'AssessorCertDesc' => 'Assessor Cert Desc',
        'AssessorCertDate' => 'Assessor Cert Date',
        'InstCertDesc' => 'Inst Cert Desc',
        'InstCertDate' => 'Inst Cert Date',
        'InstEvalCertDesc' => 'Inst Eval Cert Desc',
        'InstEvalCertDate' => 'Inst Eval Cert Date',
        'CoachCertDesc' => 'Coach Cert Desc',
        'CoachCertDate' => 'Coach Cert Date',
        'DataSource' => 'Data Source',
    ];

    /**
     * @return array
     */
    public function getHdrs(): array
    {
        return array_values($this->hdrs);
    }

    /**
     * @return array
     */
    public function getKeys(): array
    {
        return array_keys($this->hdrs);
    }


    /**
     * @param array $idList
     * @return array
     */
    public function retrieveVolsCertData(array $idList): ?array
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
        $groupSize = 15;
        $certsGroup = [];
        for ($k = 0; $k < count($idList); $k += $groupSize) {
            $certsGroup[] = array_slice($idList, $k, $groupSize);
        }

        $certs = [];
        foreach ($certsGroup as $group) {
            $c_get = (new CurlWorker)->curl_multi_get(VIEW_CERT_URL, $group);
            if (!empty($c_get)) {
                $certs = array_merge($certs, $c_get);
            }
        }

        $certData = [];
        foreach ($certs as $id => $cert) {
            if (!empty($cert)) {
                $certData[] = $this->parseCertData($idList[$id], $cert);
            }
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
            $nv['DataSource'] = DATA_SOURCE;

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
    private function parseNodeValue(int $id, string $nodeValue): ?array
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
            $cert['FullName'] = trim(ucwords(strtolower($fullName[1] . ' ' . $fullName[0])));
            $cert['Type'] = $certDetails->Type;

            $sar = explode('<br>---<br>', $certDetails->VolunteerSAR);
            $s = isset($sar[0]) ? ltrim($sar[0], '0') : null;
            $a = isset($sar[1]) ? ltrim($sar[1], '0') : null;
            $r = isset($sar[2]) ? ltrim($sar[2], '0') : null;
            if (!is_null($s)) {
                $sar = $s;
            }
            if (!is_null($a)) {
                $sar .= '<br>---<br>' . $a;
            }
            if (!is_null($r)) {
                $sar .= '<br>---<br>' . $r;
            }
            $cert['SAR'] = $sar;
            $cert['MY'] = $certDetails->VolunteerMembershipYear;

            if (!empty($certList)) {
                $c = $certList;

                $cert['SafeHavenDate'] = $c['SafeHavenDate'] ?? $cert['SafeHavenDate'];
                $cert['CDCDate'] = $c['CDCDate'] ?? $cert['CDCDate'];
                $cert['SCADate'] = $c['SCADate'] ?? $cert['SCADate'];

                $cert['RefCertDesc'] = $c['RefCertDesc'] ?? $cert['RefCertDesc'];
                $cert['RefCertDate'] = $c['RefCertDate'] ?? $cert['RefCertDate'];

                $cert['AssessorCertDesc'] = $c['AssessorCertDesc'] ?? $cert['AssessorCertDesc'];
                $cert['AssessorCertDate'] = $c['AssessorCertDate'] ?? $cert['AssessorCertDate'];

                $cert['InstCertDesc'] = $c['InstructorCertDesc'] ?? $cert['InstructorCertDesc'];
                $cert['InstCertDate'] = $c['InstructorCertDate'] ?? $cert['InstructorCertDate'];

                $cert['InstEvalCertDesc'] = $c['InstEvalCertDesc'] ?? $cert['InstEvalCertDesc'];
                $cert['InstEvalCertDate'] = $c['InstEvalCertDate'] ?? $cert['InstEvalCertDate'];

                $cert['CoachCertDesc'] = $c['CoachCertDesc'] ?? $cert['CoachCertDesc'];
                $cert['CoachCertDate'] = $c['CoachCertDate'] ?? $cert['CoachCertDate'];

            }
        }

        return $cert;
    }

}
<?php

namespace App\Service;

abstract class AbstractVolCerts
{
    protected $jsCert;

    protected $jsKey;

    protected $meta;

    protected $certs;

    protected $cert;

    protected $certList;

    public function getCertifications()
    {
        if (empty($this->jsCert)) {
            return null;
        }

        $this->cert = null;

        $this->certList = [];

    }

    protected function parseCerts()
    {
        $certs = (object)[
            'certDesc' => '',
            'certDate' => '',
            'courseDesc' => '',
            'courseDate' => '',
        ];

        if (!is_null($this->jsKey)) {
            foreach ($this->jsKey as $k => $cls) {
                if (is_bool(strpos($cls->CertificationDesc, 'Course'))) {
                    if (array_search($cls->CertificationDesc, $this->meta) > array_search(
                            $certs->certDesc,
                            $this->meta
                        )) {
                        $certs->certDesc = $cls->CertificationDesc;
                        $certs->certDate = $this->phpDate($cls->CertificationDate);

                        $certs->certDesc = str_replace(' & ', '', $certs->certDesc);

//                        if($certs->certDesc == 'National Referee' AND $certs->certDate < '2004-07-01') {
//                            $certs->certDesc = 'National 1 Referee';
//                        }
                    }
                } else {
                    if (array_search($cls->CertificationDesc, $this->meta) > array_search(
                            $certs->courseDesc,
                            $this->meta
                        )) {
                        $certs->courseDesc = $cls->CertificationDesc;
                        $certs->courseDate = $this->phpDate($cls->CertificationDate);
                    }
                }
            }
        }

        return $certs;

    }

    /**
     * @param string $certDate
     * @return false|string
     */
    protected function phpDate(string $certDate)
    {
        if ($certDate == '/Date(-62135568000000)/') {
            return '1964-09-15';
        }

        $ts = preg_replace('/[^0-9]/', '', $certDate);

        return date("Y-m-d", $ts / 1000);
    }

    protected function prepareCertList()
    {
        if (!empty($this->cert)) {
            foreach ($this->cert as $k => $c) {
                $this->certList[$k] = $c;
            }
        }
    }
}
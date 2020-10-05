<?php

namespace App\Service;


class RefCerts extends AbstractVolCerts
{
    public function __construct($certDetails)
    {
        $this->jsCert = $certDetails;
    }

    /**
     * @var array
     */
    private $certMeta = [
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
     * @return array|null
     */
    public function getCertifications()
    {
        parent::getCertifications();

        $this->certs = $this->parseCerts();

        if ($this->certs->certDesc == 'Safe Haven Referee')
            return null;

        $this->cert['RefCertDesc'] = $this->certs->certDesc;
        $this->cert['RefCertDate'] = $this->certs->certDate;
        if (array_search($this->certs->courseDesc, $this->certMeta) > array_search($this->certs->certDesc, $this->certMeta)) {
            if (!empty($this->certs->certDesc)) {
                $this->cert['RefCertDesc'] .= '<br>---<br>';
                $this->cert['RefCertDate'] .= '<br>---<br>';
            }
            $this->cert['RefCertDesc'] .= $this->certs->courseDesc;
            $this->cert['RefCertDate'] .= $this->certs->courseDate;
        }
        $this->prepareCertList();

        return $this->certList;
    }

    protected function parseCerts()
    {
        $this->jsKey = $this->jsCert->VolunteerCertificationsReferee;
        $this->meta = $this->certMeta;

        return parent::parseCerts();

    }

}
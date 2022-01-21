<?php

namespace App\Service;


class RefereeCerts extends AbstractVolCerts
{
    public function __construct($certDetails)
    {
        $this->jsCert = $certDetails;
    }

    /**
     * @var array
     */
    private array $certMeta = [
        '',     //blank required to match first certification
        'Z-Online Regional Referee Without Safe Haven',
        'Z-Online Regional Referee Course',
        'Z-Online Regional Referee',
        'Regional Referee Online Companion Course',
        'Z-Online 8U Official',
        'U-8 Official & Safe Haven Referee',
        'U-8 Official',
        'U8 Official',
        '8U Official',
        'Assistant Referee & Safe Haven Referee',
        'Assistant Referee',
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
    public function getCertifications(): ?array
    {
        parent::getCertifications();

        $this->certs = $this->parseCerts();

        if ($this->certs->certDesc == 'Safe Haven Referee')
            return null;

        $this->certs->certDesc = str_replace(' & Safe Haven Referee', '', $this->certs->certDesc);
        $this->certs->certDesc = str_replace('U-8 Official', '8U Official', $this->certs->certDesc);
        $this->certs->certDesc = str_replace('U8 Official', '8U Official', $this->certs->certDesc);

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

    protected function parseCerts(): object
    {
        $this->jsKey = $this->jsCert->VolunteerCertificationsReferee;

        $onlineRegional = ['Z-Online Regional' => false, 'Online Companion' => false];
        $rroc = null;
        $cKey = [];

        foreach ($this->jsKey as $key => $cls) {
            $this->jsKey[$key]->CertificationDesc = ucwords($cls->CertificationDesc);
            $cKey[] = $cls;
            switch ($cls->CertificationDesc) {
                case 'Z-Online Regional Referee Without Safe Haven':
                case 'Z-Online Regional Referee Course':
                case 'Z-Online Regional Referee':
                    $onlineRegional['Z-Online Regional'] = true;
                    break;
                case 'Regional Referee Online Companion Course':
                    $onlineRegional['Online Companion'] = true;
                    $rroc = $cls;
                    break;
            }
        }

        if ($onlineRegional['Z-Online Regional'] and $onlineRegional['Online Companion']) {
            $cKey[] = (object)['CertificationDesc' => 'Regional Referee', 'CertificationDate' => $rroc->CertificationDate];
        }

        $this->jsKey = $cKey;
        $this->meta = $this->certMeta;

        return parent::parseCerts();

    }

}
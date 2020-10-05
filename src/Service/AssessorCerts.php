<?php

namespace App\Service;


class AssessorCerts extends AbstractVolCerts
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
        'Referee Assessor Course',
        'Referee Assessor',
        'National Referee Assessor Course',
        'National Referee Assessor',
    ];


    /**
     * @return array|null
     */
    public function getCertifications()
    {
        parent::getCertifications();

        $this->certs = $this->parseCerts();

        $this->cert['AssessorCertDesc'] = $this->certs->certDesc;
        $this->cert['AssessorCertDate'] = $this->certs->certDate;
        if (array_search($this->certs->courseDesc, $this->certMeta) > array_search($this->certs->certDesc, $this->certMeta)) {
            if (!empty($this->certs->certDesc)) {
                $this->cert['AssessorCertDesc'] .= '<br>---<br>';
                $this->cert['AssessorCertDate'] .= '<br>---<br>';
            }
            $this->cert['AssessorCertDesc'] .= $this->certs->courseDesc;
            $this->cert['AssessorCertDate'] .= $this->certs->courseDate;
        }

        return $this->cert;
    }

    public function parseCerts()
    {
        $this->jsKey = $this->jsCert->VolunteerCertificationsReferee;
        $this->meta = $this->certMeta;

        return parent::parseCerts();

    }

}
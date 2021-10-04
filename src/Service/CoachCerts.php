<?php

namespace App\Service;


class CoachCerts extends AbstractVolCerts
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
        'B Coach',
        'C Coach',
        'Z- Online Playground and Schoolyard Program Leader',
        'Z-Online 6U Coach',
        '6U Coach',
        'Z-Online 8U Coach',
        'U-8 Coach',
        'Z-Online 10U Coach',
        '10U Coach',
        '12U Coach - Cross Certification',
        'Z-Online 12U Coach Pre Course',
        '12U Coach',
        'Intermediate Coach - Cross Certification',
        'Z-Online Intermediate Coach Pre Course',
        'Intermediate Coach',
        'Advanced Coach - Cross Certification',
        'Z-Online Advanced Coach Pre Course',
        'Advanced Coach',
        'National Coaching Course',
        'National Coach',
    ];

    /**
     * @return array|null
     */
    public function getCertifications(): ?array
    {
        parent::getCertifications();

        $this->certs = $this->parseCerts();

        if ($this->certs->certDesc == 'Safe Haven Coach')
            return null;

        $this->cert['CoachCertDesc'] = $this->certs->certDesc;
        $this->cert['CoachCertDate'] = $this->certs->certDate;
        if (array_search($this->certs->courseDesc, $this->certMeta) > array_search($this->certs->certDesc, $this->certMeta)) {
            if (!empty($this->certs->certDesc)) {
                $this->cert['CoachCertDesc'] .= '<br>---<br>';
                $this->cert['CoachCertDate'] .= '<br>---<br>';
            }
            $this->cert['CoachCertDesc'] .= $this->certs->courseDesc;
            $this->cert['CoachCertDate'] .= $this->certs->courseDate;
        }

        return $this->cert;
    }

    public function parseCerts(): object
    {
        $this->jsKey = $this->jsCert->VolunteerCertificationsCoach;
        $this->meta = $this->certMeta;

        return parent::parseCerts();

    }

}
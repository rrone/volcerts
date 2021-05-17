<?php

namespace App\Service;


class InstructorCerts extends AbstractVolCerts
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
        'Introduction to Instruction',
        'Referee Instructor Course',
        'Referee Instructor',
        'Regional Referee Instructor',
        'Intermediate Referee Instructor',
        'Advanced Referee Instructor Course',
        'Advanced Referee Instructor',
        'National Referee Instructor',
    ];

    /**
     * @return array|null
     */
    public function getCertifications(): ?array
    {
        parent::getCertifications();

        $this->certs = $this->parseCerts();

        $this->cert['InstructorCertDesc'] = $this->certs->certDesc;
        $this->cert['InstructorCertDate'] = $this->certs->certDate;
        if (array_search($this->certs->courseDesc, $this->certMeta) > array_search($this->certs->certDesc, $this->certMeta)) {
            if (!empty($this->certs->certDesc)) {
                $this->cert['InstructorCertDesc'] .= '<br>---<br>';
                $this->cert['InstructorCertDate'] .= '<br>---<br>';
            }
            $this->cert['InstructorCertDesc'] .= $this->certs->courseDesc;
            $this->cert['InstructorCertDate'] .= $this->certs->courseDate;
        }

        return $this->cert;
    }

    public function parseCerts(): object
    {
        $this->jsKey = $this->jsCert->VolunteerCertificationsInstructor;
        $this->meta = $this->certMeta;

        return parent::parseCerts();

    }

}
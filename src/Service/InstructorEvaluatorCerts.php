<?php

namespace App\Service;


class InstructorEvaluatorCerts extends AbstractVolCerts
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
        'Referee Instructor Evaluator Course',
        'Referee Instructor Evaluator',
    ];

    /**
     * @return array|null
     */
    public function getCertifications()
    {
        parent::getCertifications();

        $this->certs = $this->parseCerts();

        $this->cert['InstEvalCertDesc'] = $this->certs->certDesc;
        $this->cert['InstEvalCertDate'] = $this->certs->certDate;
        if (array_search($this->certs->courseDesc, $this->certMeta) > array_search($this->certs->certDesc, $this->certMeta)) {
            if (!empty($this->certs->certDesc)) {
                $this->cert['InstEvalCertDesc'] .= '<br>---<br>';
                $this->cert['InstEvalCertDate'] .= '<br>---<br>';
            }
            $this->cert['InstEvalCertDesc'] .= $this->certs->courseDesc;
            $this->cert['InstEvalCertDate'] .= $this->certs->courseDate;
        }

        return $this->cert;
    }

    public function parseCerts()
    {
        $this->jsKey = $this->jsCert->VolunteerCertificationsInstructor;
        $this->meta = $this->certMeta;

        return parent::parseCerts();

    }

}
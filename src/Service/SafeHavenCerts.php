<?php

namespace App\Service;

class SafeHavenCerts extends AbstractVolCerts
{
    public function __construct($certDetails)
    {
        $this->jsCert = $certDetails;
    }

    /**
     * @return array|null
     */
    public function getCertifications(): ?array
    {
        if (is_null($this->jsCert)) {
            return null;
        }

        $certs['CDCDate'] = '';
        $certs['SafeHavenDate'] = '';
        $certs['SCADate'] = '';
        foreach ($this->jsCert->VolunteerCertificationsSafeHaven as $cls) {
            $date = $this->phpDate($cls->CertificationDate);
            if (strpos($cls->CertificationDesc, 'Concussion Awareness') !== false) {
                if ($date > $certs['CDCDate']) {
                    $certs['CDCDate'] = $date;
                }
            }
            if (strpos($cls->CertificationDesc, 'AYSOs Safe Haven') !== false or
                strpos($cls->CertificationDesc, 'AYSOs Refugio Seguro') !== false) {
                if ($date > $certs['SafeHavenDate']) {
                    $certs['SafeHavenDate'] = $date;
                }
            }
            if (strpos($cls->CertificationDesc, 'NFHS Sudden Cardiac Arrest Training') !== false or
                strpos($cls->CertificationDesc, 'Z- Online Sudden Cardiac Arrest Training') !== false) {
                {
                    if ($date > $certs['SCADate']) {
                        $certs['SCADate'] = $date;
                    }
                }
            }
        }

        return $certs;
    }

}
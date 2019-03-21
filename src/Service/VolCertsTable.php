<?php /** @noinspection SpellCheckingInspection */

namespace App\Service;

use DateTime;
use DateTimeZone;

define("CERT_URL", "https://national.ayso.org/Volunteers/ViewCertification?UserName=");

class VolCertsTable
{
    /**
     * @var VolCerts $volCerts
     */
    private $volCerts;
    /**
     * @var string
     */
    private $filename;

    /**
     * @const integer
     */
    CONST MaxIDS = 6000;

    /**
     * @const string
     */
    CONST TZ = 'PST';

    /**
     * VolCertsEntity constructor
     * @param VolCerts $volCerts
     */
    public function __construct(VolCerts $volCerts)
    {
        set_time_limit(0);

        $this->volCerts = $volCerts;
    }

    /**
     * @return array
     */
    private function loadFile()
    {
        $arrIds = [];

        $fileData = fopen($this->filename, 'r');
        while ($row = fgets($fileData)) {
            $row = (int)$row;
            if ($row > 0) {
                $arrIds[] = $row;
            };
        }

        $arrIds = array_slice($arrIds, 0, self::MaxIDS);

        return $arrIds;
    }

    /**
     * @param string $fileName
     * @return array
     */
    public function retrieveVolCertData($fileName)
    {
        $this->filename = $fileName;

        $arrIds = $this->loadFile();

        $volCerts = $this->volCerts->retrieveVolsCertData($arrIds);

        foreach ($volCerts as &$volCert) {

            $aysoID = $volCert['AYSOID'];
            $url = CERT_URL.$aysoID;
            $hrefAysoID = "<a href=\"$url\" target=\"_blank\">$aysoID</a>";
            $volCert['AYSOID'] = $hrefAysoID;
        }

//        $volCerts = [];
//        foreach ($arrIds as $id) {
//            $volCerts[$id] = $this->volCerts->retrieveVolCertData($id);
//        }

        return $volCerts;
    }

    /**
     * @param array $content
     * @return array|string
     * @throws \Exception
     */
    public function renderView(array $content)
    {
        if (is_null($content)) {
            return $content;
        }

        $html = <<<EOD
<table id="vol_certs" class="display">
<thead>
<tr>
EOD;
        $hdrs = $this->volCerts->getHdrs();
        foreach ($hdrs as $hdr) {
            $html .= <<<EOD
<th>$hdr</th>
EOD;
        }

        $html .= <<<EOD
</tr>
</thead>
<tbody>
EOD;

        $keys = $this->volCerts->getKeys();

        foreach ($content as $i => $cert) {
            $html .= <<<EOD
<tr>
EOD;
            foreach ($keys as $key) {

                $html .= <<<EOD
<td>{$cert[$key]}</td>
EOD;
            }

            $html .= <<<EOD
</tr>
EOD;
        }

        $createDate = $this->getTimestamp().' '.self::TZ;
        $html .= <<<EOD
</tbody>
</table> 

<p class="createdOn">Created at $createDate </p>     
EOD;

        return $html;
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function getTimestamp()
    {
        $utc = date("Y-m-d H:i:s");;

        $ts = new DateTime($utc, new DateTimeZone('UTC'));
        $ts->setTimezone(new DateTimeZone(self::TZ));

        return $ts->format('Y - m - d H:i');
    }

}
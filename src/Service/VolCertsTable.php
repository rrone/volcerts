<?php /** @noinspection SpellCheckingInspection */

namespace App\Service;

use DateTime;
use DateTimeZone;

use Exception;

use PhpOffice\PhpSpreadsheet;

define("CERT_URL", "https://national.ayso.org/Volunteers/ViewCertification?UserName=");

class VolCertsTable
{
    /**
     * @var VolCerts $volCerts
     */
    private $volCerts;

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
     * @param $inputFileName
     * @return array
     */
    private function loadFile($inputFileName)
    {
        $arrIds = [];

        try {
            $arrIds = $this->loadXLSXFile($inputFileName);
        } catch (Exception $e) {

            return $arrIds;

        }

        return $arrIds;

    }

    /**
     * @param $inputFileName
     * @return array
     * @throws PhpSpreadsheet\Exception
     * @throws PhpSpreadsheet\Reader\Exception
     */
    private function loadXLSXFile($inputFileName)
    {
        $type = PhpSpreadsheet\IOFactory::identify($inputFileName);

        /**  Create a new Reader of the type defined in $inputFileType  **/
        $reader = PhpSpreadsheet\IOFactory::createReader($type);
        /**  Advise the Reader that we only want to load cell data  **/
        $reader->setReadDataOnly(true);

        $arrIds = [];

        /**  Load $inputFileName to a Spreadsheet Object  **/
        /** @var PhpSpreadsheet\Spreadsheet $xls */

        $xls = $reader->load($inputFileName);

        $tmp = $xls->getActiveSheet()
            ->rangeToArray(
                'A1:A25000',     // The worksheet range that we want to retrieve
                '',        // Value that should be returned for empty cells
                false,
                // Should formulas be calculated (the equivalent of getCalculatedValue() for each cell)
                false,        // Should values be formatted (the equivalent of getFormattedValue() for each cell)
                false         // Should the array be indexed by cell row and cell column
            );

        foreach ($tmp as $key => $id) {
            $id = implode($id);
            if ((int)$id > 0) {
                $arrIds[] = $id;
            };
        }

        return $arrIds;
    }

    /**
     * @param $fileName
     * @return array|string
     */
    public function retrieveVolCertData($fileName)
    {
        $arrIds = $this->loadFile($fileName);

        $volCerts = $this->volCerts->retrieveVolsCertData($arrIds);

        foreach ($volCerts as &$volCert) {

            $aysoID = $volCert['AYSOID'];
            $url = CERT_URL.$aysoID;
            $hrefAysoID = "<a href=\"$url\" target=\"_blank\">$aysoID</a>";
            $volCert['AYSOID'] = $hrefAysoID;
        }

        return $volCerts;
    }

    /**
     * @param array $content
     * @return array|string
     * @throws Exception
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
        if (empty($content)) {
            $html .= <<<EOD
<h6  class="error"><b>ERROR: <em>The file is not recognised as an CSV or Excel file type.</em></b></h6>
EOD;

        } else {
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
     * @throws Exception
     */
    protected function getTimestamp()
    {
        $utc = date("Y-m-d H:i:s");;

        $ts = new DateTime($utc, new DateTimeZone('UTC'));
        $ts->setTimezone(new DateTimeZone(self::TZ));

        return $ts->format('Y-m-d  H:i');
    }

}
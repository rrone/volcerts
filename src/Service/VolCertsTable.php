<?php /** @noinspection SpellCheckingInspection */

namespace App\Service;

use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet;

define("CERT_URL", "https://national.ayso.org/Volunteers/ViewCertification?UserName=");
define("NATIONAL_CERT_URL", "https://national.ayso.org/Volunteers/SelectVolunteerDetails?AYSOID=");

class VolCertsTable
{
    /**
     * @var VolCerts $volCerts
     */
    private $volCerts;

    /**
     * @var boolean $merge
     */
    private $mergeData;

    /**
     * @var array $dataIn
     */
    private $dataIn;

    /**
     * @var array $volCertData
     */
    private $volCertData;

    /**
     * VolCertsEntity constructor
     * @param VolCerts $volCerts
     */
    public function __construct(VolCerts $volCerts)
    {
        set_time_limit(0);

        $this->volCerts = $volCerts;
        $this->mergeData = false;
        $this->dataIn = [];
        $this->volCertData = [];
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

        $xls = $reader->load($inputFileName);

        $hrc = $xls->getActiveSheet()->getHighestRowAndColumn();
        $usedRange = "A1:" . $hrc['column'] . $hrc['row'];
        $tmp = $xls->getActiveSheet()
            ->rangeToArray(
                $usedRange,     // The worksheet range that we want to retrieve
                '',        // Value that should be returned for empty cells
                false,
                // Should formulas be calculated (the equivalent of getCalculatedValue() for each cell)
                false,        // Should values be formatted (the equivalent of getFormattedValue() for each cell)
                false         // Should the array be indexed by cell row and cell column
            );

        foreach ($tmp as $key => $row) {
            if ($key < 1) {
                array_shift($row);
                $this->dataIn[] = $row;
                continue;
            }

            if ((int)$row[0] > 0) {
                $arrIds[] = $id = $row[0];
                // remove the AYSOID; duplicate content
                array_shift($row);
                $this->dataIn[$id] = $row;
            }

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

        $vc = [];

        if (!empty($volCerts)) {
            foreach ($volCerts as $volCert) {

                $aysoID = $volCert['AYSOID'];
                $url = CERT_URL . $aysoID;
                $hrefAysoID = "<a href=\"$url\" target=\"_blank\">$aysoID</a>";
                $volCert['AYSOID'] = $hrefAysoID;
                $vc[$aysoID] = $volCert;
            }

            $this->volCertData = $vc;
        }

        return $vc;
    }

    /**
     * @param array $content
     * @return array|string
     * @throws Exception
     */
    public function renderView(array $content)
    {
        if (empty($content)) {
            return <<<EOD
<h6  class="error"><b>ERROR: <em>The file is not recognised as an CSV or Excel file type.</em></b></h6>
EOD;
        }
        $html = <<<EOD
<table id="vol_certs" class="display">
EOD;

        $html .= $this->renderHeaders();

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
            if ($this->mergeData) {
                foreach ($this->dataIn[$i] as $item) {
                    $html .= <<<EOD
<td>{$item}</td>
EOD;

                }
            }

            $html .= <<<EOD
</tr>
EOD;
        }


        $createDate = $this->getTimestamp() . ' Pacific Time';
        $html .= <<<EOD
</tbody>
</table> 
<br>
<p class="createdOn">Created at $createDate </p>     
EOD;

        return $html;
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function getTimestamp(): string
    {
        date_default_timezone_set('US/Pacific');
        $ts = new DateTime(date('Y-m-d  H:i'));

        return $ts->format('Y-m-d  H:i');
    }

    /**
     * @return array
     */
    public function getDataIn(): array
    {
        return $this->dataIn;
    }

    /**
     * @return array
     */
    public function getVolCertData(): array
    {
        return $this->volCertData;
    }

    public function setMerge(bool $merge)
    {
        $this->mergeData = $merge;
    }

    protected function renderHeaders()
    {
        $html = <<<EOD
<thead>
<tr>
EOD;
        $hdrs = $this->volCerts->getHdrs();
        foreach ($hdrs as $hdr) {
            $html .= <<<EOD

<th>$hdr</th>
EOD;
        }

        if ($this->mergeData) {
            foreach ($this->dataIn[0] as $hdr) {
                $html .= <<<EOD
<th>$hdr</th>
EOD;
            }
        }

        $html .= <<<EOD
</tr>
</thead>
EOD;
        return $html;
    }
}
<?php /** @noinspection SpellCheckingInspection */

namespace App\Service;

use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet;
use function PHPUnit\Framework\isType;

define("CERT_URL", "https://national.ayso.org/Volunteers/ViewCertification?UserName=");
define("NATIONAL_CERT_URL", "https://national.ayso.org/Volunteers/SelectVolunteerDetails?AYSOID=");
define("DATA_SOURCE", 'e3');

class VolCertsTable
{
    /**
     * @var VolCerts $volCerts
     */
    private VolCerts $volCerts;

    /**
     * @var array $dataIn
     */
    private array $dataIn;

    /**
     * @var array $dataOut
     */
    private array $dataOut;

    /**
     * @var array $volCertData
     */
    private array $volCertData;

    /**
     * VolCertsEntity constructor
     * @param VolCerts $volCerts
     */
    public function __construct(VolCerts $volCerts)
    {
        set_time_limit(0);

        $this->volCerts = $volCerts;
        $this->dataIn = [];
        $this->dataOut = [];
        $this->volCertData = [];
    }

    /**
     * @param $inputFileName
     * @return array
     */
    private function loadFile($inputFileName): array
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
    private function loadXLSXFile($inputFileName): array
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
     * @param $vCerts
     * @return array
     */
    private function linkAYSOID($vCerts): array
    {
        if (!empty($vCerts)) {
            foreach ($vCerts as $aysoID => &$vCert) {
                $url = CERT_URL . $aysoID;
                $hrefAysoID = "<a href=\"$url\" target=\"_blank\">$aysoID</a>";
                $vCert['AYSOID'] = $hrefAysoID;
            }
        }

        return $vCerts;
    }

    /**
     * @param $fileName
     * @param bool $addLink
     */
    public function retrieveVolCertData($fileName, bool $addLink = false)
    {
        $arrIds = $this->loadFile($fileName);

        $vCerts = $this->volCerts->retrieveVolsCertData($arrIds);

        if (is_null($vCerts)) {
            return;
        }

        $vc = [];
        foreach ($vCerts as $cert) {
            $vc[$cert['AYSOID']] = $cert;
        }

        $this->volCertData = $addLink ? $this->linkAYSOID($vc) : $vc;

    }

    /**
     * @return string
     * @throws Exception
     */
    public function renderView(): string
    {
        if (empty($this->dataOut)) {
            return <<<EOD
<h6  class="error"><b>ERROR: <em>The file is not recognised as an CSV or Excel file type.</em></b></h6>
EOD;
        }
        $html = <<<EOD
<table id="vol_certs" class="display">
EOD;

        $html .= $this->renderHeaders();
        foreach ($this->dataOut as $cert) {
            $html .= <<<EOD
<tr>
EOD;
            foreach ($cert as $d) {
                $d = trim((string) $d);
                $html .= <<<EOD
<td>$d</td>
EOD;
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

    /**
     * @param bool $merge
     * @return array
     */
    public function getDataOut(bool $merge = false): array
    {
        $keys = $this->volCerts->getKeys();
        $hdrs = $this->volCerts->getHdrs();

        foreach ($this->volCertData as $id => $certs) {
            $this->dataOut[$id] = array_combine($hdrs, array_values(array_merge(array_flip($keys), $certs)));
            if ($merge) {
                foreach ($this->dataIn as $k => $row) {
                    if ($k > 0) {
                        foreach ($this->dataIn[0] as $c => $hdr) {
                            $this->dataOut[$id][$hdr] = $this->dataIn[$id][$c];
                        }
                    }
                }
            }
        }

        return $this->dataOut;
    }

    /**
     * @return string
     */
    protected function renderHeaders(): string
    {
        if (empty($this->dataOut)) {
            return '';
        }

        $html = <<<EOD
<thead>
<tr>
EOD;
        $hdrs = array_values($this->dataOut)[0];
        foreach ($hdrs as $hdr => $v) {
            $html .= <<<EOD

<th>$hdr</th>
EOD;
        }

        $html .= <<<EOD
</tr>
</thead>
EOD;
        return $html;
    }
}
<?php

namespace App\Repository;

use Doctrine\DBAL\Connection;
use Doctrine;
use Exception;

/**
 * Class DataWarehouse
 * @package App\Controller
 */
class DataWarehouse
{

    /**
     * @var Connection $conn
     */
    protected Connection $conn;

    /**
     * @const BIGINT
     */
    const BIGINT = 9223372036854775807;

    /**
     * DataWarehouse constructor.
     * @param Connection $connection
     * @throws Exception
     */
    public function __construct(Connection $connection)
    {
        $this->conn = $connection;
    }

    /**
     * @param $adminId
     * @return null|int
     * @throws Doctrine\DBAL\Exception
     */
    public function getAYSOIDByAdminID($adminId): ?int
    {
        if (empty($adminId)) {
            return null;
        }

        $aysoId = $this->conn->fetchOne("SELECT `AYSOID` FROM `all.AdminIDAYSOID` WHERE `AdminID` = '$adminId' LIMIT 1");

        return intval($aysoId);
    }

    /**
     * @param $aysoId
     * @return string|null
     * @throws Doctrine\DBAL\Exception
     */
    public function getAdminIDByAYSOID($aysoId): ?string
    {
        if (empty($aysoId)) {
            return null;
        }

        $adminId = $this->conn->fetchOne("SELECT `AdminID` FROM `all.AdminIDAYSOID` WHERE `AYSOID` = '$aysoId' LIMIT 1");

        return $adminId;
    }

}

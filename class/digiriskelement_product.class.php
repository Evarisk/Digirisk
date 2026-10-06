<?php
/**
 * \file    class/digiriskelement_product.class.php
 * \ingroup digiriskdolibarr
 * \brief   Class for associating products to digirisk elements (GP/UT)
 */

/**
 * Class DigiriskElementProduct
 * Stores an association between a Dolibarr product and a DigiriskElement.
 */
class DigiriskElementProduct extends CommonObject
{
    /** @var string Module name */
    public $module = 'digiriskdolibarr';

    /** @var string Element type */
    public $element = 'digiriskelement_product';

    /** @var string Table without prefix */
    public $table_element = 'digiriskdolibarr_digiriskelement_product';

    /** @var int Does this object support multicompany module? */
    public $ismultientitymanaged = 1;

    /** @var int FK digiriskelement */
    public $fk_digiriskelement;

    /** @var int FK product */
    public $fk_product;

    /**
     * Constructor
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;
    }

    /**
     * Create association in database.
     *
     * @param  User $user      User object
     * @return int             rowid on success, <0 on error
     */
    public function create(User $user): int
    {
        global $conf;

        $this->db->begin();

        $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . $this->table_element . ' (';
        $sql .= 'entity, date_creation, fk_digiriskelement, fk_product, fk_user_creat';
        $sql .= ') VALUES (';
        $sql .= $conf->entity . ',';
        $sql .= "'" . $this->db->idate(dol_now()) . "',";
        $sql .= (int) $this->fk_digiriskelement . ',';
        $sql .= (int) $this->fk_product . ',';
        $sql .= (int) $user->id;
        $sql .= ')';

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->db->rollback();
            $this->error = $this->db->lasterror();
            return -1;
        }

        $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);

        $this->db->commit();
        return $this->id;
    }

    /**
     * Fetch from database.
     *
     * @param  int    $id   rowid
     * @return int          >0 on success, 0 if not found, <0 on error
     */
    public function fetch(int $id): int
    {
        $sql = 'SELECT rowid, entity, date_creation, tms,'
            . ' fk_digiriskelement, fk_product, fk_user_creat, fk_user_modif'
            . ' FROM ' . MAIN_DB_PREFIX . $this->table_element
            . ' WHERE rowid = ' . $id;

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->error = $this->db->lasterror();
            return -1;
        }

        $obj = $this->db->fetch_object($resql);
        if (!$obj) {
            return 0;
        }

        $this->id                 = (int) $obj->rowid;
        $this->entity             = (int) $obj->entity;
        $this->fk_digiriskelement = (int) $obj->fk_digiriskelement;
        $this->fk_product         = (int) $obj->fk_product;
        $this->date_creation      = $this->db->jdate($obj->date_creation);
        $this->tms                = $this->db->jdate($obj->tms);
        $this->fk_user_creat      = (int) $obj->fk_user_creat;
        $this->fk_user_modif      = (int) $obj->fk_user_modif;

        return 1;
    }

    /**
     * Delete an association.
     *
     * @param  User   $user
     * @param  bool   $notrigger
     * @return int               >0 on success, <0 on error
     */
    public function delete(User $user, bool $notrigger = false): int
    {
        $sql = 'DELETE FROM ' . MAIN_DB_PREFIX . $this->table_element
            . ' WHERE rowid = ' . (int) $this->id;

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->error = $this->db->lasterror();
            return -1;
        }

        return 1;
    }

    /**
     * Fetch all products associated to an element.
     *
     * @param  int   $fk_digiriskelement
     * @return array Array of DigiriskElementProduct objects, indexed by rowid
     */
    public function fetchAllByElement(int $fk_digiriskelement): array
    {
        global $conf;

        $sql = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . $this->table_element
            . ' WHERE fk_digiriskelement = ' . $fk_digiriskelement
            . ' AND entity = ' . $conf->entity
            . ' ORDER BY date_creation DESC';

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->error = $this->db->lasterror();
            return [];
        }

        $list = [];
        while ($obj = $this->db->fetch_object($resql)) {
            $r = new self($this->db);
            $r->fetch((int) $obj->rowid);
            $list[$r->id] = $r;
        }

        return $list;
    }
}

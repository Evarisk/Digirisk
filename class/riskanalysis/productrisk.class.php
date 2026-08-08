<?php
/**
 * \file    class/riskanalysis/productrisk.class.php
 * \ingroup digiriskdolibarr
 * \brief   Class for product risks (danger category + description + photos + protections)
 */

/**
 * Class ProductRisk
 * Stores a risk associated with a Dolibarr product.
 */
class ProductRisk extends CommonObject
{
    /** @var string Module name */
    public $module = 'digiriskdolibarr';

    /** @var string Element type */
    public $element = 'product_risk';

    /** @var string Table without prefix */
    public $table_element = 'digiriskdolibarr_product_risk';

    /** @var int Does this object support multicompany module? */
    public $ismultientitymanaged = 1;

    /** @var int FK product */
    public $fk_product;

    /** @var int Danger category position (from dangerCategories.json) */
    public $danger_category;

    /** @var string|null Description of the risk on this product */
    public $description;

    /** @var string|null JSON-encoded protections array */
    public $protections_json;

    /** @var int Status (1 = active) */
    public $status = 1;

    /**
     * Constructor
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;
    }

    /**
     * Create a product risk in database.
     *
     * @param  User $user      User object
     * @return int             rowid on success, <0 on error
     */
    public function create(User $user): int
    {
        global $conf;

        $this->db->begin();

        $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . $this->table_element . ' (';
        $sql .= 'ref, entity, date_creation, status, fk_product, danger_category, description, protections_json, fk_user_creat';
        $sql .= ') VALUES (';
        $sql .= "'" . $this->db->escape('(PROV)') . "',";
        $sql .= $conf->entity . ',';
        $sql .= "'" . $this->db->idate(dol_now()) . "',";
        $sql .= (int) $this->status . ',';
        $sql .= (int) $this->fk_product . ',';
        $sql .= (int) $this->danger_category . ',';
        $sql .= ($this->description !== null ? "'" . $this->db->escape($this->description) . "'" : 'NULL') . ',';
        $sql .= ($this->protections_json !== null ? "'" . $this->db->escape($this->protections_json) . "'" : 'NULL') . ',';
        $sql .= (int) $user->id;
        $sql .= ')';

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->db->rollback();
            $this->error = $this->db->lasterror();
            return -1;
        }

        $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);

        // Update ref with rowid-based provisional ref
        $refSql = 'UPDATE ' . MAIN_DB_PREFIX . $this->table_element
            . " SET ref = 'PR-" . $this->id . "'"
            . ' WHERE rowid = ' . $this->id;
        $this->db->query($refSql);

        $this->db->commit();
        return $this->id;
    }

    /**
     * Fetch a product risk from database.
     *
     * @param  int    $id   rowid
     * @return int          >0 on success, 0 if not found, <0 on error
     */
    public function fetch(int $id): int
    {
        $sql = 'SELECT rowid, ref, entity, date_creation, tms, status,'
            . ' fk_product, danger_category, description, protections_json, fk_user_creat, fk_user_modif'
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

        $this->id               = (int) $obj->rowid;
        $this->ref              = $obj->ref;
        $this->entity           = (int) $obj->entity;
        $this->status           = (int) $obj->status;
        $this->fk_product       = (int) $obj->fk_product;
        $this->danger_category  = (int) $obj->danger_category;
        $this->description      = $obj->description;
        $this->protections_json = $obj->protections_json;
        $this->date_creation   = $this->db->jdate($obj->date_creation);
        $this->tms             = $this->db->jdate($obj->tms);
        $this->fk_user_creat   = (int) $obj->fk_user_creat;
        $this->fk_user_modif   = (int) $obj->fk_user_modif;

        return 1;
    }

    /**
     * Fetch all risks for a given product.
     *
     * @param  int   $fk_product Product rowid
     * @return array             Array of ProductRisk objects, indexed by rowid
     */
    public function fetchAllByProduct(int $fk_product): array
    {
        global $conf;

        $sql = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . $this->table_element
            . ' WHERE fk_product = ' . $fk_product
            . ' AND entity = ' . $conf->entity
            . ' AND status > 0'
            . ' ORDER BY tms DESC, rowid DESC';

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

    /**
     * Update description and protections of a product risk.
     *
     * @param  User $user
     * @return int        >0 on success, <0 on error
     */
    public function update(User $user): int
    {
        $sql = 'UPDATE ' . MAIN_DB_PREFIX . $this->table_element . ' SET'
            . ' description = ' . ($this->description !== null ? "'" . $this->db->escape($this->description) . "'" : 'NULL') . ','
            . ' protections_json = ' . ($this->protections_json !== null ? "'" . $this->db->escape($this->protections_json) . "'" : 'NULL') . ','
            . ' tms = NOW(),'
            . ' fk_user_modif = ' . (int) $user->id
            . ' WHERE rowid = ' . (int) $this->id;

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->error = $this->db->lasterror();
            return -1;
        }

        return 1;
    }

    /**
     * Delete a product risk (and its associated photos).
     *
     * @param  User   $user
     * @param  bool   $notrigger
     * @return int               >0 on success, <0 on error
     */
    public function delete(User $user, bool $notrigger = false): int
    {
        $sql = 'UPDATE ' . MAIN_DB_PREFIX . $this->table_element
            . ' SET status = 0'
            . ' WHERE rowid = ' . (int) $this->id;

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->error = $this->db->lasterror();
            return -1;
        }

        return 1;
    }

    /**
     * Get decoded protections array.
     *
     * @return array  Array of ['position' => int, 'comment' => string]
     */
    public function getProtections(): array
    {
        if (empty($this->protections_json)) {
            return [];
        }
        $decoded = json_decode($this->protections_json, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Get the photo directory for this risk.
     *
     * @return string Absolute path to the photo directory
     */
    public function getPhotoDir(): string
    {
        global $conf;
        return $conf->digiriskdolibarr->multidir_output[$conf->entity]
            . '/medias/product/' . $this->fk_product . '/risks/' . $this->id . '/';
    }

    /**
     * List photos for this risk.
     *
     * @return array Array of filenames
     */
    public function getPhotos(): array
    {
        $dir = $this->getPhotoDir();
        if (!is_dir($dir)) {
            return [];
        }
        $files = [];
        foreach (dol_dir_list($dir, 'files', 0, '', '(\.thumb\.|_mini\.)') as $file) {
            $files[] = $file['name'];
        }
        return $files;
    }
}

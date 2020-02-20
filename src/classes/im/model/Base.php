<?php
/**
 * Parent class for CRUD operations on a single table
 *
 * Child class must have following constants defined:
 * DB_TABLE, PRIMARY_KEYS, DB_MODEL
 * Use /menu/model.php?table=TABLENAME to generate sample code
 *
 * == HOW TO USE BASE METHODS ==
 *
 * READ an existing record from the database, either:
 *  $obj = new CLASS($db);
 *  $obj->read($primary_key);
 * Or (shortcut):
 *  $obj = new CLASS($db, $primary_key);
 * (Note: with compound primary keys, use setKey($array) instead)
 * Then grab data using:
 *  $data = $obj->get();
 * Or for individual field values:
 *  $value = $obj->get($field_name);
 *
 * ADD OR CHANGE data items using:
 *  $obj->set([$field_name1=$value1, $field_name2=$value2]);
 * Note: must run create() or update() to commit changes to database (see below)
 *
 * VALIDATE data using:
 *  $obj->validate()
 * then if returns false, use:
 *  $obj->getErrors()
 * to return array of validation errors
 *
 * CREATE / UPDATE database record using:
 *  $obj->create() or $obj->update()
 * Note: these will run $obj->validate() if not already run since last data change
 *
 *
 *
*
*/
namespace im\model;
use \Exception;
use \im\exception\ValidationException;
use \im\helpers\Strings;
use \im\helpers\Arrays;

class Base {

    const LIST_ADMIN_USERS = [450=>'richard@imutual', 57=>'sue@imutual', 827=>'michelle@imutual'];
    const FOREVER_DATE = '2030-01-01';

    protected $db;
    protected $key;
    protected $data = []; // current state of object, with any new/changed data
    protected $objects = []; // will contain instances of related objects (i.e. for foreign key fields)
    protected $existingData = []; // represents current database record
    protected $changedData = [];
    protected $validationComplete = false;
    protected $errors = [];
    protected $warningsCount = 0;
    protected $ignoreWarnings = false;
    protected $isNewRecord = false; // Whether we're creating a new DB entry
    protected $now;
    protected $exceptionOnError;
    protected $selectUrl;
    public    $traceLog; // used for debugging

    function __construct(\sql_db $db, int $primary_key = null) {
        $this->db = $db;
        $this->now = gmdate('Y-m-d H:i:s');
        if ( $primary_key ) $this->read($primary_key); // Load existing db record
    }

    // Return specifc item or whole record
    public function get($param=null) {
        return ( $param ) ? $this->data[$param] ?? null : $this->data;
    }

    public function set(array $data) {
        $this->resetValidation(); // may by new/changed data, so reset validation status
        $newData = $this->clean($data);
        foreach($newData as $field=>$newValue) {
            $existingValue = $this->existingData[$field] ?? null; // NB if creating, we ignore nulls
            if ( $newValue !== $existingValue ) {
                $this->data[$field]=$this->changedData[$field]=$newValue; // update the value
                unset($this->objects[$field]); // remove related object if set
            } else {
                unset($this->changedData[$field]);
            }
        }
        if ( !$this->isNewRecord() && $key = $this->extractPrimaryKeyFromData() ) {
            if ( $key != $this->getKey() ) {
                $this->setKey($key)->load(); // load record from DB
            }
        }
        return $this;
    }

    public function setIgnoreWarnings(bool $ignoreWarnings = true) {
        $this->ignoreWarnings = $ignoreWarnings;
        return $this;
    }

    public function getIgnoreWarnings() {
        return $this->ignoreWarnings;
    }

    public function getListValues($list) {
        if ( is_numeric($list) ) {
            if ( !empty(static::DB_LISTS[$list]) ) return static::DB_LISTS[$list];
        }
        throw new \Exception('List not defined');
    }

    /**
     * Expects lookup definition in form of "method:label_field"
     * Runs method expecting it to return array of records from find()
     * Convert to 1-dim array with this field as key and the label field as value
     */
    public function getLookupValues($field) {
        $values=[];
        $def = $this->getModel()[$field];
        $obj = $this->getObject($field);
        if ( $def['lookup'] === true ) {
            return $obj->getAllTitles();
        }
        $tmp=explode(':',$def['lookup']);
        $lookupMethod=$tmp[0];
        $labelField = $tmp[1];
        foreach($obj->$lookupMethod() as $item) {
            $values[$item[$field]]=$item[$labelField];
        }
        return $values;
    }

    /**
     * Return associative array of all records in form:
     * ['key'=>'title']
     */
    public function getAllTitles() {
        $data=[];
        $sql = 'SELECT '.$this->idField().' as id, '.$this->titleField().' as title FROM '.static::DB_TABLE.' ORDER BY '.$this->titleField();
        $result=$this->db->sql_query($sql);
        foreach($this->db->sql_fetchrowset($result) as $row) {
            $data[$row['id']]=$row['title'];
        }
        return $data;
    }

    public function validate(bool $fullValidation=true) {
        $this->errors = [];
        foreach(static::DB_MODEL as $field => $def) {
            $value = $this->get($field) ?? null;
            if ( $value === null || $value === '' ) {
                if ( $fullValidation === true ) {
                    if ( !empty($def['required']) ) {
                        $this->setError($field,'Required field');
                        continue;
                    } elseif ( isset($def['default']) ) {
                        $value = $this->defaultValue($field);
                        $this->data[$field]=$value;
                    }
                }
            } else {
                // Validate the supplied value

                // Validate using lookup list
                if ( isset($def['list']) && !isset(static::DB_LISTS[$def['list']][$value]) ) {
                    $this->setError($field,'Invalid value');
                    continue;
                }

                // Validate by data type
                switch($def['type']) {
                    case 'key':
                        if ( empty($value) && empty($this->getKey()[$field]) ) $this->setError($field,'Required key field');
                    case 'num':
                    case 'uts':
                        if ( !is_numeric($value) ) $this->setError($field,'Must be numeric');
                        break;

                    case 'dat':
                        if ( strtotime($value) === false ) $this->setError($field,'Invalid date');
                        break;

                    default;
                        break;
                }

                // Validate by regular expression
                if ( isset($def['regex']) && !preg_match($def['regex'], $value) )  $this->setError($field,'Invalid format');

                if ( !empty($def['unique']) && isset($this->getChangedData()[$field]) ) {
                    // Check value does not already exist in database
                    $found = $this->find([$field=>$value]);
                    if ( count($found) > 0 ) {
                        $this->setError($field,'Value already exists. Must be unique');
                    }
                }
            }
        }

        if ( method_exists($this, 'customValidate') ) {
            $this->customValidate();
        }

        if ( $this->isValid() ) {
            if ( $fullValidation ) $this->validationComplete = true;
            return true;
        } else {
            if ( $this->exceptionOnError ) throw new ValidationException(json_encode($this->getErrors()), 400);
            else return false;
        }
    }

    public function create(array $data=null, bool $exceptionOnError=false) {
        $this->isNewRecord = true;
        $this->exceptionOnError = (bool)$exceptionOnError;
        $this->unload();
        if ($data) {
            $this->resetData()->set($data);
        }
        if ( !$this->validationComplete ) {
            if ( !$this->validate() ) return false;
        }
        $sql = $this->constructInsertQuery($this->get());
        return $this->runQuery($sql);
    }

    public function update(array $data=null, bool $exceptionOnError=false) {
        $this->isNewRecord = false;
        $this->exceptionOnError = (bool)$exceptionOnError;
        $this->mustBeLoaded();
        if ($data) {
            $this->resetData()->set($data);
        }
        if ( empty($this->getChangedData()) ) {
            return true; // nothing to update
        }
        if ( !$this->validationComplete ) {
            if ( !$this->validate() ) {
                $tmp = debug_backtrace()[0];
                trigger_error("update() on line ".$tmp['line'].' of '.$tmp['file'].': '.json_encode($this->get()));
                trigger_error('Validation errors: '.json_encode($this->getErrors()));
                return false; // @todo return errors as validationException?
            }
        }
        $sql = $this->constructUpdateQuery($this->getChangedData());
        return $this->runQuery($sql);
    }

    public function delete(array $data=null, bool $exceptionOnError=false) {
        $this->isNewRecord = false;
        $this->exceptionOnError = (bool)$exceptionOnError;
        if ( !empty($data) ) $this->findOne($data);
        $this->mustBeLoaded();
        $sql = $this->constructDeleteQuery();
        return $this->runQuery($sql);
    }

    /**
     * Load existing record from database
     * For tables with single primary key only
     * Will remove / reset any existing data in the object
     * @param int $id
     * @return self
     */
    public function read(int $id) {
        if ( !$this->hasSingleKeyField() ) throw new \Exception('Cannot use read method with compound key');
        $this->unload()->resetData();
        $this->setKey([static::PRIMARY_KEYS[0]=>$id])->load();
        return $this;
    }

    public function isLoaded() {
        return ( !empty($this->existingData) );
    }

    protected function mustBeLoaded() {
        if ( !$this->isLoaded() ) {
            $tmp = debug_backtrace()[0];
            $msg = 'Function called with no record loaded on line '.$tmp['line'].' of '.$tmp['file'];
            throw new \Exception($msg);
        }
    }

    public function getKey() {
        return $this->key;
    }

    public function getChangedData() {
        return $this->changedData;
    }

    public function isNewRecord() {
        return (bool)$this->isNewRecord;
    }

    public function getModel() {
        return static::DB_MODEL;
    }

    public function postActionRedirect(string $mode) {
        return null;
    }

    /**
     * Provides data for listing records
     * @return array $data
     */
    public function listData(array $options=[]) {

        $data=[];
        $lookups=[];
        if ( count(static::PRIMARY_KEYS) !== 1 ) throw new \Exception('Cannot list data for class with compound key');
        $primary_key = static::PRIMARY_KEYS[0];
        $sql = 'SELECT '.$primary_key.' AS id';
        foreach($this->listModel() as $field=>$def) {
            $sql .= ', `'.$field.'`';
            if ( isset($def['list']) ) {
                $lookups[$field] = static::DB_LISTS[$def['list']];
            } elseif ( isset($def['lookup']) ) {
                $lookups[$field] = $this->getLookupValues($field);
            }
        }
        $sql .= ' FROM '.static::DB_TABLE;
        if ( !empty($options['where']) ) {
            $sql .= ' WHERE '.$this->getWhereClause($options['where']);
        }
        $sql .= ' ORDER BY id DESC LIMIT 10000';
        $result=$this->db->sql_query($sql);
        foreach($this->db->sql_fetchrowset($result) as $row) {
            // Replace keys with labels using lookups
            foreach($row as $field=>$value) {
                if ( isset($lookups[$field]) ) {
                    $row[$field] = $lookups[$field][$value] ?? null;
                }
            }
            $data[]=$row;
        }
        return $data;
    }

    public function getClassName() {
        $path = explode('\\', get_class($this));
        return array_pop($path);
    }

    public function getCreateUrl() {
        return 'record.php?class='.$this->getClassName().'&mode=create';
    }

    protected function getSelectUrl() {
        return $this->selectUrl ?? 'record.php?class='.$this->getClassName().'&key=';
    }

    // Set custom destination when selecting record from datatables list
    public function setSelectUrl($url) {
        $this->selectUrl = $url;
    }

    /**
     * Returns array of DataTables column definitions used when listing records
     * See https://datatables.net/reference/option/data
     * @return array $columns
     */
    public function listColumns() {

        $columns=[[
            'data'=>'id',
            'orderable'=>false,
            'title'=>'ID',
            'render' => 'function ( data, type, row, meta ) { return \'<a href="'.$this->getSelectUrl().'\'+data+\'">Select</a>\'; }',
            ]];

        // Create 'nice' english field heading
        foreach($this->listModel() as $field=>$def) {
            $type = $render = $orderable = null;
            $label = $def['class'] ?? ucwords(str_replace('_',' ',$field));
            switch($def['type']) {
                case 'num':
                $type = 'num';
                if ( isset($def['scale']) && $def['scale'] == 2 ) {
                    // Assume it's money
                    $render = 'number( \',\', \'.\', 2, \'£\' )';
                }
                if ( isset($def['list']) || isset($def['lookup']) ) $orderable = false;
                break;

                case 'dat':
                case 'uts': // needs testing
                $type = 'date';
                $render = 'function (data, type) { return type == \'display\' ? moment(data).format(\'DD/MM/YYYY\') : data }';
                break;

                default:
                $render = 'text()';
            }
            $column = array_filter(['data'=>$field,'title'=>$label,'type'=>$type,'render'=>$render]);
            if ( isset($orderable) ) $column['orderable']=$orderable;
            $columns[]=$column;
        }
        return $columns;
    }

    public function traceLog(string $text, $important=false) {
        $tmp = debug_backtrace()[0];
        $tmp2 = explode('/',$tmp['file']);
        $text = end($tmp2).' '.$tmp['line'].': '.$text;
        if ( $important ) $text = '<b>'.$text.'</b>';
        $this->traceLog .= "\n\n".$text;
    }

    public function writeTraceLog() {
        file_put_contents('/tmp/traceLog'.$this->getClassName(),$this->getTraceLog());
    }

    public function getTraceLog() {
        return $this->traceLog;
    }

    /**
     * Convert to Json and add jquery notation to render property
     * Because json_encode encloses the jquery function in "" and breaks it!
     */
    public function listColumnJson() {
        $text = json_encode($this->listColumns());
        // "render": "function THEN STRING WITHOUT }"} FOLLOWED BY }"}
        $text = preg_replace('/("render":)\s?"function (((?!}"}).)+)}"}/', '$1 function $2}}',$text);
        $text = preg_replace('/("render":)\s?"([^"]+)"/', '$1$.fn.dataTable.render.$2',$text);
        // render: $.fn.dataTable.render.number( ',', '.', 2, '$' )
        return $text;
    }

    /**
     * Determines which fields are used when listing records
     * Add custom method with same name to child class when necessary
     * @return array $fieldNames
     */
    protected function listFields() {
        // Default to the first 5 non-key fields in the data model
        return array_slice(array_keys($this->getModel()),1,5,true);
    }

    /**
     * Return subset of data model for fields shown in records list
     * @return array $listModel
     */
    protected function listModel() {
        return $this->getPartialModel($this->listFields(),false);
    }

    /**
     * Return subset of data model for fields in suppled array
     * @var     array $fieldList
     * @return  array $partialModel
     */
    protected function getPartialModel(array $fields, bool $includeKey=true) {
        if ( $includeKey ) $fields = array_merge(static::PRIMARY_KEYS,$fields);
        $model=static::DB_MODEL; // using getModel() would result in infinite loop
        $partialModel = [];
        array_walk($fields, function ($field, $key) use ($model, &$partialModel) {
            $partialModel[$field] = $model[$field];
        });
        return $partialModel;
    }

    /**
     * Update value of primary key(s)
     * If key is changed, unload any existing record and reload from DB using new key
     * @param array $key
     * @return self
     */
    protected function setKey(array $key) {
        if ( $this->key != $key ) {
            if ( count($key) != count(static::PRIMARY_KEYS) ) throw new \Exception('Wrong number of key fields');
            foreach(static::PRIMARY_KEYS as $field) {
                if ( empty($key[$field]) )  throw new \Exception('Key: '.$field.' not found in '.json_encode($key));
                $this->data[$field] = $key[$field]; // load() also does this
            }
            if ( $this->isLoaded() ) $this->unload();
            $this->key = $key;
        }
        return $this;
    }

    public function id() {
        $idField = $this->idField();
        return $this->getKey()[$idField];
    }

    protected function idField() {
        if ( !$this->hasSingleKeyField() ) throw new \Exception('Cannot call idField() with compound key');
        return static::PRIMARY_KEYS[0];
    }

    public function getObject(string $field) {
        if ( !isset($this->objects[$field]) ) {
            $class = static::DB_MODEL[$field]['class'] ?? null;
            if ( !$class ) throw new \Exception('getObject called on field '.$field.' with no class defined');
            if ( strpos($class,'\\') === false ) $class = __NAMESPACE__ . '\\' . $class;
            // (Re)load instance of related object using value as primary key
            $value = $this->data[$field] ?? $this->existingData[$field] ?? null;
            $this->objects[$field] = new $class($this->db, $value);
        }
        return $this->objects[$field];
    }

    public function isValid() {
        return !(bool)$this->getErrors();
    }

    public function getErrors() {
        if ( !is_array($this->errors) ) $this->validate();
        return array_filter($this->errors);
    }

    public function getWarningsCount() {
        return $this->warningsCount;
    }

    /**
     * Search table using one or more key/value pairs
     * Return array of any matching database records
     */
    public function find(array $data, array $options=[]) {
        $results=[];
        $cleanData = $this->clean($data);
        $sql = 'SELECT * FROM '.static::DB_TABLE.' WHERE '.$this->getWhereClause($cleanData);
        if ( !empty($options['order']) && $orderBy = $this->getOrderByClause($options['order']) ) {
            $sql .= ' ORDER BY '.$orderBy;
        }
        if ( !empty($options['limit']) && preg_match('#^\d+(?>,\d+)$#',$options['limit']) ) {
            $sql .= ' LIMIT '.$options['limit'];
        }
        if ( $result=$this->db->sql_query($sql) ) {
            while ( $row=$this->db->sql_fetchrow($result) ) {
                $results[] = $this->dbToModel($row);
            }
        }
        return $results;
    }

    /**
     * Add / update multiple records using array of data
     * Return summary totals of new/updated records
     */
    public function massUpdate(array $data, $matchFields=[]) {
        if ( empty($matchFields) ) $matchFields = [static::PRIMARY_KEYS[0]];

        $results=['records'=>count($data), 'new'=>0];
        foreach($data as $item) {
            $item = $this->modelToDb($item);
            $match=[];
            foreach($matchFields as $field) {
                if ( !array_key_exists($field,$item) ) {
                    throw new Exception('Match field missing: '.$field);
                }
                $match[$field]=$item[$field];
            }

            if ( $this->findOne($match,true) ) {
                // Matches existing db record, update it
                $this->update($item);
                //$changes = $this->getChangedData();
                //if ( count($changes) > 0 ) $results['changed']++;
            } else {
                $this->create($item);
                $results['new']++;
            }
        }
        return $results;
    }

    /**
     * Search table using one or more key/value pairs
     * Load first record found into this instance
     * Return boolean to indicate if found
     */
    public function findOne(array $data, bool $strict = false) {
        $this->unload()->resetData();
        if ( $results = $this->find($data) ) {
            if ( $strict && count($results) > 1 ) {
                throw new \Exception('Multiple results for strict findOne search: '.json_encode($data));
            }
            $this->loadDbData($results[0]);
            return true;
        }
        return false;
    }

    /**
     * Search table using one or more key/value pairs
     * If record not found, create one using search array
     * Return primary key
     */
    public function findOrCreate(array $data) {
        if ( !$this->findOne($data) ) {
            return $this->create($data);
        }
        return $this->id();
    }

    public function name() {
        $title_field = $this->titleField();
        return $this->get($title_field);
    }

    protected function titleField() {
        return ( defined('static::TITLE_FIELD') ) ? static::TITLE_FIELD : static::PRIMARY_KEYS[0];
    }

    /**
     * Return any default value defined for specified field
     * @return string $value
     */
    public function defaultValue($field) {
        $default = $this->getModel()[$field]['default'] ?? null;
        if ( $default === '{NOW}' ) $default = $this->now;
        return $default;
    }

    /**
     * Links to display at top of "Edit record" screen
     * To customise, add function of same name to child class
     * @return array $links
     */
    protected function headerLinks() {
        return [['label'=>'List records', 'url'=>'records.php?class='.$this->getClassName()]];
    }

    /**
     * Html to display at top of "Edit record" screen
     * @return string $html
     */
    public function headerHtml($lsu=null) {
        $tags=[];
        $links = Arrays::populatePlaceholders($this->headerLinks(),$this->get());
        foreach($links as $link) {
            $url = $link['url'];
            if ($lsu) {
                $url = Strings::modifyQueryString(['lsu'=>$lsu],$url);
            }
            $tags[]='<a href="'.$url.'">'.$link['label'].'</a>';
        }
        $html = implode(' | ',$tags);
        return $html;
    }

    /**
     * Populate key and existingData using record already pulled from database
     */
    public function loadDbData($row) {
        if ( !$key = $this->extractPrimaryKeyFromData($row) ) {
            throw new \Exception('Key missing from data');
        }
        $this->setKey($key);
        $this->existingData = $this->dbToModel($row);
        $this->resetData();
        $this->isNewRecord = false;
        $this->changedData = [];
    }

    /**
     * Use the set key to find record in database
     * then populate $existingData
     */
    protected function load() {

        if ( !$this->isLoaded() ) {
            if ( !$this->isKeySet() ) throw new \Exception('No key set');
            $sql = 'SELECT * FROM '.static::DB_TABLE.' WHERE '.$this->getKeyWhereClause();
            $result=$this->db->sql_query($sql);
            if ( !$row=$this->db->sql_fetchrow($result) ) {
                if ( $this->hasSingleKeyField() ) {
                    // With single primary key supplied, we expect to find record in database
                    throw new \Exception($this->getClassName().' record not found ',404);
                }
                return false;
            }
            $this->existingData = $this->dbToModel($row);
            $this->resetData();
            $this->isNewRecord = false;
            $this->changedData = [];
        }
    }

    /**
     * Remove all existing data from object
     */
    protected function unload() {
        $this->existingData = [];
        $this->objects = [];
        $this->key = null;
        return $this;
    }

    protected function constructInsertQuery($data) {
        $dBdata = $this->modelToDb($data);
        $sql = '';
        foreach($dBdata as $var=>$val) {
            $sql .= ( !empty($sql) ) ? ', ' : ' SET ';
            $sql .= '`'.$var.'` = ';
            $sql .= ( $val === null ) ? 'NULL' : '"'.$this->db->sql_escape_string($val).'"';
        }
        $sql = 'INSERT INTO '.static::DB_TABLE.$sql;
        return $sql;
    }

    protected function constructUpdateQuery($data) {
        $dBdata = $this->modelToDb($data);
        $sql = '';
        foreach($dBdata as $var=>$val) {
            $sql .= ( !empty($sql) ) ? ', ' : ' SET ';
            $sql .= '`'.$var.'` = ';
            $sql .= ( $val === null ) ? 'NULL' : '"'.$this->db->sql_escape_string($val).'"';
        }
        $sql = 'UPDATE '.static::DB_TABLE.$sql.' WHERE '.$this->getKeyWhereClause();
        return $sql;
    }

    protected function constructDeleteQuery() {
        return 'DELETE FROM '.static::DB_TABLE.' WHERE '.$this->getKeyWhereClause().' LIMIT 1';
    }

    protected function getKeyWhereClause() {
        if ( !$key = $this->getKey() ) throw new \Exception('Key not set');
        return $this->getWhereClause($key);
    }

    protected function getWhereClause(array $data) {
        $tmp=[];
        foreach($data as $field=>$value) {
            if ( !isset(static::DB_MODEL[$field]) ) throw new \Exception('Invalid field: '.$field);
            if ( is_array($value) ) {
                $tmp[] = $this->mongoQueryToSql($field, $value);
            } elseif ( $value === null ) {
                $tmp[] = "`$field` IS NULL";
            } else {
                $tmp[] = "`$field` = \"".$this->db->sql_escape_string($value).'"';
            }
        }
        if ( empty($tmp) ) throw new \Exception('No valid search criteria');
        return implode(' AND ',$tmp);
    }

    protected function getOrderByClause(array $data) {
        $tmp=[];
        foreach($data as $field=>$value) {
            if ( !isset(static::DB_MODEL[$field]) ) throw new \Exception('Invalid field: '.$field);
            $text = $field;
            if ( in_array($value,['desc','DESC','-1',-1]) ) {
                $text .= ' DESC';
            }
            $tmp[]=$text;
        }
        return implode(', ',$tmp);
    }

    protected function mongoQueryToSql($field, array $query) {
        $operators = ['ne'=>'!=','gt'=>'>','gte'=>'>=','lt'=>'<','lte'=>'<='];
        $sql = null;
        foreach($query as $key=>$value) {
            if ( !$symbol = $operators[$key] ) throw new Exception('Unknown operator: '.$key);
            if ( $sql ) $sql .= ' AND ';
            $sql .= "`$field` $symbol \"".$this->db->sql_escape_string($value).'"';
        }
        return $sql;
    }

    protected function extractPrimaryKeyFromData($data = null) {
        if ( empty($data) ) $data = $this->get();
        $key=[];
        foreach(static::PRIMARY_KEYS as $field) {
            $value = $data[$field] ?? null;
            if ( empty($value) ) return false;
            $key[$field] = $value;
        }
        return $key;
    }

    /**
     * Strip array of any elements not part of the model and sanitise data
     */
    protected function clean($data) {
        $clean_data=[];

        foreach(static::DB_MODEL as $field => $def) {
            if ( array_key_exists($field, $data) ) {
                if ( $data[$field] === null ) {
                    $value = null; // leave nulls unaltered
                } else {
                    switch($def['type']) {
                        case 'num':
                        case 'uts':
                        if ( strlen($data[$field]) == 0 ) {
                            $value = null;
                        } else {
                            $value = floatval($data[$field]);
                            if ( isset($def['scale']) ) $value = round($value,$def['scale']);
                        }
                        break;

                        default:
                        $value = $data[$field];
                        break;
                    }
                    if ( !empty($def['transform']) ) {
                        if ( method_exists($this,$def['transform']) ) {
                            $value = $this->{$def['transform']}($value);
                        } else {
                            trigger_error('Transform method not found: '.$def['transform']);
                        }
                    }
                }
                $clean_data[$field] = $value;
            }
        }
        return $clean_data;
    }

    /**
     * Restore status of data to match current DB record
     * Lose any changes not yet committed to database
     */
    protected function resetData() {
        $this->data = ( $this->isLoaded() ) ? $this->existingData : [];
        return $this;
    }

    /**
     * Apply default values to data where no value has been provided
     */
    protected function addFallbackValues(array $defaults) {
        $new_values=[];
        foreach($defaults as $var=>$value) {
            if ( $this->get($var) === null && $value !== null ) {
                $new_values[$var]=$value;
            }
        }
        if ( !empty($new_values) ) {
            $this->set($new_values);
        }
        return count($new_values);
    }

    protected function dbToModel($databaseRecord) {
        $data=[];
        foreach(static::DB_MODEL as $field => $def) {
            if ( array_key_exists($field, $databaseRecord) ) {
                if ( is_null($databaseRecord[$field]) ) {
                    $data[$field] = null;
                } else {
                    switch($def['type']) {
                        case 'num':
                        case 'uts':
                            $data[$field] = floatval($databaseRecord[$field])+0;
                            break;
                        case 'jsn':
                            $data[$field] = json_decode($databaseRecord[$field],true);
                            break;
                        default:
                            $data[$field] = $databaseRecord[$field];
                    }
                }
            }
        }
        return $data;
    }

    protected function modelToDb(array $data) {
        $dbData=[];
        foreach(static::DB_MODEL as $field => $def) {
            if ( array_key_exists($field, $data) && ($def['type'] != 'key' || $this->isNewRecord() ) ) {
                switch($def['type']) {
                    case 'dat':
                    $dbData[$field] = date('Y-m-d H:i:s',strtotime($data[$field]));
                    break;

                    case 'txt':
                    $dbData[$field] = (string)$data[$field];
                    break;

                    case 'jsn':
                    $dbData[$field] = json_encode($data[$field]);
                    break;

                    default:
                    $dbData[$field] = $data[$field];
                }
            }
        }
        if ( empty($dbData) ) {
            trigger_error('No valid data in '.print_r($data,true));
        }
        return $dbData;
    }

    protected function runQuery($sql) {
        if ( !$result = $this->db->sql_query($sql) ) {
            throw new \Exception('SQL error: '.$this->db->sql_error()['message']);
        }

        // Set new key if we've just created a record
        if ( $this->isNewRecord() ) {
            if ( !$key = $this->extractPrimaryKeyFromData() ) {
                // No value set for primary key, assume it's auto_increment
                $key = [static::PRIMARY_KEYS[0] => $this->db->sql_nextid()];
            }
            $this->setKey($key);
        }
        // Now reset as if record had just been loaded
        $this->existingData = $this->data;
        $this->isNewRecord = false;
        $changedData = $this->changedData; // Take copy of changed data before resetting
        $this->changedData = [];
        $this->runPostChangeRoutines($changedData);

        return $this->getKey()[static::PRIMARY_KEYS[0]]; // return primary key to indicate success
    }

    protected function hasSingleKeyField() {
        return count(static::PRIMARY_KEYS) == 1;
    }

    protected function isKeySet() {
        return $this->getKey()[static::PRIMARY_KEYS[0]] ?? null;
    }

    /**
     * Run any update routines triggered by these changes
     */
    protected function runPostChangeRoutines(array $changedData) {
        $extraMethods=[];
        foreach($changedData as $field=>$newValue) {
            if ( isset(static::DB_MODEL[$field]['onChange']) ) {
                $extraMethods[static::DB_MODEL[$field]['onChange']] = true;
            }
        }
        // Run any routines triggered by changes to certain fields
        foreach($extraMethods as $method => $bool) {
            $this->$method();
        }
    }

    protected function setError(string $item, string $message, $class='danger') {
        if ( !in_array($class, ['danger','warning']) ) throw new \Exception('Invalid error class');
        if ( $class == 'warning' ) $this->warningsCount++;
        if ( $class == 'danger' || !$this->getIgnoreWarnings() ) {
            $this->errors[$item] = ['msg'=>$message, 'class'=>$class];
        }
    }

    protected function resetValidation() {
        $this->validationComplete = false;
        $this->errors = null;
        $this->warningsCount = 0;
    }

    protected function validateUrl($url) {
        if( !filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED) ) {
            throw new \Exception('Invalid URL: '.$url);
        }
        return $url;
    }

    protected function overlayArrays($array1, $array2) {
        $merged = $array1;
        foreach ( $array2 as $key => &$value ) {
            if ( is_array($value) && isset($merged[$key]) && is_array($merged[$key]) ) {
                $merged[$key] = $this->overlayArrays($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }
        return $merged;
    }
}
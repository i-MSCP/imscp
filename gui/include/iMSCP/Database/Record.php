<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "i-MSCP - Multi Server Control panel".
 *
 * The Initial Developer of the Original Code is i-MSCP Team.
 * Portions created by the i-MSCP Team are Copyright (C) 2006-2010 by
 * internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package     iMSCP_Database
 * @copyright 	2010 by i-MSCP | http://i-mscp.net
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     SVN: $Id: Service.php 4006 2010-12-05 22:28:14Z nuxwin $
 * @link		http://i-mscp.net i-MSCP Home Site
 * @license     http://www.mozilla.org/MPL/ MPL 1.1
 */


/**
 * iMSCP Database Record class
 *
 * iMSCP_Database_Record objects don't specify their attributes directly, but rather infer them from the table
 * definition with which they're linked. Adding, removing, and changing attributes and their type is done directly in
 * the database. Any change is instantly reflected in the Record objects. The mapping that binds a given
 * iMSCP_Database_Record class to a certain database table will happen automatically in most common case, but can be
 * overwritten for the uncommon ones.
 *
 * @property bool readOnly
 *
 * @category	i-MSCP
 * @package     iMSCP_Database
 * @copyright 	2010 by i-MSCP | http://i-mscp.net
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @since		1.0.0 (i-MSCP)
 * @version		1.0.0
 */
abstract class iMSCP_Database_Record {

	/**
	 * Database instance
	 *
	 * @var PDO
	 */
	private static $_db = null;

	/**
	 * Child class name that represent the model
	 *
	 * @var string
	 */
	protected $modelName = null;

	/**
	 * Table name associated with the model class
	 *
	 * @var string
	 */
	protected $tableName = null;

	/**
	 * Tell whether or not the object represent a new record
	 *
	 * @var bool
	 */
	protected $newRecord = true;

	/**
	 * Tell whether or not the record can be saved or updated
	 *
	 * @var bool
	 */
	protected $readonly = false;

	/**
	 * Primary key of the associated table
	 *
	 * @var string
	 */
	protected $primaryKey = 'id';

	/**
	 * Columns info
	 */
	private $_columnsInfo =  array();


	private $VALID_FIND_OPTIONS = array(
		'conditions', 'include', 'joins', 'limit', 'offset', 'order',
		'select', 'readonly', 'group', 'having', 'from', 'lock'
	);

	/**
	 * Create a new object
	 *
	 * A new object can be instantiated as either empty (no construction parameter) or pre-set with attributes but no
	 * yet saved (pass associative array where each key name matching associated table column names). In both case,
	 * valid attributes keys are determined by the column names of the associated table - hence you can't have
	 * attributes that aren't part of the table columns.
	 *
	 * @param array $attributes Associative array where each key names matching associated table column names
	 * @return void
	 */
	public function __construct(array $attributes = array()) {

		echo 'called __construct() <br />';

		// Set the model name
		if(is_null($this->modelName)) {
			$this->modelName = get_class($this);
		}

		$this->_establishConnection();
		$this->_getColumnsInfo();

	}

	/**
	 * Find one or more records
	 *
	 * Find operates with four different retrieval approaches:
	 *
	 * Find by id:
	 *  This can either be a specific id (1), a list of ids (1, 5, 6), or an array of ids (array(5, 6, 10)).
	 *  If no record can be found for all of the listed ids, then an {@link iMSCP_Exception_Database} will be raised.
	 *
	 * Find first:
	 *  This will return the first record matched by the options used. These options can either be specific conditions
	 *  or merely an order. If not record can be matched, NULL is returned.
	 *
	 * Find all:
	 *  Find all - This will return all the records matched by the options used. If no records are found, an empty array
	 *  is returned.
	 *
	 * Find last:
	 *  This will return the last record matched by the options used. These options can either be specific conditions or
	 *  merely an order. If no record can be matched, null is returned.
	 *
	 * All approaches accept an options associative array as their last parameter:
	 *
	 * conditions:
	 * order:
	 * group:
	 * having:
	 * limit:
	 * offset:
	 * joins:
	 * include:
	 * select:
	 * from:
	 * readonly:
	 * lock:
	 * lock:
	 *
	 * @param mixed $ids,... Can be either (first, last all), and id, a list of ids, or an array of ids
	 * @param array $options An associative array of parameter (condition, order, group, having, joins, readonly, include,
	 * select, from, lock)
	 * @return void
	 * @todo (first, last, all) and optional parameter
	 */
	public function find($ids) {

		echo 'called find() <br />';

		$arguments = func_get_args();
		$options = is_array($arguments[count($arguments)-1]) ? array_pop($arguments) : array();

		// flatten the $arguments array if needed
		if(is_array($arguments[0])) {
			foreach($arguments as $k => $v) $arguments[$k] = (array) $v;
            $arguments = call_user_func_array('array_merge', $arguments);
		}

		// Ensure for proper parameters
		$this->validateFindOptions($options);

		switch($arguments[0]) {
			case 'first':
				return $this->findInitial($options);
		    break;
		    case 'last':
		        return $this->findLast($options);
		    break;
			case 'all':
		        return $this->findEvery($options);
		    break;
		    default:
				return $this->findFromIds($arguments, $options);
		}
	}

	/**
	 * Validate find options
	 *
	 * @throws iMSCP_Exception
	 * @param  $options An array that contain all find options
	 * @return void
	 */
	private function validateFindOptions($options) {

		if(!empty($options)) {
			$options = array_diff(array_keys($options), $this->VALID_FIND_OPTIONS);

			if(!empty($options)) {
				throw new iMSCP_Exception("Wrong find() argument '{$options[0]}'!");
			}
		}
	}


	/**
	 * @static
	 * @param  $options
	 * @return void
	 */
	private function findInitial($options){

		echo 'called findInitial() <br />';
	}

	/**
	 * @static
	 * @param  $options
	 * @return void
	 */
	private function findLast($options){

		echo 'called findLast() <br />';
	}

	/**
	 * @static
	 * @param  $options
	 * @return void
	 * @todo associations
	 */
	private function findEvery($options) {

		echo 'called findEvery() <br />';

		$records = $this->findBySql($this->constructFinderSql($options));

		foreach($records as $record) {
			if(array_key_exists('readonly', $options) && $options['readonly']) {
				$record->setReadOnly();
			}
		}

		return $records;
	}

	/**
	 * @param  $options
	 * @return void
	 */
	private function constructFinderSql($options) {

		echo 'called constructFinderSql() <br />';

		// Todo improve this
		$sql = "SELECT * ";

		// Todo improve this
		$sql .= "FROM `{$this->tableName}` WHERE {$options['condition']}";

		return $sql;
	}
/*
        def construct_finder_sql(options)
          scope = scope(:find)
          sql  = "SELECT #{options[:select] || (scope && scope[:select]) || default_select(options[:joins] || (scope && scope[:joins]))} "
          sql << "FROM #{options[:from]  || (scope && scope[:from]) || quoted_table_name} "

          add_joins!(sql, options[:joins], scope)
          add_conditions!(sql, options[:conditions], scope)

          add_group!(sql, options[:group], options[:having], scope)
          add_order!(sql, options[:order], scope)
          add_limit!(sql, options, scope)
          add_lock!(sql, options, scope)

          sql
        end
*/

	/**
	 * Execute a custom SQL query against the database and returns all the results.
	 *
	 * The results will be returned as an array with columns requested encapsulated as attributes of the model you call
	 * this method from. If you call findBySql() from an User class instance, then the result will be returned in a User
	 * object with the attributes you specified in the SQL query.
	 *
	 * If you call a complicated SQL query which spans multiple tables, the columns specified by the SELECT will be
	 * attributes of the model, whether or not they are columns of the corresponding tables.
	 *
	 * The $sql parameter is a full SQL query as a string. It will be called as is, there will be no database agnostic
	 * terms will lock you to using that particular database engine or require you to change your call if you switch
	 * engines.
	 *
	 * Examples:
	 *
	 *  # A simple SQL query spanning multiple tables
	 *  $post = new Post;
	 *  $post->findBySql("SELECT p.title, c.author FROM post p, comments c WHERE p.id = c.post_id");
	 *
	 *  will return something like this:
	 *
	 *  array(
	 *      [0] => Post object (
	 *          [title] => i-MSCP on the floor,
	 *          [author] => Linux
	 *          ...
	 *      )
	 * )
	 *
	 * @param  $sql
	 * @return void
	 * @todo prepared statement
	 */
	public function findBySQL($sql) {

		echo 'called findBySQL() <br />';

		$stmt = self::$_db->query($sql);

		return $stmt->fetchALL(PDO::FETCH_CLASS,  $this->modelName);
	}

	/**
	 * Find one or more record in the database
	 *
	 * @throws iMSCP_Exception_Database
	 * @param  $ids
	 * @param  $options
	 * @return void
	 */
	private function findFromIds($ids, $options) {

		echo 'called findFromIds() <br />';

		$ids = array_unique((array) $ids);

		switch(count($ids)) {
			case 0:
				throw new iMSCP_Exception_Database("Couldn't find record without an ID!");
		    break;
		    case 1:
		        return $this->findOne(array_pop($ids), $options);
			break;
		    default:
				return $this->findSome($ids, $options);
		}
	}

	/**
	 * Find one record in the database
	 *
	 * @throws iMSCP_Exception_Database
	 * @param  $id Record id
	 * @param  $options
	 * @return void
	 */
	private function findOne($id, $options) {

		echo 'called findOne <br />';

		$conditions = array_key_exists('conditions', $options) ? $conditions = " AND {$options['conditions']}" : '';
		$options['condition'] = "`{$this->tableName}`.`{$this->primaryKey}` = " . $this->quote($id) . "$conditions";

		# Use find_every($options) since the primary key condition already ensures we have a single record.
		# Using find_initial adds a superfluous :limit => 1.
		$result = $this->findEvery($options);

		if(!empty($result)) {
			return $result[0];
		} else {
			throw new iMSCP_Exception_Database("Couldn't find service with ID=$id$conditions");
		}
	}




	/**
	 * @return
	 */
	public function isReadOnly() {

		echo 'called isReadOnly() <br />';

		return (isset($this->readonly) && $this->readOnly);
	}

	/**
	 * @return void
	 */
	public function setReadOnly() {

		echo 'called setReadOnly() <br />';

		$this->readOnly = true;
	}






// QUOTING FUNCTION END
	private function quote($value) {

		echo 'called quote() <br />';

		return self::$_db->quote($value);
	}
// QUOTE FUNCTIONS END

// SQL SANITIZE FUNCTIONS - START

	private function sanitizeSqlForConditions($condition, $table_name = null) {

	}

	private function sanitizeSql($condition, $table_name = null){
		$this->sanitizeSqlForConditions($condition, $table_name);
	}


/*
        # Accepts an array, hash, or string of SQL conditions and sanitizes
        # them into a valid SQL fragment for a WHERE clause.
        #   ["name='%s' and group_id='%s'", "foo'bar", 4]  returns  "name='foo''bar' and group_id='4'"
        #   { :name => "foo'bar", :group_id => 4 }  returns "name='foo''bar' and group_id='4'"
        #   "name='foo''bar' and group_id='4'" returns "name='foo''bar' and group_id='4'"
        def sanitize_sql_for_conditions(condition, table_name = quoted_table_name)
          return nil if condition.blank?

          case condition
            when Array; sanitize_sql_array(condition)
            when Hash;  sanitize_sql_hash_for_conditions(condition, table_name)
            else        condition
          end
        end
        alias_method :sanitize_sql, :sanitize_sql_for_conditions
 */

// SQL SANITIZE FUNCTIONS - END


	/**
	 * @param  $ids
	 * @param  $options
	 * @return void
	 */
	private function findSome($ids, $options) {
		echo 'called findSome() <br />';
	}

	/**
	 * Find all records
	 *
	 * This is an alias for find('all'). You can pass in all the same arguments to this method as you can to find('all')
	 *
	 * @static
	 * @return void
	 */
	public static function findAll(){

		echo 'called findSome() <br />';
	}

	/* PRIVATE METHOD */

	/**
	 * Establish the connection to the database
	 * 
	 * @return void
	 */
	private function _establishConnection() {

		echo 'called _establishConnection() <br />';

		if(is_null(self::$_db)) {
			self::$_db = iMSCP_Registry::get('pdo');
		}
	}

	private function _getColumnsInfo() {

		echo 'called _getColumnsInfo() <br />';

		$stmt = self::$_db->query("SHOW COLUMNS FROM {$this->tableName};");
		$this->_columnsInfo = $stmt->fetchAll(PDO::FETCH_OBJ);
	}

} // end class

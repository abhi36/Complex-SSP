<?php
/*
 * Helper functions for building a DataTables server-side processing (SSP) SQL query
 *
 * Author: Abhijeet K
 *
 * @license: GNU v3.0
 */
class SSP {
	private static $filterFields = [];
	private static $sortFields = [];
	private static $fetchFields = [];
	private static $columnFields = [];
	private static $actionCtrls = "";
	private static $silentFields = ["silence", "actions"];
	/**
	 * Create the data output array for the DataTables rows
	 *
	 *  @param  array  $columns		Column information array
	 *  @param  array  $data    	Data from the SQL get
	 *  @param	string $primaryKey	Primary Key provided by calling script
	 *  @return array          Formatted data in a row based format
	 *
	 * It allowes two types of user defined functions "callback" "formatter"
	 * the callback function gets precedence
	 */
	static function data_output($columns, $data, $primaryKey) {
		$out = array();
		for ($i = 0, $ien = count($data); $i < $ien; $i++) {
			$row = array();
			for ($j = 0, $jen = count($columns) - 1; $j < $jen; $j++) {
				$column = $columns[$j];
				$fieldItemNameParts = explode(".", $primaryKey);
				$pk = trim(end($fieldItemNameParts));
				$rowData = $data[$i];
				$pkVal = $data[$i][$pk];

				// If there is a callback function declared and defined
				if (isset($column["callback"]) && function_exists($callBackFunc = $column["callback"])) {
					$rowData[self::$columnFields[$j]] = call_user_func_array($callBackFunc, [
						$rowData[self::$columnFields[$j]],
						$j,
						$rowData,
						$pk
					]);
				}
				// Skip special case of actions
				if ($column["name"] == "actions") {
					self::$actionCtrls = "";
					if (isset($column["options"]) && is_array($column["options"])) {
						self::prepareActionCtrls($column["options"], $data[$i][$pk]);
					}
					$row[$j] = self::$actionCtrls;
				} elseif (!in_array($column["name"], self::$silentFields)) {
					extract($data[$i]);

					// If there a formatter is provided
					if (isset($column['formatter'])) {
						// Construct formatted response with simple string replacement
						preg_match_all("/{{(\w+)}}/", $column["formatter"], $vars);
						if (count($vars) === 2) {
							$searches = $vars[0];
							$replacers = $vars[1];
							if (is_array($searches) && is_array($replacers)) {
								if (count($searches) === count($replacers) && count($searches)) {
									foreach ($replacers as $rk => $rv) {
										$replacers[$rk] = (isset($$rv))
											? (!is_array($$rv) ? $$rv : serialize($$rv))
											: (isset($_SESSION[$rv]) ? $_SESSION[$rv] : "");
									}
								}
							}
							$column["formatter"] = str_replace($searches, $replacers, $column["formatter"]);
						}
						// Construct formatted response with expression replacement
						preg_match_all("/{\[(.*?)\]}/", $column["formatter"], $vars);
						if (count($vars) === 2) {
							$searches = $vars[0];
							$replacers = $vars[1];
							if (is_array($searches) && is_array($replacers)) {
								if (count($searches) === count($replacers) && count($searches)) {
									foreach ($replacers as $rk => $rv) {
										preg_match_all("/{(\w+)}/", $rv, $dataVars);
										$dSearches = $dataVars[0];
										$dReplacers = $dataVars[1];
										if (is_array($dSearches) && is_array($dReplacers)) {
											if (count($dSearches) === count($dReplacers) && count($dSearches)) {
												foreach ($dReplacers as $drk => $drv) {
													$dReplacers[$drk] = (isset($rowData[$drv])) ? "$" . $drv : "";
												}
											}
										}
										$replacers[$rk] = str_replace($dSearches, $dReplacers, $replacers[$rk]);
										$replacers[$rk] = eval("return " . $replacers[$rk] . ";");
									}
								}
							}
							$column["formatter"] = str_replace($searches, $replacers, $column["formatter"]);
						}
						$row[$j] = $column['formatter'];
					} else {
						$row[$j] = $rowData[self::$columnFields[$j]];
					}
				}
			}
			$out[] = $row;
		}
		return $out;
	}
	/**
	 * Database connection
	 *
	 * Obtain an PHP PDO connection from a connection details array
	 *
	 *  @param  array $conn SQL connection details. The array should have
	 *    the following properties
	 *     * host - host name
	 *     * db   - database name
	 *     * user - user name
	 *     * pass - user password
	 *  @return resource PDO connection
	 */
	static function db($conn) {
		if (is_array($conn)) {
			return self::sql_connect($conn);
		}
		return $conn;
	}
	/**
	 * Paging
	 *
	 * Construct the LIMIT clause for server-side processing SQL query
	 *
	 *  @param  array $request Data sent to server by DataTables
	 *  @return string SQL limit clause
	 */
	static function limit($request) {
		$limit = '';
		if (isset($request['start']) && $request['length'] != -1) {
			$limit = "LIMIT " . intval($request['start']) . ", " . intval($request['length']);
		}
		return $limit;
	}
	/**
	 * Ordering
	 *
	 * Construct the ORDER BY clause for server-side processing SQL query
	 *
	 *  @param  array $request Data sent to server by DataTables
	 *  @param  array $columns Column information array
	 *  @return string SQL order by clause
	 */
	static function order($request, $columns) {
		$order = '';
		if (isset($request['order']) && count($request['order'])) {
			$orderBy = array();
			for ($i = 0, $ien = count($request['order']); $i < $ien; $i++) {
				// Convert the column index into the column data property
				$columnIdx = intval($request['order'][$i]['column']);
				$requestColumn = $request['columns'][$columnIdx];
				$orderFields = $columns[$requestColumn["data"]];
				self::$sortFields = isset($orderFields["sorter"]) && !empty($orderFields["sorter"])
					? $orderFields["sorter"] : (isset($orderFields["filter"]) && !empty($orderFields["filter"])
						? $orderFields["filter"] :
						$orderFields["name"]);
				self::$sortFields = (!is_array(self::$sortFields)) ? [self::$sortFields] : self::$sortFields;
				if ($requestColumn['orderable'] == 'true') {
					$dir = $request['order'][$i]['dir'] === 'asc' ?
						'ASC' :
						'DESC';
					if (is_array(self::$sortFields)) {
						foreach (self::$sortFields as $orderField) {
							$orderBy[] = $orderField . ' ' . $dir;
						}
					}
				}
			}
			$order = (!empty($orderBy)) ? 'ORDER BY ' . implode(', ', $orderBy) : '';
		}
		return $order;
	}
	/**
	 * Searching / Filtering
	 *
	 * Construct the WHERE clause for server-side processing SQL query.
	 *
	 * NOTE this does not match the built-in DataTables filtering which does it
	 * word by word on any field. It's possible to do here performance on large
	 * databases would be very poor
	 *
	 *  @param  array $request Data sent to server by DataTables
	 *  @param  array $columns Column information array
	 *  @return string SQL where clause
	 */
	static function filter($request, $columns, &$bindings) {
		$globalSearch = array();
		$columnSearch = array();

		if (isset($request['search']) && $request['search']['value'] != '') {
			$str = $request['search']['value'];
			for ($i = 0, $ien = count($request['columns']); $i < $ien; $i++) {
				$requestColumn = $request['columns'][$i];
				$filterFields = $columns[$requestColumn["data"]];
				self::$sortFields = isset($filterFields["filter"]) ? $filterFields["filter"] :
					$filterFields["name"];
				self::$sortFields = (!is_array(self::$sortFields)) ? [self::$sortFields] : self::$sortFields;
				if ($requestColumn['searchable'] == 'true') {
					foreach (self::$sortFields as $k => $filterField) {
						if ($k === count(self::$sortFields) - 1) {
							$binding = self::bind($bindings, "%" . $str . "%", PDO::PARAM_STR);
							$globalSearch[] = $filterField . " LIKE {$binding}";
						}
					}
				}
			}
		}
		// var_dump($globalSearch);
		// exit();
		return $globalSearch;

		// var_dump($globalSearch);
		// Individual column filtering
		/*if ( isset( $request['columns'] ) ) {
			for ( $i=0, $ien=count($request['columns']) ; $i<$ien ; $i++ ) {
				$requestColumn = $request['columns'][$i];
				$columnIdx = array_search( $requestColumn['data'], $dtColumns );
				$column = $columns[ $columnIdx ];
				$str = $requestColumn['search']['value'];
				if ( $requestColumn['searchable'] == 'true' &&
				 $str != '' ) {
					$binding = self::bind( $bindings, '%'.$str.'%', PDO::PARAM_STR );
					$columnSearch[] = "`".$column['db']."` LIKE ".$binding;
				}
			}
		}
		// Combine the filters into a single string
		$where = '';
		if ( count( $globalSearch ) ) {
			$where = '('.implode(' OR ', $globalSearch).')';
		}
		if ( count( $columnSearch ) ) {
			$where = $where === '' ?
				implode(' AND ', $columnSearch) :
				$where .' AND '. implode(' AND ', $columnSearch);
		}
		if ( $where !== '' ) {
			$where = 'WHERE '.$where;
		}*/
		// return $where;
	}

	/**
	 * Prepare action controls called from origin
	 *
	 * 	@param	array	$options Action items in array
	 * 	@param	int		Primary key of the current data row
	 *
	 * 	@return	null	Makes a call direct to the calling user function
	 * and append the returned formatted string to private variable $actionCtrls
	 */
	private static function prepareActionCtrls($options = [], $pk = 0) {
		if (!empty($options)) {
			foreach ($options as $k => $option) {
				$fnName = $option;
				if (function_exists($fnName)) {
					self::$actionCtrls .= call_user_func($fnName, $pk);
				}
			}
		}
	}
	private static function setFetchFields($fieldsParts) {
		if (is_array($fieldsParts)) {
			foreach ($fieldsParts as $fieldItems) {
				if (isset($fieldItems["name"])) {
					if (in_array(trim($fieldItems["name"]), self::$silentFields)) {
						if (isset($fieldItems["fields"]) && !empty($fieldItems["fields"])) {
							$fetchF = $fieldItems["fields"];
							self::$fetchFields[] = (is_array($fetchF)) ? implode(",", $fetchF) : $fetchF;
						}
					} else {
						self::$fetchFields[] = trim($fieldItems["name"]);
					}
				}
			}
		}
	}
	private static function setColumnFields($fieldsParts) {
		if (is_array($fieldsParts)) {
			foreach ($fieldsParts as $fieldItems) {
				if (isset($fieldItems["name"])) {
					if (strpos($fieldItems["name"], "AS")) {
						$fieldItemNameParts = explode("AS", $fieldItems["name"]);
						$fieldItemName = trim(end($fieldItemNameParts));
					} elseif (strpos($fieldItems["name"], ".")) {
						$fieldItemNameParts = explode(".", $fieldItems["name"]);
						$fieldItemName = trim(end($fieldItemNameParts));
					} else {
						$fieldItemName = trim($fieldItems["name"]);
					}
					self::$columnFields[] = trim($fieldItemName);
				}
			}
		}
	}
	static function complex($request, $conn, $table = null, $primaryKey = 'id') {
		if (!isset($request["fields"])) {
			self::fatal("No fields defined to fetch");
		}
		$bindings = [];
		$db = self::db($conn);
		$fieldsParts = unserialize(base64_decode($request['fields']));
		$params = isset($request["params"]) ? unserialize(base64_decode($request["params"])) : false;
		$joinedTbls = isset($params["joinedTbls"]) ? $params["joinedTbls"] : false;

		$where = isset($params["where"]) ? "WHERE {$params["where"]}" : false;
		$whereAlways = isset($params["whereAlways"]) ? "{$params["whereAlways"]}" : false;

		$groupBy = (isset($params["groupBy"]) && null != $params["groupBy"]) ? "GROUP BY {$params["groupBy"]}" : "";

		if (null === $table) {
			$table = isset($request["table"]) ? $request["table"] : false;
		}

		if (!$table) {
			self::fatal("No base tables defined");
		}
		if (!is_array($fieldsParts)) {
			self::fatal("fields param expects an array");
		} else {
			foreach ($fieldsParts as $fieldItem) {
				if (!isset($fieldItem["name"])) continue; //self::fatal("Each field items need a name index");
			}
		}
		self::setFetchFields($fieldsParts);
		self::$fetchFields[] = $primaryKey;

		if (!empty(self::$silentFields)) {
			foreach (self::$silentFields as $silentField) {
			}
		}
		self::setColumnFields($fieldsParts);
		// Build the SQL query string from the request
		$limit = self::limit($request);
		$order = self::order($request, $fieldsParts);
		$whereResult = self::filter($request, $fieldsParts, $bindings);
		$whereResult = self::_flatten($whereResult, " OR ");

		if ($whereResult) {
			$where = $where ?
				$where . ' AND (' . $whereResult . ')' :
				'WHERE ' . $whereResult;
		}


		/**
		 * The where clause which is always set
		 */
		$whereAll = self::_flatten($whereAlways);
		$whereAllSql = "";
		if ($whereAll) {
			$where = ($where != "") ?
				$where . ' AND (' . $whereAll . ')' :
				"WHERE {$whereAll}";

			$whereAllSql = "WHERE {$whereAll}";
		}

		if (null !== $joinedTbls) {
			$table .= " {$joinedTbls}";
		}
		$query = "SELECT " . implode(", ", self::$fetchFields) . "
		FROM {$table}
		$where
		$groupBy
		$order
		$limit";

		if (isset($params["debug"]) && $params["debug"]) {
			var_dump($query);
			exit();
		}
		// Main query to actually get the data
		$data = self::sql_exec($db, $bindings, $query);


		/**
		 * Get count of filtered records
		 */
		$query = "SELECT COUNT({$primaryKey})
		FROM {$table}
		$where
		$groupBy";

		$resFilterLength = self::sql_exec($db, $bindings, $query);
		$recordsFiltered = (count($resFilterLength) === 1) ? $resFilterLength[0][0] : count($resFilterLength);

		/**
		 * Get count of total dataset
		 */
		$query = "SELECT COUNT({$primaryKey})
		FROM {$table}
		{$whereAllSql}
		{$groupBy}";
		$resTotalLength = self::sql_exec($db, [], $query);
		$recordsTotal = (count($resTotalLength) === 1) ? $resTotalLength[0][0] : count($resTotalLength);

		/*
		 * Output
		 */
		return array(
			"draw"            => isset($request['draw']) ?
				intval($request['draw']) :
				0,
			"recordsTotal"    => intval($recordsTotal),
			"recordsFiltered" => intval($recordsFiltered),
			"data"            => self::data_output($fieldsParts, $data, $primaryKey)
		);
	}
	/**
	 * Connect to the database
	 *
	 * @param  array $sql_details SQL server connection details array, with the
	 *   properties:
	 *     * host - host name
	 *     * db   - database name
	 *     * user - user name
	 *     * pass - user password
	 * @return resource Database connection handle
	 */
	static function sql_connect($sql_details): object {
		try {
			$db = new \PDO(
				"mysql:host={$sql_details['host']};dbname={$sql_details['db']}",
				$sql_details['user'],
				$sql_details['pass'],
				array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
			);
			$db->exec("set names utf8");
			return $db;
		} catch (PDOException $e) {
			self::fatal(
				"An error occurred while connecting to the database. " .
					"The error reported by the server was: " . $e->getMessage()
			);
		}
	}
	/**
	 * Execute an SQL query on the database
	 *
	 * @param  resource $db  Database handler
	 * @param  array    $bindings Array of PDO binding values from bind() to be
	 *   used for safely escaping strings. Note that this can be given as the
	 *   SQL query string if no bindings are required.
	 * @param  string   $sql SQL query to execute.
	 * @return array         Result from the query (all rows)
	 */
	static function sql_exec($db, $bindings, $sql = null) {
		// Argument shifting
		if ($sql === null) {
			$sql = $bindings;
		}
		$stmt = $db->prepare($sql);
		// Bind parameters
		// echo $sql;
		if (is_array($bindings)) {
			for ($i = 0, $ien = count($bindings); $i < $ien; $i++) {
				$binding = $bindings[$i];
				$stmt->bindValue($binding['key'], $binding['val'], $binding['type']);
			}
		}
		// Execute
		try {
			$stmt->execute();
		} catch (PDOException $e) {
			self::fatal("An SQL error occurred: " . $e->getMessage());
		}
		// Return all
		return $stmt->fetchAll(PDO::FETCH_BOTH);
	}
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Internal methods
	 */
	/**
	 * Throw a fatal error.
	 *
	 * This writes out an error message in a JSON string which DataTables will
	 * see and show to the user in the browser.
	 *
	 * @param  string $msg Message to send to the client
	 */
	static function fatal($msg) {
		echo json_encode(array(
			"error" => $msg
		));
		exit(0);
	}
	/**
	 * Create a PDO binding key which can be used for escaping variables safely
	 * when executing a query with sql_exec()
	 *
	 * @param  array &$a    Array of bindings
	 * @param  *      $val  Value to bind
	 * @param  int    $type PDO field type
	 * @return string       Bound key to be used in the SQL where this parameter
	 *   would be used.
	 */
	static function bind(&$a, $val, $type) {
		$key = ':binding_' . count($a);
		$a[] = array(
			'key' => $key,
			'val' => $val,
			'type' => $type
		);
		return $key;
	}
	/**
	 * Pull a particular property from each assoc. array in a numeric array,
	 * returning and array of the property values from each item.
	 *
	 *  @param  array  $a    Array to get data from
	 *  @param  string $prop Property to read
	 *  @return array        Array of property values
	 */
	static function pluck($a, $prop) {
		$out = array();
		for ($i = 0, $len = count($a); $i < $len; $i++) {
			$out[] = $a[$i][$prop];
		}
		return $out;
	}
	/**
	 * Return a string from an array or a string
	 *
	 * @param  array|string $a Array to join
	 * @param  string $join Glue for the concatenation
	 * @return string Joined string
	 */
	static function _flatten($a, $join = ' AND ') {
		if (!$a) {
			return '';
		} else if ($a && is_array($a)) {
			return implode($join, $a);
		}
		return $a;
	}
}

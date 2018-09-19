<?php 

	/**
     * HungerGames
     *
     * This program is free software: you can redistribute it and/or modify
     * it under the terms of the GNU Lesser General Public License as published by
     * the Free Software Foundation, either version 3 of the License, or
     * (at your option) any later version.
     *
     * @author Denzel Code
     * @link https://github.com/DenzelCode
     *
     */

	namespace HungerGames\Database;

	/**
	* Connection class
	*/
	class Connection {
		
		private $con;

		private static $connected = false;

		private static $instance;

		private $table = false;
                
        private $data = [];

		public function __construct(array $data) {		
			self::$instance = $this;
                        
            $this->data = $data;
		
	        if (!extension_loaded('pdo')) {
	        	throw new \Exception('HungerGames need PDO extension');
	        }
        }

		public function getInstance() : Connection {
			return self::$instance;
		}

		public function run() {
			try {
				$this->con = new \PDO("mysql:host=" . $this->data['host'] . ";port=" . $this->data['port'] . ";dbname=" . $this->data['db'], $this->data['user'], $this->data['pass']);
				
				self::$connected = true;
 			} catch (\PDOException $e) {
	        	return false;
	        }
		}

	    public function getPDO() : \PDO {
	    	return $this->con;
	    }

	    public function setTable($table) : Connection {
	    	$this->table = $table;
			
	    	return $this;
	    }

	    public function getTable() {
	    	return $this->table;
	    }

	    public function select(array $data = ['*'], $options = false) {
	    	if (empty($data)) {
	    		return false;
	    	} elseif (!$this->getTable()) {
	    		return false;
	    	} else {
	    		$query = 'SELECT ';

	    		$i = 0;

	            foreach ($data as $key) {

	                if ($i != (count($data) - 1)) {
	                    $query .= "{$key}, ";
	                } else {
	                    $query .= "{$key} ";
	                }  

	                $i++;
	            }

	            $query .= 'FROM ' . $this->getTable();

	            if ($options) {
	            	$query .= ' ' . $options;
	            }
            
	            $prepare = $this->con->prepare($query);

	            $execute = $prepare->execute();

	            return $prepare;
	    	}
	    }

	    public function insert(array $data) {
	    	if (empty($data)) {
	    		return false;
	    	} elseif (!$this->getTable()) {
	    		return false;
	    	} else {
	    		$query = 'INSERT INTO ' . $this->getTable() . ' (';
            
	            $i = 0;

	            foreach ($data as $key => $value) {

	                if ($i != (count($data) - 1)) {
	                    $query .= "{$key}, ";
	                } else {
	                    $query .= "{$key}";
	                }

	                $i++;
	            }

	            $query .= ') VALUES (';

	            $i = 0;

	            foreach ($data as $key => $value) {
	                
	                if ($i != (count($data) - 1)) {
	                    $query .= "'{$value}', ";
	                } else {
	                    $query .= "'{$value}'";
	                }

	                $i++;
	            }

	            $query .= ')';

	            $add = $this->con->prepare($query);

	            $add = $add->execute();

	            return $add;
	    	}
	    }

	    public function update(array $data, $options = false) {
	    	if (empty($data)) {
	    		return false;
	    	} elseif (!$this->getTable()) {
	    		return false;
	    	} else {
	    		$query = 'UPDATE ' . $this->getTable() . ' ';

                $i = 0;

                foreach ($data as $key => $value) {

					if (count($data) == 1) {
						$query .= "SET {$key} = '{$value}' ";
					} else {
						if ($i == 0) {
							$query .= "SET {$key} = '{$value}', ";
						} else if ($i != (count($data) - 1)) {
							$query .= "{$key} = '{$value}', ";
						} else {
							$query .= "{$key} = '{$value}'";
						}
					}

                    $i++;
                }

                if ($options) {
                	$query .= ' ' . $options;
				}
				
				// echo $query;

	            $update = $this->con->prepare($query);

	            $update = $update->execute();

	            return $update;
	    	}
	    }

	    public function delete($options = false) {
	    	if (!$this->getTable()) {
	    		return false;
	    	} else {
	    		$query = 'DELETE FROM ' . $this->getTable();

	    		if ($options) {
	    			$query .= ' ' . $options;
	    		}

	            $delete = $this->con->prepare($query);

	            $delete = $delete->execute();

	            return $delete;
	    	}
	    }

	    public function create(array $data) {
	    	if (!$this->getTable()) {
	    		return false;
	    	} else if (empty($data)) {
	    		return false;
	    	} else {
	    		$query = 'CREATE TABLE IF NOT EXISTS ' . $this->getTable() . ' ( ';

	    		$i = 0;

                foreach ($data as $key => $value) {

                    if ($i != (count($data) - 1)) {
                        $query .= "{$key} {$value}, ";
                    } else {
                        $query .= "{$key} {$value} ";
                    }

                    $i++;
                }

                $query .= ')';

	            $create = $this->con->prepare($query);

	            $create = $create->execute();

	            return $create;
	    	}
	    }

	    public function truncate($options = false) {
	    	if (!$this->getTable()) {
	    		return false;
	    	} else {
	    		$query = 'TRUNCATE TABLE ' . $this->getTable();

	    		if ($options) {
                	$query .= ' ' . $options;
                }

	            $truncate = $this->con->prepare($query);

	            $truncate = $truncate->execute();

	            return $truncate;
	    	}
	    }

	    public function drop($options = false) {
	    	if (!$this->getTable()) {
	    		return false;
	    	} else {
	    		$query = 'DROP TABLE ' . $this->getTable();

	    		if ($options) {
                	$query .= ' ' . $options;
                }

	            $drop = $this->con->prepare($query);

	            $drop = $drop->execute();

	            return $drop;
	    	}
	    }
	}

?>

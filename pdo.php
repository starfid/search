<?php
	class Database {
		var $link;
		function __construct($db) {
			try {
				$this->link = new PDO(
					$db['rdbms'].":host=".$db['host'].";dbname=".$db['dbname'].";port=".$db['port'],
					$db['username'],
					$db['password']
				);
				$this->link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			}
			catch(PDOException $e) {
				echo $e->getMessage(); exit;
			}
		}
		public function query($sql, $row = false) {
			$res = Array();
			$res['count'] = 0;
			try {
				$exec = $this->link->query($sql);
				$res['sql'] = $sql;
				$res['count'] = $exec->rowCount();
				$views = array('select','show','describe');
				$prefix = strtok(trim(preg_replace('/\PL/u',' ',strtolower($sql))),' ');
				
				if(in_array($prefix,$views)) {
					$res['cols'] = Array();
					if($exec->columnCount() > 0) {
						foreach(range(0, $exec->columnCount() - 1) as $columns) {
							$meta = $exec->getColumnMeta($columns);
							$res['cols'][] = $meta['name'];
						}
					}
					$res['match'] = $exec->fetchAll(PDO::FETCH_ASSOC);
					if(is_numeric($row)) return $res['match'][$row][$res['cols'][0]];
				}
			}
			catch(PDOException $e) {
				$res['error'] = $e->getMessage();
			}
			return $res;
		}
	}
?>

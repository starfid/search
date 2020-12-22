<?php
	class Search extends Database {
		public $benchmark, $data, $selectedCat, $result, $catfound, $keywords, $sql, $nodata, $error = Array();
		private $settings, $isSearch;

		function __construct($settings){
			$this->settings = $settings;
			parent::__construct($settings['database']);

			if(!isset($_SESSION['dictionary'])){
				$this->build_dictionary();
			}

			if(isset($_GET['search']) && strlen(trim($_GET['search']))>1){
				$this->isSearch = true;
				$this->keywords['original']['placeholder'] = trim(preg_replace('!\s+!',' ',preg_replace('/[^a-zA-Z0-9 ]/',' ',$_GET['search'])));
				$this->build_keywords();
			}

			$this->selectedCat = $this->isSearch && isset($_GET['cat']) && in_array($_GET['cat'],array_keys($this->settings['preference']['categories']))?$_GET['cat']:'all';
			$this->build_sql();
			$this->preparing_result();
			$this->error = array_filter($this->error);
		}

		function preparing_result(){
			foreach($this->sql as $sql){
				$q = $this->query($sql);
				$this->error[] = isset($q['error'])?$q['error']:NULL;
				for($i=0;$i<$q['count'];$i++){
					$this->data[] = $q['match'][$i];
					if(!empty($q['match'][$i]['category'])){
						$this->catFound[] = $q['match'][$i]['category'];
					}
				}
			}

			if(!is_null($this->data)){
				if($this->isSearch){
					
					//rank multiple sql results based by rank key
					$rank = array();

					//PHP 5.3
					foreach ($this->data as $key => $row){ $rank[$key] = $row['rank']; }
					
					//PHP 5.4+
					//$rank = array_column($this->data, 'rank');

					array_multisort($rank, SORT_DESC, $this->data);
				}
				$this->catFound = array_count_values($this->catFound);
			}
			else{
				$this->nodata = true;
			}
		}

		function build_dictionary(){

			//collect all dictionaries
			$dir = opendir($this->settings['dictionary_folder']);
			while(($file = readdir($dir)) !== false) {
				is_file($this->settings['dictionary_folder']."/".$file) && require($this->settings['dictionary_folder']."/".$file);
			}
			closedir($dir);

			//merge similarity and abbreviation
			$dictionary['similarity'] = array_merge(
				$dictionary['similarity'],
				$dictionary['abbreviation']
			);
			unset($dictionary['abbreviation']);
			
			//store dictionary session to session
			$_SESSION['dictionary'] = $dictionary;
		}


		function build_keywords(){

			$fullSentence = strtolower($this->keywords['original']['placeholder']);

			$this->keywords['original']['full'] = $fullSentence;
			$this->keywords['original']['words'] = explode(' ',$fullSentence);
			$this->keywords['new']['without_noise'] = array_diff($this->keywords['original']['words'],$_SESSION['dictionary']['noise']);
			$this->keywords['new']['similarity'] = array();

			foreach($this->keywords['new']['without_noise'] as $words){
				if(isset($_SESSION['dictionary']['similarity'][$words])) {
					$colon = explode(',',$_SESSION['dictionary']['similarity'][$words]);
					foreach($colon as $word){
						$this->keywords['new']['similarity'][] = str_replace("xslashx","'",$word);
					}
				}
			}
			
			$this->keywords['new']['merge'] = array_merge(
				$this->keywords['new']['similarity'],
				$this->keywords['new']['without_noise']
			);

			//unique words
			$this->keywords['new']['merge'] = array_unique($this->keywords['new']['merge']);

			//remove tiny word
			foreach($this->keywords['new']['merge'] as $words){
				if(strlen($words)>1) $this->keywords['new']['final'][] = $words;
			}

			if(!isset($this->keywords['new']['final'])){
				$this->isSearch = false;
				return;
			}

			//sort
			isset($this->keywords['new']['final']) && sort($this->keywords['new']['final']);

		}

		function build_sql(){
			foreach($this->settings['tables'] as $param){
				
				$select = "select";
				$table = $param['name'];
				$limit = $param['limit'];
				$entry = $param['entry'];

				$col = array();
				$col[] = "\n\t".$param['category']." as category";
				$col[] = "\n\t".$param['header']." as header";
				$col[] = "\n\t".$param['location']." as location";
				$col[] = "\n\t".$param['additional']." as additional";
				$col[] = "\n\t".$param['entry']." as entry";
				$col[] = "\n\t".$param['pubyear']." as pubyear";
				
				
				$cols = implode(',',$col);

				$having = $this->selectedCat != "all"?"having category = '".$this->selectedCat."'":"";
				$orderBy = "";
				$rank = "";
				$where = "";

				if($this->isSearch){
					$where = array();
					$rank = array();

					foreach($param['index'] as $column){
						$column = "trim(lcase(".$column."))";

						$where[] = "\n\t".$column." like '%".$this->keywords['original']['full']."%'";
	
						$rank[] = "\n\tcast(if(".$column."='".$this->keywords['original']['full']."','400',0) as signed) ";

						if(!in_array($this->keywords['original']['full'],$_SESSION['dictionary']['low'])){
							$rank[] = "\n\tcast(if(instr(concat('xzxz',".$column."),'xzxz".$this->keywords['original']['full']."')>0,'300',0) as signed) ";
							$rank[] = "\n\tcast(if(instr(concat(".$column.",'xzxz'),'".$this->keywords['original']['full']."xzxz')>0,'250',0) as signed) ";
						}

						foreach($this->keywords['new']['final'] as $word){
							$where[] = "\n\t".$column." like '%".$word."%'";

							if(!in_array($word,$_SESSION['dictionary']['low'])){
								$rank[] = "\n\tcast(if( ".$column." = '".$word."' and (length(".$column.") - length(replace(".$column.", ' ', '')) + 1) = 1 ,'200',0) as signed) ";
								$rank[] = "\n\tcast(if( ".$column." = '".$word."' and (length(".$column.") - length(replace(".$column.", ' ', '')) + 1) > 1 ,'200',0) as signed) ";
								$rank[] = "\n\tcast(if( instr(concat('$',".$column."),'$".$word."')>0 and (length(".$column.") - length(replace(".$column.", ' ', '')) + 1) = 2 ,'21',0) as signed) ";
								$rank[] = "\n\tcast(if( instr(concat(".$column.",'$'),'".$word."$')>0 and (length(".$column.") - length(replace(".$column.", ' ', '')) + 1) = 2 ,'20',0) as signed) ";

								$rank[] = "\n\tcast(if(instr(concat(' ',".$column.",' '),' ".$word." ')>0,'6',0) as signed)";
								$rank[] = "\n\tcast(if(instr(concat(' ',".$column."),' ".$word."')>0,'7',0) as signed)";
								$rank[] = "\n\tcast(if(instr(concat(".$column.",' '),'".$word." ')>0,'2',0) as signed)";
							}
							else {
								$rank[] = "\n\tcast(if(instr(concat(' ',".$column.",' '),' ".$word." ')>0,'3',0) as signed)";
								$rank[] = "\n\tcast(if(instr(concat(' ',".$column."),' ".$word."')>0,'2',0) as signed)";
								$rank[] = "\n\tcast(if(instr(concat(".$column.",' '),'".$word." ')>0,'1',0) as signed)";
							}
						}
					}

					$rank = implode(" + ",$rank)." as rank,";
					$where = "where ".implode(" or ", $where);
					$orderBy = "order by rank desc";
				}
				else {
					$orderBy = "order by ".$entry." desc";
				}
				
				$this->sql[] = $select." ".$rank." ".$cols."\nfrom ".$table."\n".$where."\n".$having."\n".$orderBy."\nlimit ".$limit;
			}
		}
	}


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

<?php
	class Search extends Database {
		public $data, $selectedCat, $catFound, $years, $langs, $keywords, $sql, $nodata, $benchmarkStarted, $error = Array(), $isSearch, $isStrict;
		private $settings;

		function __construct($settings){
			$this->benchmarkStarted = microtime(true);
			$this->settings = $settings;
			parent::__construct($settings['database']);

			if(!isset($_SESSION['dictionary'])){
				$this->build_dictionary();
			}

			if(isset($_GET['search']) && strlen(trim($_GET['search']))>1){
				$this->isSearch = true;
				$_GET['search'] = iconv('UTF-8', 'ASCII//TRANSLIT', $_GET['search']);
				
				//space, alphanum, dash, single and double quote
				$this->keywords['original']['placeholder'] = trim(preg_replace('!\s+!',' ',preg_replace('/[^a-zA-Z0-9\'\" ]/',' ',$_GET['search'])));

				//space and alphanum
				$this->keywords['original']['clean'] = trim(preg_replace('!\s+!',' ',preg_replace('/[^a-zA-Z0-9\' ]/',' ',$_GET['search'])));

				$this->isStrict = str_word_count($this->keywords['original']['clean']) == 1 || (substr($this->keywords['original']['placeholder'],0,1)=="\"" && substr($this->keywords['original']['placeholder'],-1) == "\"")?true:false;
				
				$this->build_keywords();
			}

			$this->selectedCat = $this->isSearch && isset($_GET['cat']) && in_array($_GET['cat'],array_keys($this->settings['preference']['categories']))?$_GET['cat']:'all';
			$this->build_sql();
			$this->preparing_result();
			$this->error = array_filter($this->error);
		}

		
		function build_keywords(){
			$fullSentence = strtolower($this->keywords['original']['clean']);

			//fullsetence without punctuation
			$this->keywords['original']['full'] = $fullSentence;
			
			//support quoted words
			$this->keywords['original']['words'] = array_map(
				function($str){return trim(str_replace("\"", "", $str));},
				preg_split('/("[^"]*")|\h+/', strtolower($this->keywords['original']['placeholder']), -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE)
			);

			//new word from striped word contain non-alpha 
			$newAlpha = array();
			foreach($this->keywords['original']['words'] as $word){
				$onlyAlpha = preg_replace('/[^a-z]/i','', $word);
				if($onlyAlpha != $word) {
					$newAlpha[] =  $onlyAlpha;
				}
			}
			if(count($newAlpha)>0){
				$this->keywords['original']['words'] = array_merge($this->keywords['original']['words'],$newAlpha);
			}

			$this->keywords['new']['without_noise'] = array_diff($this->keywords['original']['words'],$_SESSION['dictionary']['noise']);

			//full word alternative from permutation
			$wordCount = count($this->keywords['new']['without_noise']);
			if($wordCount == count($this->keywords['original']['words']) && $wordCount > 1 && $wordCount < 4){
				$permut = $this->permutation($this->keywords['new']['without_noise']);
				foreach($permut as $word){
					if($word != $this->keywords['original']['words']) $this->keywords['new']['fullalternative'][] = implode(' ',$word);
				}
			}

			$this->keywords['new']['alternative'] = array();

			foreach($this->keywords['new']['without_noise'] as $words){

				//checking similarity dictionary
				if(isset($_SESSION['dictionary']['similarity'][$words])) {
					$colon = explode(',',$_SESSION['dictionary']['similarity'][$words]);
					foreach($colon as $word){
						$this->keywords['new']['alternative'][] = str_replace("xslashx","'",$word);
					}
				}

				//checking suffix dictionary
				if(isset($_SESSION['dictionary']['suffix_exception'])){
					foreach($_SESSION['dictionary']['suffix_exception'] as $suffix => $exception){
						if(substr($words,-strlen($suffix)) == $suffix && !in_array($words,$exception)){
							$this->keywords['new']['alternative'][] = substr($words,0,-strlen($suffix));
						}
					}
				}

				//checking prefix dictionary
				if(isset($_SESSION['dictionary']['prefix_exception'])){
					foreach($_SESSION['dictionary']['prefix_exception'] as $prefix => $exception){
						if(substr($words,0,strlen($prefix)) == $prefix && !in_array($words,$exception)){
							$this->keywords['new']['alternative'][] = substr($words,strlen($prefix));
							$this->keywords['new']['alternative'][] = $prefix." ".substr($words,strlen($prefix));
						}
					}
				}

				//checking duplicate letter, offer alternate without letter duplication
				if(preg_match('/(\w)\1+/', $words)){
					$this->keywords['new']['alternative'][] = preg_replace('/(\w)\1+/', '$1', $words);
				}
				
				//checking consonant followed by letter h
				if(preg_match('/([b-df-hj-np-tv-z])h/i',$words)){
					$this->keywords['new']['alternative'][] = preg_replace('/([b-df-hj-np-tv-z])h/i','$1',$words);
				}

				//alternative spelling
				if(preg_match('/oe|dj|dz|sy/', $words)){
					$this->keywords['new']['alternative'][] = str_replace(array('oe','dj','dz','sy'),array('u','j','z','sh'),$words);
				}
				
			}
			
			$this->keywords['new']['merge'] = array_merge(
				$this->keywords['new']['alternative'],
				$this->keywords['new']['without_noise']
			);

			//unique words
			$this->keywords['new']['merge'] = array_unique($this->keywords['new']['merge']);

			//remove tiny word
			foreach($this->keywords['new']['merge'] as $words){
				if(strlen($words)>2) $this->keywords['new']['final'][] = $words;
			}

			if(!isset($this->keywords['new']['final'])){
				$this->isSearch = false;
				return;
			}

			//sort
			isset($this->keywords['new']['final']) && sort($this->keywords['new']['final']);

		}

		function correction(){
			$oldData = $this->data;
			$newData = array();
			$dataCount = count($oldData);

			for($i=0;$i<$dataCount;$i++){
				$oldData[$i]['corrected'] = false;
				
				$oldData[$i]['effected'] = array();
				
				$oldData[$i]['rankBefore'] = $oldData[$i]['rank'];

				//checking first word from both header and additional are the same
				$indexed = preg_replace('/\s+/', ' ',strtolower(preg_replace('/[^a-z0-9\-\' ]/i','',$oldData[$i]['header']." ".$oldData[$i]['additional'])));
				$indexed = explode(' ',$indexed);

				if($indexed[0] == strtolower(substr(trim($oldData[$i]['additional']),0,strlen($indexed[0])))){
					$newAdditional = explode('.',$oldData[$i]['additional']);
					$oldData[$i]['additionalFirstWord'] = $newAdditional[0];
					if($oldData[$i]['rank'] > 41){
						//have single keyword
						$oldData[$i]['rank'] = $oldData[$i]['rank'] - 40;
					}
					array_shift($newAdditional);
					//$oldData[$i]['index0'] = $indexed;
					$oldData[$i]['additional'] = preg_replace('/^[^a-z]+/i', '',implode('.',$newAdditional));
				}


				$indexed = preg_replace('/\s+/', ' ',strtolower(preg_replace('/[^a-z0-9\-\' ]/i','',$oldData[$i]['header']." ".$oldData[$i]['additional'])));
				$oldData[$i]['indexed'] = $indexed;

				$indexed = explode(' ',$indexed);
				$words = array_count_values($indexed);
				
				foreach($words as $word => $count){
					
					$oldData[$i]['diff'][$word] = $count;

					if(in_array($word,$this->keywords['new']['final']) && $count > 1 && $oldData[$i]['rank'] > 5){
						$netralized = ($count-1)*5;
						
						$oldData[$i]['rank'] = $oldData[$i]['rank'] - $netralized;
						
						$oldData[$i]['corrected'] = true;
						$oldData[$i]['effected'][$word] = $netralized;
					}
					if($count==1 && in_array($word,$this->keywords['new']['final'])){
						$oldData[$i]['found'][] = $word;
					}
				}

				if(isset($oldData[$i]['found']) && count(array_diff($this->keywords['new']['final'],$oldData[$i]['found']))<1){
					$oldData[$i]['rank'] = $oldData[$i]['rank'] + 100;
				}

				$newData[] = $oldData[$i];
			}
			$this->data = $newData;
		}
		function permutation($items, $perms = array( ), &$return = array()) {
			if (empty($items)) {
				$return[] = array_values($perms);
			}
			else {
				for ($i = count($items) - 1; $i >= 0; --$i) {
					 $newitems = $items;
					 $newperms = $perms;
					 list($foo) = array_splice($newitems, $i, 1);
					 array_unshift($newperms, $foo);
					$this->permutation($newitems, $newperms,$return);
				 }
				return $return;
			}
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
				$col[] = "\n\tlower(".$param['lang'].") as lang";
				$col[] = "\n\t(length(".$param['index'][0].") - length(replace(".$param['index'][0].", ' ', '')) + 1) as wordcount";
				
				$having = $this->selectedCat != "all"?"having category = '".$this->selectedCat."'":"";
				$orderBy = "";
				$rank = "";
				$where = "";

				if($this->isSearch){
					$where = array();
					$rank = array();
					$gap = "$$$";

					foreach($param['index'] as $column){
						$column = "trim(lcase(replace(".$column.",'-',' ')))";
						$full = addslashes($this->keywords['original']['full']);
						$where[] = "\n\t".$column." like '%".$full."%'";

						if(array_key_exists("fullalternative",$this->keywords['new'])){
							foreach($this->keywords['new']['fullalternative'] as $alternative){
								$alternative = addslashes($alternative);
								$where[] = "\n\t".$column." like '%".$alternative."%'";	
								$rank[]  = "\n\tcast(if(".$column."='".$alternative."','390',0) as signed) ";
								$rank[] = "\n\tcast(if(instr(concat('".$gap."',".$column."),'".$gap.$alternative."')>0,'43',0) as signed) ";
								$rank[] = "\n\tcast(if(instr(concat(".$column.",'".$gap."'),'".$alternative.$gap."')>0,'38',0) as signed) ";
								$rank[] = "\n\tcast(if(instr(".$column.",'".$alternative."')>0,'32',0) as signed) ";
							}
						}

						$rank[] = "\n\tcast(if(trim(replace(".$column.",'.',' '))='".$full."','400',0) as signed) ";

						//puzling words
						$fullWordsCount = str_word_count($full);
						if($fullWordsCount > 1){
							$rank[] = "\n\tcast(if(instr(replace(".$column.",'.',' '),'".$full."')>0,'32',0) as signed) ";
							if($fullWordsCount > 2 && $fullWordsCount < 5){
								$word1and2 = explode(' ',$full);
								$word1and2 = $word1and2[0]." ".$word1and2[1];
								
								$rank[] = "\n\tcast(if(instr(concat('".$gap."',".$column."),'".$gap.$word1and2."')>0,'3',0) as signed) ";

								//full 2 words
								$rank[] = "\n\tcast(if(trim(replace(".$column.",'.',' '))='".$word1and2."','240',0) as signed) ";
								
								
								if($fullWordsCount==4){

									//full 3 words
									$word1and2and3 = $word1and2[0]." ".$word1and2[1]." ".$word1and2[3];
									$rank[] = "\n\tcast(if(trim(replace(".$column.",'.',' '))='".$word1and2and3."','290',0) as signed) ";

									$word1and2 = implode(" ",array_slice(explode(' ',$full),-2));
									$rank[] = "\n\tcast(if(instr(concat('".$gap."',".$column."),'".$gap.$word1and2."')>0,'2',0) as signed) ";
									$rank[] = "\n\tcast(if(instr(".$column.",'".$word1and2."')>0,'5',0) as signed) ";
								}
								
							}
						}

						//add bigger point for shorter sentence
						if($fullWordsCount < 4){
							$stripCol = "replace(replace(".$column.",':',' '),'  ',' ')";
							for($i=1;$i<5;$i++){
								$rank[] = "\n\tcast(if((length(".$column.") - length(replace(".$column.", ' ', '')) + 1) = ".$i.",'".(14-$i)."',0) as signed) ";
							}
						}
						
						if(!in_array($full,$_SESSION['dictionary']['low'])){
							$rank[] = "\n\tcast(if(instr(concat('".$gap."',".$column."),'".$gap.$full."')>0,'115',0) as signed) ";
							$rank[] = "\n\tcast(if(instr(concat(".$column.",'".$gap."'),'".$full.$gap."')>0,'115',0) as signed) ";
						}

						foreach($this->keywords['new']['final'] as $word){
							if(strlen(trim($word))<3) continue;
							$word = addslashes($word);
							$where[] = "\n\t".$column." like '%".$word."%'";

							
							if(!in_array($word,$_SESSION['dictionary']['low'])){
								$rank[] = "\n\tcast(if(instr(concat(' ',".$column.",' '),' ".$word." ')>0,'6',0) as signed) ";
								$rank[] = "\n\tcast(if(instr(concat(' ',".$column."),' ".$word."')>0,'7',0) as signed) ";
								$rank[] = "\n\tcast(if(instr(concat(".$column.",' '),'".$word." ')>0,'2',0) as signed) ";
							}
							else {
								$rank[] = "\n\tcast(if(instr(concat(' ',".$column.",' '),' ".$word." ')>0,'3',0) as signed) ";
								$rank[] = "\n\tcast(if(instr(concat(' ',".$column."),' ".$word."')>0,'2',0) as signed) ";
								$rank[] = "\n\tcast(if(instr(concat(".$column.",' '),'".$word." ')>0,'1',0) as signed) ";
							}
							
						}
					}

					$rank = implode(" + ",$rank)." as rank,";
					$where = "where ".implode(" or ", $where);
					$orderBy = "order by rank desc, wordcount asc";
				}
				else {
					$orderBy = "order by ".$entry." desc";
				}

				$cols = implode(',',$col);
				
				$this->sql[] = $select." ".$rank." ".$cols."\nfrom ".$table."\n".$where."\n".$having."\n".$orderBy."\nlimit ".$limit;
			}
		}

		
		function preparing_result(){
			foreach($this->sql as $sql){
				$q = $this->query($sql);
				$this->error[] = isset($q['error'])?$q['error']:NULL;
				for($i=0;$i<$q['count'];$i++){
					if($q['match'][$i]['header'] == "") continue;
					$this->data[] = $q['match'][$i];
					if(!empty($q['match'][$i]['category'])){
						$this->catFound[] = $q['match'][$i]['category'];
					}
					if(!empty($q['match'][$i]['pubyear']) && preg_match('/^19|20\d{2}/',$q['match'][$i]['pubyear']) ){
						$this->years[] = $q['match'][$i]['pubyear'];
					}
					if(!empty($q['match'][$i]['lang'])){
						$this->langs[] = ucwords(strtolower($q['match'][$i]['lang']));
					}
				}
			}

			if(!is_null($this->data)){
				if($this->isSearch){
					

					$this->correction();
					
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

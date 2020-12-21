<?php
	class Serp {
		public $data;
		private $result, $keywords, $selectedCat, $allCats, $name, $emptyResult, $debug, $placeholder, $error;

		function __construct($x, $preference){
			$this->keywords = $x->keywords;
			
			$this->allCats = array_keys($preference['categories']);
			array_unshift($this->allCats,"all");

			$this->selectedCat = $x->selectedCat;
			$this->pref = $preference;
			$this->debug = $preference['debug'];

			$this->result = $x->data;
			$this->error = $x->error;
			$this->emptyResult = is_null($x->data)?True:False;
			$this->emptyKeyword = (!isset($_GET['search']) || empty($_GET['search']))?True:False;
			
			$this->placeholder = isset($this->keywords['original'])?$this->keywords['original']['placeholder']:"";

			$this->html();
		}
		function highlight($txt){
			if(
				!isset($this->keywords) || 
				!array_key_exists('new',$this->keywords) || 
				!array_key_exists('final',$this->keywords['new'])
			) return $txt;

			$words = $this->keywords['new']['final'];
			array_multisort(array_map('strlen', $words), $words);
			$txt = preg_replace("#(".implode("|",$words).")#i", "<strong>$1</strong>", $txt);
			return $txt;
		}
		function html(){
			$s = "<!DOCTYPE html>";
			$s .= "\n<html lang='en'>";
			$s .= "\n\t<head>";

			$header = !empty($this->placeholder)?ucwords($this->placeholder)." - ":"";
			$header = $header.$this->pref['site']['name'];

			$s .= "\n\t\t<title>".$header."</title>";
			$s .= "\n\t\t<meta content=\"yes\" name=\"apple-mobile-web-app-capable\" />";
			$s .= "\n\t\t<meta content=\"notranslate\" name=\"google\" />";
			$s .= "\n\t\t<meta name=\"theme-color\" content=\"#5D5D5D\" />";
			$s .= "\n\t\t<meta content=\"".$this->pref['site']['desc']."\" name=\"description\" />";
			$s .= "\n\t\t<meta content=\"width=device-width,initial-scale=1,shrink-to-fit=no\" name=\"viewport\" />";
			$s .= "\n\t\t<meta content=\"text/html;charset=utf-8\" http-equiv=\"Content-Type\" />";
			$s .= "\n\t\t<meta content=\"telephone=no\" name=\"format-detection\" />";
			$s .= "\n\t\t<link href=\"cache/style.css\" rel=\"stylesheet\" type=\"text/css\" />";
			$s .= "\n\t\t<script src=\"cache/script.js\" type=\"text/javascript\"></script>";
			$s .= "\n\t</head>";
			$s .= "\n\t<body class=\"main-bg\">";

			if($this->emptyKeyword){
				$s .= "\n\t\t\t<div id=\"campaign\">";
				
				$s .= "\n\t\t\t\t<div class=\"mid1000\">";
				
				$s .= "\n\t\t\t\t\t<h1>".$this->pref['campaign']['title']."</h1>";
				$s .= "\n\t\t\t\t\t<h2>".$this->pref['campaign']['desc']."</h2>";

				$s .= "\n\t\t\t\t</div>";

				$s .= "\n\t\t\t\t<div id=\"edge\" style=\"\">&nbsp;</div>";
				$s .= "\n\t\t\t</div>";
			}

			$s .= "\n\t\t<div>";
			$s .= "\n\t\t\t<div id=\"top-head\" class=\"float mid1000\">";
			
			$s .= "\n\t\t\t\t<div id=\"searchBox\">";
			$s .= "\n\t\t\t\t\t<form method=\"get\" action=\"?\">";
			$s .= "\n\t\t\t\t\t\t<input placeholder=\"Search here\" name=\"search\" id=\"search\" inputmode=\"search\" type=\"search\" value=\"".$this->placeholder."\" ondblclick=\"this.value=''\" autocomplete=\"off\" autocorrect=\"off\" spellcheck=\"false\" autocapitalize=\"off\" />";
			$s .= "\n\t\t\t\t\t\t<input type=\"hidden\" id=\"cat\" name=\"cat\" value=\"".$this->selectedCat."\" />";
			$s .= "\n\t\t\t\t\t\t<img id=\"magnify\" src=\"cache/magnify.png\" onclick=\"submit();\" />";
			$s .= "\n\t\t\t\t\t</form>";
			$s .= "\n\t\t\t\t</div>";

			$s .= "\n\t\t\t</div>";
			$s .= "\n\t\t</div>";

			if(!$this->emptyKeyword){
				$s .= "\n\t\t<div id=\"catswrap\">";
				$s .= "\n\t\t\t<ul id=\"cats\" class=\"mid970\">";

				
				foreach($this->allCats as $category){
					$borderBottom = $category==$this->selectedCat?" class=\"selCat\"":"";
					$s .= "\n\t\t\t\t<li onclick=\"selectCat(this)\"".$borderBottom." id=\"".strtolower($category)."\">".ucwords($category)."</li>";
				}

				$s .= "\n\t\t\t</ul>";
				$s .= "\n\t\t</div>";
			}
		
			
			$s .= "\n\t\t<div id=\"content\" class=\"float mid1000\">";

			$s .= "\n\t\t\t<div class=\"left\" id=\"results\">";

			if(count($this->error)>0){
				$s .= "\n\t\t\t\t<dl>\n\t\t\t\t\t<dt>Check your SQL in table settings</dt><dd>".implode('<br />',$this->error)."</dd></dl>";
			}
			elseif($this->emptyResult){
				$s .= "\n\t\t\t\t<dl>\n\t\t\t\t\t<dd>";
				if(!empty($this->placeholder)){
					
					$s .= "No results found for <strong>".$this->placeholder."</strong> ";
					$s .= $this->selectedCat != "all"?"in this ".$this->selectedCat." category":"";
				}
				elseif(is_null($this->error)){
					$s .= "No data was found";
				}
				else{
					$s .= implode($this->error,'<br />');
				}
				$s .= "\n\t\t\t\t\t</dd>";
				$s .= "\n\t\t\t\t</dl>";
			}
			else{
				for($i=0;$i<count($this->result);$i++){
					$data = $this->result[$i];
					
					$title = in_array('rank',$data)&&$this->debug?" title=\"".$data['rank']."\"":"";

					$s .= "\n\t\t\t\t<dl".$title.">";
					$s .= "\n\t\t\t\t\t<dt>".$this->highlight(stripslashes(stripslashes($data['header'])))."</dt>";
					$s .= "\n\t\t\t\t\t<dd>";
					$s .= "\n\t\t\t\t\t\t<div class=\"loc\">".ucfirst($data['category'])." &gt; ".ucfirst($data['location'])."</div>";
					$s .= "\n\t\t\t\t\t\t<div class=\"info\">".$this->highlight(stripslashes($data['additional']))."</div>";
					$s .= "\n\t\t\t\t\t</dd>";
					$s .= "\n\t\t\t\t</dl>";
				}
			}

			$s .= "\n\t\t\t</div>";
			$s .= "\n\t\t</div>";

			$s .= "\n\t<div>x</div>";

			$this->data = $s;

		}
		function xml(){

		}
		function json(){

		}
	}
?>

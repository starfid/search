<?php
	class Serp {
		public $data;
		private $result, $keywords, $catFound, $allCat, $name, $emptyResult, $debug, $placeholder, $error;

		function __construct($x, $preference){
			$this->keywords = $x->keywords;
			$this->allCat = $preference['prefix'];
			$this->pref = $preference;
			$this->debug = $preference['debug'];

			$this->result = $x->data;
			$this->error = $x->error;
			$this->emptyResult = is_null($x->data)?True:False;

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
			$header = $header.$this->pref['siteName'];

			$s .= "\n\t\t<title>".$header."</title>";
			$s .= "\n\t\t<meta content=\"yes\" name=\"apple-mobile-web-app-capable\" />";
			$s .= "\n\t\t<meta content=\"notranslate\" name=\"google\" />";
			$s .= "\n\t\t<meta content=\"width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no\" name=\"viewport\" />";
			$s .= "\n\t\t<meta content=\"text/html;charset=utf-8\" http-equiv=\"Content-Type\" />";
			$s .= "\n\t\t<meta content=\"telephone=no\" name=\"format-detection\" />";
			$s .= "\n\t\t<link href=\"cache/style.css\" rel=\"stylesheet\" type=\"text/css\" />";
			$s .= "\n\t\t<script src=\"cache/script.js\" type=\"text/javascript\"></script>";
			$s .= "\n\t</head>";
			$s .= "\n\t<body class=\"main-bg\">";
			$s .= "\n\t\t<div class=\"grey-bg\">";
			$s .= "\n\t\t\t<div id=\"top-head\" class=\"float mid1000\">";
			
			$s .= "\n\t\t\t\t<div id=\"searchBox\">";
			$s .= "\n\t\t\t\t\t<form method=\"get\">";
			$s .= "\n\t\t\t\t\t\t<input name=\"search\" id=\"search\" inputmode=\"search\" type=\"search\" value=\"".$this->placeholder."\" ondblclick=\"this.value=''\" autocomplete=\"off\" autocorrect=\"off\" spellcheck=\"false\" autocapitalize=\"off\" />";
			$s .= "\n\t\t\t\t\t\t<img id=\"magnify\" src=\"cache/magnify.png\" onclick=\"submit();\" />";
			$s .= "\n\t\t\t\t\t</form>";
			$s .= "\n\t\t\t\t</div>";

			$s .= "\n\t\t\t</div>";
			$s .= "\n\t\t</div>";
			$s .= "\n\t\t<div class=\"grey-bg\" id=\"catswrap\">";
			$s .= "\n\t\t\t<ul id=\"cats\" class=\"mid970\">";

			$catSelected = " id=\"cat-selected\"";
			
			$allSelected = isset($this->keywords) && array_key_exists('category',$this->keywords)?'':$catSelected;
			$s .= "\n\t\t\t\t<li class=\"cat\" onclick=\"add('')\"".$allSelected.">All</li>";

			foreach($this->allCat as $category => $prefix){
				$listSelected = isset($this->keywords) && array_key_exists('category',$this->keywords) && $this->keywords['category'] == $category ?$catSelected:'';
				$s .= "\n\t\t\t\t<li".$listSelected." class=\"cat\" onclick=\"add('".$category."')\">".ucwords($category)."</li>";
			}

			$s .= "\n\t\t\t</ul>";
			$s .= "\n\t\t</div>";
			$s .= "\n\t\t<div id=\"browse-by\" class=\"mid970\">";
			$s .= "\n\t\t\tShow latest from";
			$s .= "\n\t\t\t<select id=\"samples\" name=\"samples\" class=\"main-bg\" onchange=\"show();\">";
			$s .= "\n\t\t\t\t<option selected>category</option>";
			foreach($this->allCat as $category => $prefix){
				$s .= "\n\t\t\t\t<option value=\"".$category."\">".ucwords($category)."</option>";
			}
			$s .= "\n\t\t\t</select>";
			$s .= "\n\t\t</div>";
			$s .= "\n\t\t<div id=\"content\" class=\"float mid1000\">";

			if(
				isset($this->keywords) && 
				array_key_exists('new',$this->keywords) && 
				count($this->keywords['new']['similarity']) > 0
			){
				$s .= "\n\t\t\t<div class=\"right\" id=\"hero\">";
				$s .= "\n\t\t\t\tInclude searching for:<br />&bullet; ".implode("<br />&bullet; ",$this->keywords['new']['similarity']);
				$s .= "\n\t\t\t</div>";
			}
			elseif(!isset($_GET['search']) || empty($_GET['search'])){
				$s .= "\n\t\t\t<div class=\"right\" id=\"hero\">";
				$s .= "\n\t\t\t\t<img width=\"120\" class=\"right\" src=\"cache/profile.png\" />";
				$s .= "\n\t\t\t\t<h1>".$this->pref['siteName']."</h1>";
				$s .= "\n\t\t\t\t<h5 class=\"loc\">".$this->pref['shortDesc']."</h5>";
				$s .= "\n\t\t\t\t<p>".$this->pref['longDesc']."</p>";
				$s .= "\n\t\t\t</div>";
			}

			$s .= "\n\t\t\t<div class=\"left\" id=\"results\">";

			if($this->emptyResult){
				$s .= "\n\t\t\t\t<dl>\n\t\t\t\t\t<dd>";
				if(!empty($this->placeholder)){
					$s .= "No results found for <strong>".$this->placeholder."</strong>";
				}
				elseif(is_null($this->error)){
					$s .= "No data was found";
				}
				else{
					$s .= $this->error;
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

			$this->data = $s;

		}
		function xml(){

		}
		function json(){

		}
	}
?>
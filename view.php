<?php
	class Serp extends Tool{
		public $content, $keywords;
		private $data, $selectedCat, $allCats, $catFound, $name, $siteTitle, $emptyResult, $debug, $placeholder, $error;

		function __construct($result, $preference){
			$this->keywords = $result->keywords;

			$this->allCats = array_keys($preference['categories']);
			array_unshift($this->allCats,"all");

			$this->selectedCat = $result->selectedCat;
			$this->pref = $preference;
			$this->debug = $preference['debug'];
			
			$this->benchmarkStarted = $result->benchmarkStarted;
			$this->data = $result->data;
			$this->error = $result->error;
			$this->emptyResult = is_null($result->data)?True:False;
			$this->emptyKeyword = !$result->isSearch?true:false;

			if(!$this->emptyKeyword && isset($this->keywords['new']['final'])){
				array_multisort(array_map('strlen', $this->keywords['new']['final']), $this->keywords['new']['final']);
				$this->keywords['new']['final'] = array_map('trim',$this->keywords['new']['final']);
				$this->keywords['new']['final'] = array_reverse($this->keywords['new']['final']);
				$this->keywords['new']['final'] = array_values(array_filter($this->keywords['new']['final'], function($word) {
					return strlen($word) > 2;
				}));
			}
			
			$this->placeholder = isset($this->keywords['original'])?$this->keywords['original']['placeholder']:"";
			
			$this->siteTitle = !empty($this->placeholder)?ucwords($this->placeholder)." - ":"";
			$this->siteTitle = $this->siteTitle.$this->pref['site']['name'];

			$format = isset($_GET['format'])?$_GET['format']:NULL;
			method_exists($this,$format)?$this->$format():$this->html();

		}

		function html(){
			$s = "<!DOCTYPE html>";
			$s .= "\n<html lang='en'>";
			$s .= "\n\t<head>";
			$s .= "\n\t\t<title>".$this->siteTitle."</title>";
			$s .= "\n\t\t<meta content=\"yes\" name=\"apple-mobile-web-app-capable\" />";
			$s .= "\n\t\t<meta content=\"notranslate\" name=\"google\" />";
			$s .= "\n\t\t<meta content=\"#5D5D5D\" name=\"theme-color\" />";
			$s .= "\n\t\t<meta content=\"".$this->pref['site']['desc']."\" name=\"description\" />";
			$s .= "\n\t\t<meta content=\"width=device-width,initial-scale=1,shrink-to-fit=no\" name=\"viewport\" />";
			$s .= "\n\t\t<meta content=\"text/html;charset=utf-8\" http-equiv=\"Content-Type\" />";
			$s .= "\n\t\t<meta content=\"telephone=no\" name=\"format-detection\" />";
			$s .= "\n\t\t<link href=\"cache/style.css\" rel=\"stylesheet\" type=\"text/css\" />";
			$s .= "\n\t\t<script src=\"cache/script.js\" type=\"text/javascript\"></script>";
			$s .= "\n\t</head>";
			$s .= "\n\t<body id=\"main-bg\">";

			if($this->emptyKeyword){
				$s .= "\n\t\t\t<div id=\"campaign\">";
				
				$s .= "\n\t\t\t\t<div class=\"center\">";
				
				$s .= "\n\t\t\t\t\t<h1>".$this->pref['campaign']['title']."</h1>";
				$s .= "\n\t\t\t\t\t<h2>".$this->pref['campaign']['desc']."</h2>";

				$s .= "\n\t\t\t\t</div>";

				$s .= "\n\t\t\t\t<div id=\"edge\" style=\"\">&nbsp;</div>";
				$s .= "\n\t\t\t</div>";
			}
			
			$s .= "\n\t\t<div>";
			$s .= "\n\t\t\t<div id=\"top-head\" class=\"float center\">";
			
			$s .= "\n\t\t\t\t<div id=\"searchBox\">";
			$s .= "\n\t\t\t\t\t<form method=\"get\" action=\"?\" onsumit=\"return submitting()\">";
			$s .= "\n\t\t\t\t\t\t<input placeholder=\"Search here\" name=\"search\" id=\"search\" inputmode=\"search\" type=\"search\" value=\"".$this->placeholder."\" ondblclick=\"this.value=''\" autocomplete=\"off\" autocorrect=\"off\" spellcheck=\"false\" autocapitalize=\"off\" />";
			$s .= "\n\t\t\t\t\t\t<input type=\"hidden\" id=\"cat\" name=\"cat\" value=\"".$this->selectedCat."\" />";
			$s .= "\n\t\t\t\t\t\t<img id=\"magnify\" src=\"cache/magnify.png\" />";
			$s .= "\n\t\t\t\t\t</form>";
			$s .= "\n\t\t\t\t</div>";

			$s .= "\n\t\t\t</div>";
			$s .= "\n\t\t</div>";

			if(!$this->emptyKeyword){
				$s .= "\n\t\t<div id=\"catswrap\">";
				$s .= "\n\t\t\t<ul id=\"cats\" class=\"center\">";
				
				foreach($this->allCats as $category){
					$borderBottom = $category==$this->selectedCat?" class=\"selCat\"":"";
					$s .= "\n\t\t\t\t<li onclick=\"selectCat(this)\"".$borderBottom." id=\"".strtolower($category)."\">".$category."</li>";
				}

				$s .= "\n\t\t\t\t<li onclick=\"selectCat(this)\" id=\"lang\">Lang</li>";
				$s .= "\n\t\t\t\t<li onclick=\"selectCat(this)\" id=\"year\">Year</li>";
								
				$s .= "\n\t\t\t</ul>";
				$s .= "\n\t\t</div>";
			}
			
			if(!$this->emptyResult && $this->pref['benchmark']){
				$s .= "\n\t\t<div id=\"benchmark\" class=\"center\">About ".round((microtime(true)-$this->benchmarkStarted),2)." seconds</div>";
			}

			$s .= "\n\t\t<div id=\"main\" class=\"float center\">";

			if(!$this->emptyResult){
				$selected = $this->data[0];
				$s .= "\n\t\t\t<div class=\"right\" id=\"sideBar\">";
				$s .= "\n\t\t\t\t<div class=\"segment\">";
				$s .= "\n\t\t\t\t\t<h1 id='header'>".$selected['header']."</h1>";
				$s .= "\n\t\t\t\t\t<div id='additional'>".$selected['additional']."</div>";
				$s .= "\n\t\t\t\t\t<div><b>Category:</b> <span id='category'>".$selected['category']."</span></div>";
				$s .= "\n\t\t\t\t\t<div><b>Location:</b> <span id='location'>".$selected['location']."</span></div>";
				$s .= "\n\t\t\t\t\t<div><b>Published Year:</b> <span id='pubyear'>".$selected['pubyear']."</span></div>";
				$s .= "\n\t\t\t\t\t<div><b>Language:</b> <span id='language'>".$selected['lang']."</span></div>";
				$s .= "\n\t\t\t\t</div>";
				$s .= "\n\t\t\t</div>";
			}


			$s .= "\n\t\t\t<div class=\"left\" id=\"results\">";

			if(count($this->error)>0){
				$s .= "\n\t\t\t\t<dl>\n\t\t\t\t\t<dt class=\"error\">Check your SQL</dt>";
				$s .= "\n\t\t\t\t\t<dd>";
				$s .= "\n\t\t\t\t\t\t<div class=\"loc\">Settings.php &gt; Tables</div>";
				$s .= "\n\t\t\t\t\t\t<div class=\"info\">".implode('<br />',$this->error)."</div>";
				$s .= "\n\t\t\t\t\t</dd>";
				$s .= "\n\t\t\t\t</dl>";
			}
			elseif($this->emptyResult){
				$s .= "\n\t\t\t\t<dl class=\"warning\">\n\t\t\t\t\t<dd>";
				$s .= "No results found for <strong>".$this->placeholder."</strong> ";
				$s .= $this->selectedCat != "all"?"in this ".$this->selectedCat." category":"";
				$s .= "\n\t\t\t\t\t</dd>";
				$s .= "\n\t\t\t\t</dl>";
			}
			else{
				for($i=0;$i<count($this->data);$i++){
					$data = $this->data[$i];
					
					$title = !$this->emptyKeyword && $this->debug && isset($data['rank'])?" title=\"No. ".($i+1).". Rank ".$data['rank']."\"":"";
					$category = " data-cat=\"".$data['category']."\"";
					$lang = " data-lang=\"".$data['lang']."\"";
					$pubyear = " data-year=\"".$data['pubyear']."\"";
					
					$s .= "\n\t\t\t\t<dl".$title.$category.$lang.$pubyear.">";
					$s .= "\n\t\t\t\t\t<dt>".$this->highlight(stripslashes(stripslashes($data['header'])))."</dt>";
					$s .= "\n\t\t\t\t\t<dd>";
					$s .= "\n\t\t\t\t\t\t<div class=\"loc\"><span class=\"itemCat\">".ucfirst($data['category'])."</span> &gt; <span class=\"itemLoc\">".ucfirst($data['location'])."</span></div>";
					$s .= "\n\t\t\t\t\t\t<div class=\"info\">".$this->highlight(stripslashes($data['additional']))."</div>";
					$s .= "\n\t\t\t\t\t</dd>";
					$s .= "\n\t\t\t\t</dl>";
				}
			}

			$s .= "\n\t\t\t</div>";
			$s .= "\n\t\t</div>";

			$this->content = $this->pref['minimizeHTML']?preg_replace('/[\r\n|\n|\t]+/', '', $s):$s;

		}

		function rss(){
			header('Content-Type: text/xml;charset=UTF-8');
			$s = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
			$s .= "\n<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\" xmlns:content=\"http://purl.org/rss/1.0/modules/content/\" xmlns:media=\"http://search.yahoo.com/mrss/\">";
			$s .= "\n\t<channel>";
			$s .= "\n\t\t<lastBuildDate>".date("D, d M Y H:i:s T")."</lastBuildDate>";
			$s .= "\n\t\t<title>".$this->siteTitle."</title>";
			$s .= "\n\t\t<description>".$this->pref['site']['desc']."</description>";
			
			
			for($i=0;$i<count($this->data);$i++){
				$data = $this->data[$i];

				$s .= "\n\t\t<item>";
				$s .= "\n\t\t\t<pubdate>".date("D, d M Y H:i:s",strtotime($data['entry']))."</pubdate>";
				$s .= "\n\t\t\t<title>".stripslashes(stripslashes($data['header']))."</title>";
				$s .= "\n\t\t\t<guid>".ucfirst($data['category'])." > ".html_entity_decode(ucfirst($data['location']))."</guid>";
				$s .= "\n\t\t\t<description>".stripslashes($data['additional'])."</description>";
				$s .= "\n\t\t</item>";
			}
			$s .= "\n\t</channel>";
			$s .= "\n</rss>";
			
			$this->content = $s;
		}

	}

	class Tool {
		function highlight($txt){
			if(
				!isset($this->keywords) || 
				!array_key_exists('new',$this->keywords) || 
				!array_key_exists('final',$this->keywords['new'])
			) return $txt;

			$words = $this->keywords['new']['final'];
			$txt = preg_replace("#(".implode("|",$words).")#i", "<strong>$1</strong>", $txt);
			return $txt;
		}
	}

?>

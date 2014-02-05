<?php
/*
Plugin Name: Adiro InText Plugin
Plugin URI: http://www.adiro.de/wordpress-plugin/
Description: Adiro InText Plugin adds the InText code to your Blog.
Author: Adiro GmbH
Version: 1.2.2
Author URI: http://www.adiro.de/
*/

define("wpadiro_VERSION", 	"1.2.1");
define("wpadiro_TAGSTART", 	"<!-- aeBeginAds -->");
define("wpadiro_TAGEND", 	"<!-- aeEndAds -->");
define("wpadiro_NO_ADS", 	"<!-- aeNoAds -->");
define("wpadiro_CONGIG_NAMESPACE", "Adiro_InText_config");


$location = $_SERVER['REQUEST_URI'];

	if (!class_exists('wpadiro')) {
		
		class wpadiro{
			var $no_config = false;
			
			/********************************************************************************
			// general functions
			********************************************************************************/
			function wpadiro_activate(){
				$this->wpadiro_init();
				$this->wpadiro_save(wpadiro_CONGIG_NAMESPACE, $this->defaultArray);
			}
			
			function wpadiro_deactivate(){
				delete_option(wpadiro_CONGIG_NAMESPACE);
			}
			
			function wpadiro_init(){
				/*  initiate the default array */
				$this->defaultArray = array();
				$this->defaultArray['pub_zone_id']		= "";			//str
				$this->defaultArray['titlecolor']		= "FF0000";		//str
				$this->defaultArray['textcolor']		= "000000";		//str
				$this->defaultArray['linkcolor']		= "009ECA";		//str
				$this->defaultArray['hookcolor']		= "0000CC";		//str
				$this->defaultArray['hookcount']		= 8;			//int
				
				$this->defaultArray['normalstyle']		= "double";		//str
				$this->defaultArray['normalsize']		= "3";			//str
				$this->defaultArray['hooverstyle']		= "groove";		//str
				$this->defaultArray['hooversize']		= "3";			//str
				
				$this->defaultArray['excluded_pages'] 	= array();		//int
				$this->defaultArray['excluded_posts'] 	= array();		//int
				$this->defaultArray['excluded_cats'] 	= array();		//int
				$this->defaultArray['excluded_tags'] 	= array();		//str
				$this->defaultArray['excluded_phrases'] = array();		//str
				
				$this->defaultArray['adm_user']			= "showads";	//str
				$this->defaultArray['se_user']			= "showads";	//str
				$this->defaultArray['reg_user']			= "showads";	//str
				
				/* load all config values from db */
				$this->configArray = array();
				$this->configArray =  unserialize( get_option(wpadiro_CONGIG_NAMESPACE) );
				if(!$this->configArray || count($this->configArray) <= 0){
					$this->no_config = True;
				}
				
				/* check if the user sets the required settings */
				if(!$this->wpadiro_getVar("pub_zone_id"))
					$this->message = "<div id=\"message\" class=\"error\">Bitte tragen Sie Ihre Placement ID unter dem Reiter 'Allgemein' ein!<br/>Falls Sie noch nicht bei Adiro angemeldet sind, geht es <a href='http://publisher.adiro.de/register' title='' target='_blank'>hier</a> zur Adiro-Publisher Dashboard Registration.</div>";
					
				/* set the pagetype param */
				if( is_single() )
					$this->pagetype = "single";
				elseif( is_page() )
					$this->pagetype = "page";
				elseif(is_tag())
					$this->pagetype = "tag";
				elseif(is_category())
					$this->pagetype = "category";
					
				/* check if there is a $_POST request to save some data */
				if (isset($_POST['action']) && $_POST['action'] == 'insert'){
					foreach($_POST as $key=>$val){
						if($key == "excluded_phrases"){
							$val = explode("\n", $val);
							$this->wpadiro_setVar($key, $val);
							continue;
						}
						
						if(is_array($val))
							$this->wpadiro_setVar($key, $val, True);
						else
							$this->wpadiro_setVar($key, $val);
					}
					$this->wpadiro_save(wpadiro_CONGIG_NAMESPACE, $this->configArray);
				}
				return True;
			}
			
			function wpadiro_destruct(){
				/* destruct all member vars */
				unset( $this->defaultArray );
				unset( $this->configArray );
			}
			
			function wpadiro_getVar($name, $val=False){
				/* check if there is a configuration in the db */
				if( $this->no_config )
					$cfgArray = $this->defaultArray;
				else
					$cfgArray = $this->configArray;
				
				/* check if there is a value to validate */
				if($val){
					if( is_array($cfgArray[$name]) ){
						if( count($cfgArray[$name]) > 0 )
							if(in_array($val, $cfgArray[$name]))
								return True;
							else
								return False;
						else
								return False;
					}
					else{ 
						if($cfgArray[$name] == $val)
							return True;
						else
							return False;
					}
				}
				if(!$cfgArray[$name])
					return $this->defaultArray[$name];	
				return $cfgArray[$name];	
			}
			
			function wpadiro_setVar($name, $val, $array=False){
				unset($this->configArray[$name]);
				if($array)
					$this->configArray[$name][] = $val;
				else
					$this->configArray[$name] = $val;
				return True;
			}
			
			function wpadiro_save($key, $configArray){
				$configArray = serialize( $configArray );
				update_option($key, $configArray);
				$this->message = "<div id=\"message\" class=\"updated fade\">Data saved!</div>";
			}
			
			function wpadiro_checkReferrer(){
				$this->searchEngines['google.com'] 			= "q";
				$this->searchEngines['google.de'] 			= "q";
				$this->searchEngines['yahoo.com'] 			= "p";
				$this->searchEngines['yahoo.de'] 			= "p";
				$this->searchEngines['bing.com'] 			= "q";
				$this->searchEngines['suche.t-online.de'] 	= "q";
				$this->searchEngines['suche.web.de'] 		= "q";
				$this->searchEngines['search.lycos.de'] 	= "query";
				$this->searchEngines['search.lycos.com'] 	= "query";
				$this->searchEngines['suche.aol.de'] 		= "q";
				$this->searchEngines['de.ask.com'] 			= "q";
				
				$referer = wp_get_referer();
				$ref_arr = parse_url($referer);
				$ref_host = str_replace("www.", "", $ref_arr['host']);

				if(array_key_exists($ref_host, $this->searchEngines))
					return True;
				return False;
			}
			
			function wpadiro_filter($content, $callback=False){			
				/* check referre */
				if($this->wpadiro_checkReferrer() && $this->configArray['se_user'] == "blockads")
					return False;
				
				/* check admin user*/
				
				if(current_user_can('manage_options') && $this->configArray['adm_user'] == "blockads")
					return False;
				
				/* check reg user */
				if(is_user_logged_in() && !current_user_can('manage_options') && $this->configArray['reg_user'] == "blockads")
					return False;
					
				/* get the metadata from current page/post/tag/categorie */
				$pti 	= get_the_title();
				$tme	= get_the_time('F jS, Y');
				$ath	= get_the_author();
				$tgs	= get_the_tags();
				$cnt	= get_the_content();
				
				/* in_array filter */
				switch($this->pagetype){
					case "single":
						if( $this->wpadiro_getVar('excluded_posts', get_the_ID()) || strstr  ($cnt, wpadiro_NO_ADS))
							return False;
						break;
					case "page":
						if( $this->wpadiro_getVar('excluded_pages', get_the_ID()) || strstr  ($cnt, wpadiro_NO_ADS))
							return False;
						break;
					case "tag":
						if( $this->wpadiro_getVar('excluded_tags', get_the_ID()) || strstr  ($cnt, wpadiro_NO_ADS))
							return False;
						break;
					case "category":
						if( $this->wpadiro_getVar('excluded_cats', get_the_ID()) || strstr  ($cnt, wpadiro_NO_ADS))
							return False;
						break;
					default:
						break;
				}
				
				/* content filter */
				$excluded_phrases = $this->wpadiro_getVar('excluded_phrases');
				
				array_walk($excluded_phrases, array(&$this, 'trim_value'));
				$tmp_cnt = str_replace($excluded_phrases, "", $cnt);
				if( $tmp_cnt != $cnt )
					return False;
				return True;
			}
						
			function trim_value(&$value) 
			{ 
				$value = trim($value); 
			}
			
			/********************************************************************************
			// admin frontend functions
			********************************************************************************/
			function wpadiro_admin_init(){
				global $plugin_page;
				if (strpos($plugin_page, 'wpadiro') !== False) {
					
					/* register scripts */
					wp_deregister_script( 'jscolor' );
					wp_register_script( 'jscolor', WP_PLUGIN_URL . "/wpadiro/include/intext/jscolor/jscolor.js" );
					wp_enqueue_script('jscolor');
					
					/* init */
					$this->wpadiro_init();
				}
			}
			
			function wpadiro_tmpl_css(){
				return "<link rel='stylesheet' type='text/css' href='" . plugins_url( 'wpadiro' ) . "/css/base.css'></link>\n";			
			}
			
			function wpadiro_tmpl_header(){
				if(isset($this->message) && $this->message)
					echo $this->message;
				?>
				
				<div class="wpadiro_header">
					<?php
					/* load css */
					echo $this->wpadiro_tmpl_css();
					?>
					<div class="wpadiro_head_title">
							<img src='<? echo plugins_url("wpadiro/img/logo-icon.png"); ?>'>
							<span class="wpadiro_title vtop">Adiro WordPress Plugin v<?=wpadiro_VERSION?></span>
							<div class="wpadiro_head_title_links">
								<a href="http://www.adiro.de" target="_blank">Homepage</a>
								|
								<a href="http://www.adiro.de/kontakt/" target="_blank">Kontakt</a>
								|
								<a href="http://www.facebook.de/AdiroDe" target="_blank">Adiro bei Facebook</a>
								|
								<a href="http://twitter.com/AdiroDe" target="_blank">Adiro bei Twitter</a>
								
							</div>
					</div>	

					<div class="wpadiro_header_infos">
						<div class="wpadiro_head_twitter">
							<div class="wpadiro_header_infos_twitter">
								<script type="text/javascript" src="http://twitterjs.googlecode.com/svn/trunk/src/twitter.min.js"></script>
								<script type="text/javascript">
								getTwitters('tweet', { 
								  id: 'AdiroDE', 
								  count: 1, 
								  enableLinks: true, 
								  ignoreReplies: true, 
								  clearContents: true,
								  template: '<' + 'a href="http://twitter.com/%user_screen_name%/statuses/%id_str%/" target="_blank">"%text%"</a>'
								});
								</script>
								<div id="tweet">
								</div>
								<div id="facebook">
									<iframe src="http://www.facebook.com/plugins/activity.php?site=http%3A%2F%2Fwww.adiro.de&amp;width=500&amp;height=120&amp;header=true&amp;colorscheme=light&amp;recommendations=false" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:500px; height:120px;" allowTransparency="true"></iframe>
								</div>
							</div>
						</div>
					</div>
					
				</div>
				<?php
			}
			
			function wpadiro_admin_intext(){
				global $location;
				?>
				<div class="wrap">
				<?php
				echo $this->wpadiro_tmpl_header();
				?>
				
				<script type="text/javascript">
					jQuery(document).ready(function() {
					
						jQuery(".color-hint").toggle(function(){
							jQuery(".hint-text").fadeIn("slow");
						}, function(){
							jQuery(".hint-text").fadeOut("slow");
						});
						
							
						setSelects("#normalstyle", '<?=$this->wpadiro_getVar("normalstyle");?>');
						setSelects("#hooverstyle", '<?=$this->wpadiro_getVar("hooverstyle");?>');
						
						
						 jQuery("#intext_title").css("color", "#" + jQuery('#titlecolor').val());
						 jQuery("#intext_text").css("color", "#" + jQuery('#textcolor').val());
						 jQuery("#intext_link").css("color", "#" + jQuery('#linkcolor').val());
						 jQuery("#intext_underline").css("color", "#" + jQuery('#hookcolor').val());				 
						 jQuery("#intext_underline").css("border-bottom",  jQuery('#normalsize').val() + "px " + jQuery('#normalstyle').val());
						 
						 
						 jQuery('#hookcount, #normalsize, #hooversize').change( function(){
							if(jQuery(this).val() > 10)
								jQuery(this).val(10);
							if(jQuery(this).val() < 1)
								jQuery(this).val(1);
						 });
						 
						 jQuery('#titlecolor').change( function(){
							jQuery("#intext_title").css("color", "#" + jQuery(this).val());
						 });
						 jQuery('#textcolor').change( function(){
							jQuery("#intext_text").css("color", "#" + jQuery(this).val());
						 });
						 jQuery('#linkcolor').change( function(){
							jQuery("#intext_link").css("color", "#" + jQuery(this).val());
						 });
						 jQuery('#hookcolor').change( function(){
							jQuery("#intext_underline").css("color", "#" + jQuery(this).val());
						 });
						 jQuery('#normalstyle').change( function(){
							jQuery("#intext_underline").css("border-bottom",  jQuery('#normalsize').val() + "px " + jQuery('#normalstyle :selected').val());
						 });
						 jQuery('#normalsize').change( function(){
							jQuery("#intext_underline").css("border-bottom",  jQuery('#normalsize').val() + "px " + jQuery('#normalstyle :selected').val());
						 });
						 
						 jQuery(".wpadiro_preview_intext").hover(function(){
							jQuery("#intext_underline").css("border-bottom",  jQuery('#hooversize').val() + "px " + jQuery('#hooverstyle').val());
						}, function(){
							jQuery("#intext_underline").css("border-bottom",  jQuery('#normalsize').val() + "px " + jQuery('#normalstyle').val());
						});
						
						
						 jQuery("#intext_underline").hover(function(){
							jQuery(this).css("border-bottom",  jQuery('#hooversize').val() + "px " + jQuery('#hooverstyle').val());
						}, function(){
							jQuery(this).css("border-bottom",  jQuery('#normalsize').val() + "px " + jQuery('#normalstyle').val());
						});
							 
						});

					function setSelects(selector, value){
						jQuery(selector + " [value=" + value + "]").attr("selected", "selected");
					}
					
				</script>	
				<div class="wpadiro_formbox_title">
					<img src='<? echo plugins_url("wpadiro/img/cfg.png"); ?>'>
					<h3> wpadiro - InText Konfigurationen</h3>
				</div>
					<div class="wpadiro_formbox_content">
						<div class="wpadiro_config_intext" style="float:left;">
						  <form name="wpadiro_options" method="post" action="<?=$location ?>">
							  <table>
								<tr>
									<td>Titel Farbe: </td>
									<td><input id="titlecolor" class="color" name="titlecolor" value="<?=$this->wpadiro_getVar("titlecolor");?>" type="text" /><span class="color-hint">?<div class="hint-text" style="background-color: #666;color: #fff; width: 315px; position:absolute;left:293px; display:none"><div style="padding:10px;"><img src="<? echo plugins_url("wpadiro/img/helligkeit.png"); ?>" alt="Helligkeitseinstellungen" style="float:right;"/>Falls sich der Farbwert im Textfeld nicht ver&auml;ndert, wenn Sie eine Farbe ausw&auml;hlen, schauen Sie auf der rechten Seite des Color-Pickers auf die Helligkeitsskala</div></div></span></td>
								</tr>
								<tr>
									<td>Text Farbe: </td>
									<td><input id="textcolor" class="color" name="textcolor" value="<?=$this->wpadiro_getVar("textcolor");?>" type="text" /></td>
								</tr>
								<tr>
									<td>Link Farbe: </td>
									<td><input id="linkcolor" class="color" name="linkcolor" value="<?=$this->wpadiro_getVar("linkcolor");?>" type="text" /></td>
								</tr>
								<tr>
									<td>Hook Farbe: </td>
									<td><input id="hookcolor" class="color" name="hookcolor" value="<?=$this->wpadiro_getVar("hookcolor");?>" type="text" /></td>
								</tr>
								<tr>
									<td>&nbsp;</td><td>&nbsp;</td>	
								</tr>
								<tr>
									<td>Max Hooks: </td>
									<td><input id="hookcount" name="hookcount" value="<?=$this->wpadiro_getVar("hookcount");?>" type="text" /></td>
									<td style="color:#aaaaaa">(1-10)</td>
								</tr>
								<tr>
									<td>&nbsp;</td><td>&nbsp;</td>	
								</tr>
								<tr>
									<td>Hook Normal Style: </td>
									<td>
										<select id="normalstyle" name="normalstyle">
											<option value="solid">solid</option>
											<option value="dashed">dashed</option>
											<option value="dotted">dotted</option>
											<option value="double">double</option>
											<option value="ridge">ridge</option>
											<option value="groove">groove</option>
											<option value="inset">inset</option>
											<option value="outset">outset</option>
										</select>
									</td>
									<td>Hook Normal Gr&ouml;&szlig;e:</td>
									<td><input id="normalsize" name="normalsize" value="<?=$this->wpadiro_getVar("normalsize");?>" type="text" /></td>
									<td style="color:#aaaaaa">px</td>
								</tr>
								<tr>
									<td>Hook Hoover Style: </td>
									<td>
										<select id="hooverstyle" name="hooverstyle">
											<option value="solid">solid</option>
											<option value="dashed">dashed</option>
											<option value="dotted">dotted</option>
											<option value="double">double</option>
											<option value="ridge">ridge</option>
											<option value="groove">groove</option>
											<option value="inset">inset</option>
											<option value="outset">outset</option>
										</select>
									</td>
									<td>Hook Hoover Gr&ouml;&szlig;e:</td>
									<td><input id="hooversize" name="hooversize" value="<?=$this->wpadiro_getVar("hooversize");?>" type="text" /></td>
									<td style="color:#aaaaaa">px</td>
								</tr>
								<tr>
									<td>&nbsp;</td><td>&nbsp;</td>	
								</tr>
								</table>	
								<input type="submit" value="Speichern" />
								<input name="action" value="insert" type="hidden" />
							</form>
						</div>
						
						
						<div class="wpadiro_preview_intext" style="float:left;margin-left: 20px;">	
							<div style="width: 347px;height: 170px;position:relative; overflow:hidden;">
								<div id="AdInsider_InText_Show" style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent none repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; visibility: visible; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: auto; position: absolute; z-index: 9999997; opacity: 0.95; -moz-user-select: none;">
									<div style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent none repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: auto;">
									</div>
									<table style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent none repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: auto; border-spacing: 0px; border-collapse: collapse; width: 350px;">
										<tbody style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent none repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: auto;">
											<tr style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent none repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: auto;">
												<td style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent url(http://static.adiro.contextmatters.de/intext/images/adiro/tl.png) repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: auto; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; width: 15px; height: 48px;">
												</td>
												<td style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent url(http://static.adiro.contextmatters.de/intext/images/adiro/tm.png) repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: auto; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial;">
												</td>
												<td style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent url(http://static.adiro.contextmatters.de/intext/images/adiro/tr.png) repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: auto; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; width: 17px;">
												</td>
											</tr>
											<tr style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent none repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: auto;">
												<td style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent url(http://static.adiro.contextmatters.de/intext/images/adiro/ml.png) repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: auto; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; width: 11px;">
												</td>
												<td style="border: 0pt none ; margin: 0pt; padding: 0pt; background: rgb(255, 255, 255) none repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: auto; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial;">
													<div style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent none repeat scroll 0% 50%; overflow: hidden; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; vertical-align: baseline; direction: ltr; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: pointer; width: 311px; text-align: justify; z-index: 9999999; height: 97px;">
														<div id="intext_title" style="border: 0pt none ; margin: 5px 0pt 0pt; padding-left: 5px; background: transparent none repeat scroll 0% 50%; font-style: normal; text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; text-align: start; vertical-align: baseline; direction: ltr; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: inherit; color: rgb(39, 36, 32); font-family: Verdana,sans-serif; font-size: 12px; line-height: 16px; font-weight: bold;">
															 Adiro InText-Werbung
														</div>
														<div id="intext_text" style="border: 0pt none ; margin: 10px 0pt 0pt; padding-left: 5px; background: transparent none repeat scroll 0% 50%; font-weight: 400; font-style: normal; text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; text-align: start; vertical-align: baseline; direction: ltr; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: inherit; color: rgb(95, 95, 95); font-family: Verdana,sans-serif; font-size: 11px; line-height: 15px;">
															Erh&ouml;hen Sie Ihre Einnahmen mit InText von Adiro. Jetzt Anmelden!
														</div>
														<div id="intext_link" style="border: 0pt none ; margin: 0pt; padding-left: 5px; background: transparent none repeat scroll 0% 50%; font-weight: 400; font-style: normal; text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: inherit; color: rgb(139, 132, 0); white-space: nowrap; font-family: Verdana,sans-serif; font-size: 11px;">
															www.adiro.de
														</div>
													</div>
												</td>
												<td style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent url(http://static.adiro.contextmatters.de/intext/images/adiro/mr.png) repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: auto; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; width: 17px;">
												</td>
											</tr>
											<tr style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent none repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: auto;">
												<td style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent url(http://static.adiro.contextmatters.de/intext/images/adiro/bl.png) repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: auto; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; width: 11px; height: 34px;">
												</td>
												<td style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent url(http://static.adiro.contextmatters.de/intext/images/adiro/bm.png) repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: auto; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; height: 21px;">
												</td>
												<td style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent url(http://static.adiro.contextmatters.de/intext/images/adiro/br.png) repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: auto; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; width: 17px; height: 21px;">
												</td>
											</tr>
										</tbody>
									</table>
								<div style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent none repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; right: auto; bottom: auto; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: move; position: absolute; top: 0px; left: 0px; height: 25px; width: 311px;">
								</div>
								<img src="http://static.adiro.contextmatters.de/intext/images/adiro/brand.png" style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent none repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; right: auto; bottom: auto; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: pointer; position: absolute; top: 12px; left: 16px;">
								<img style="border: 0pt none; margin: 0pt; padding: 0pt; background-color: transparent; background-image: none; background-repeat: repeat; background-attachment: scroll; background-position: 0% 50%; -moz-background-size: auto auto; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; bottom: auto; left: auto; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: pointer; position: absolute; top: 13px; right: 37px;" src="http://static.adiro.contextmatters.de/intext/images/adiro/qbox.png">
								<img src="http://static.adiro.contextmatters.de/intext/images/adiro/close.png" style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent none repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; bottom: auto; left: auto; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: pointer; position: absolute; top: 13px; right: 19px;">
								</div>
							</div>
							<div style="position:relative; bottom:10px;">Dies ist eine <span id="intext_underline">Unterstreichung</span> in Ihrer ausgew&auml;hlten Farbe.</div>
							</div>









						
						
						<!--div class="wpadiro_preview_intext" style="float:left;margin-left: 20px;">					 
			
							<div style="width: 300px;height: 200px;position:relative;">
								<div id="AdInsider_InText_Show" style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent none repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; visibility: visible; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: auto; position: absolute; z-index: 9999997; opacity: 0.95; -moz-user-select: none;">
									<div style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent none repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: auto;">
									</div>
									<table style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent none repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: auto; border-spacing: 0px; border-collapse: collapse; width: 350px;">
										<tbody style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent none repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: auto;">
											<tr style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent none repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: auto;">
												<td style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent url(http://static.contextmatters.de/intext/images/adiro/tl.png) repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: auto; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; width: 18px; height: 33px;">
												</td>
												<td style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent url(http://static.contextmatters.de/intext/images/adiro/tm.png) repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: auto; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial;">
												</td>
												<td style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent url(http://static.contextmatters.de/intext/images/adiro/tr.png) repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: auto; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; width: 21px;">
												</td>
											</tr>
											<tr style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent none repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: auto;">
												<td style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent url(http://static.contextmatters.de/intext/images/adiro/ml.png) repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: auto; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; width: 18px;">
												</td>
												<td style="border: 0pt none ; margin: 0pt; padding: 0pt; background: rgb(255, 255, 255) none repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: auto; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial;">
													<div style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent none repeat scroll 0% 50%; overflow: hidden; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; vertical-align: baseline; direction: ltr; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: pointer; width: 311px; text-align: justify; z-index: 9999999; height: 97px;">
														<div id="intext_title" style="border: 0pt none ; margin: 5px 0pt 0pt; padding: 0pt; background: transparent none repeat scroll 0% 50%; font-style: normal; text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; text-align: start; vertical-align: baseline; direction: ltr; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: inherit; color: rgb(39, 36, 32); font-family: Verdana,sans-serif; font-size: 12px; line-height: 16px; font-weight: bold;">
															InText f&uuml;r die Massen
														</div>
														<div id="intext_text" style="border: 0pt none ; margin: 10px 0pt 0pt; padding: 0pt; background: transparent none repeat scroll 0% 50%; font-weight: 400; font-style: normal; text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; text-align: start; vertical-align: baseline; direction: ltr; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: inherit; color: rgb(95, 95, 95); font-family: Verdana,sans-serif; font-size: 11px; line-height: 15px;">
															Erh&ouml;hen Sie Ihre Einnahmen mit InText von Adiro. Jetzt Anmelden!
														</div>
														<div id="intext_link" style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent none repeat scroll 0% 50%; font-weight: 400; font-style: normal; text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: inherit; color: rgb(139, 132, 0); white-space: nowrap; font-family: Verdana,sans-serif; font-size: 11px;">
															www.adiro.de
														</div>
													</div>
												</td>
												<td style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent url(http://static.contextmatters.de/intext/images/adiro/mr.png) repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: auto; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; width: 21px;">
												</td>
											</tr>
											<tr style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent none repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: auto;">
												<td style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent url(http://static.contextmatters.de/intext/images/adiro/bl.png) repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: auto; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; width: 18px; height: 21px;">
												</td>
												<td style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent url(http://static.contextmatters.de/intext/images/adiro/bm.png) repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: auto; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; height: 21px;">
												</td>
												<td style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent url(http://static.contextmatters.de/intext/images/adiro/br.png) repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; top: auto; right: auto; bottom: auto; left: auto; position: static; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: auto; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; width: 21px; height: 21px;">
												</td>
											</tr>
										</tbody>
									</table>
								<div style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent none repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; right: auto; bottom: auto; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: move; position: absolute; top: 0px; left: 0px; height: 25px; width: 311px;">
								</div>
								<img src="http://static.contextmatters.de/intext/images/adinsider.png" style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent none repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; right: auto; bottom: auto; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: pointer; position: absolute; top: 3px; left: 5px;">
								<img src="http://static.contextmatters.de/intext/images/adiro/close.png" style="border: 0pt none ; margin: 0pt; padding: 0pt; background: transparent none repeat scroll 0% 50%; font-family: serif; font-size: 16px; font-weight: 400; font-style: normal; color: rgb(0, 0, 0); text-transform: none; text-decoration: none; letter-spacing: normal; word-spacing: normal; line-height: 20px; text-align: start; vertical-align: baseline; direction: ltr; -moz-background-clip: -moz-initial; -moz-background-origin: -moz-initial; -moz-background-inline-policy: -moz-initial; bottom: auto; left: auto; visibility: visible; z-index: auto; white-space: normal; clip: rect(auto, auto, auto, auto); float: none; clear: none; cursor: pointer; position: absolute; top: 8px; right: 20px;">
								</div>
							</div>
							<div style="position:relative; bottom:10px;">Dies ist eine <span id="intext_underline">Unterstreichung</span> in Ihrer ausgew&auml;hlten Farbe.</div>
						</div-->
						<div class="cls"></div>
					</div>
					
				</div>
			<?php
			}
			
			function wpadiro_admin_general(){
				global $location;
				?>				
				<script type="text/javascript">
					jQuery(document).ready(function() {
						jQuery(".placement-hint").toggle(function(){
							jQuery(".hint-text").fadeIn("slow");
						}, function(){
							jQuery(".hint-text").fadeOut("slow");
						});
					});
				</script>
				
				<div class="wrap">
				<?=$this->wpadiro_tmpl_header()?>
				<div class="wpadiro_formbox_title">
				<img src='<? echo plugins_url("wpadiro/img/cfg.png"); ?>'>
					<h3> wpadiro - Allgemeine Konfigurationen</h3>
				</div>
					<div class="wpadiro_formbox_content">
						<div style="margin-top:10px;margin-left:10px;margin-bottom:10px;">
							<form name="wpadiro_options" method="post" action="<?=$location ?>">
								<table>
									<tr>
										<td>Placement ID: </td>
										<td>
											<input name="pub_zone_id" value="<?=$this->wpadiro_getVar('pub_zone_id');?>" type="text" />
											<span class="placement-hint">?
												
											</span>
											<div class="hint-text" style="background-color:#666666;color:#FFFFFF;left:284px;position:absolute;top:13px;width:400px;display:none">
													<div style="padding:10px;">
														Ihre Placement ID finden Sie im <br /><a href="http://publisher.adiro.de/" title="" target="_blank">Publisher Dashboard</a> unter dem Men&uuml;punkt "Webseiten" (Spalte 'ID')
													</div>
												</div>
										</td>
									</tr>
								</table>	
								<input type="submit" value="Speichern" />
								<input name="action" value="insert" type="hidden" />
							</form>
						</div>
					</div>
					<div class="cls"></div>
				</div>
				<?php
			}
			
			function wpadiro_admin_filter(){
				global $location;
				?>
				<script type="text/javascript">
					function setSelects(selector, value){
						jQuery(selector + " [value=" + value + "]").attr("selected", "selected");
					}
					jQuery(document).ready(function() {
						setSelects(".wpadiro_adm_user",'<?=$this->wpadiro_getVar("adm_user");?>');
						setSelects(".wpadiro_reg_user",'<?=$this->wpadiro_getVar("reg_user");?>');
						setSelects(".wpadiro_se_user",'<?=$this->wpadiro_getVar("se_user");?>');
					});
				</script>
				<div class="wrap">
				<?PHP $this->wpadiro_tmpl_header(); ?>
				<div class="wpadiro_formbox_title">
				<img src='<? echo plugins_url("wpadiro/img/cfg.png"); ?>'>
					<h3> wpadiro - Filter Konfigurationen</h3>
				</div>
					<div class="wpadiro_formbox_content">
						<div style="margin-top:10px;margin-left:10px;margin-bottom:10px;">
							<form name="wpadiro_options" method="post" action="<?=$location ?>">
								<table>
									<tr>
										<td>Suchmaschinen Besucher</td>
										<td>
											<select name="se_user" class="wpadiro_se_user">
												<option value="showads">InText anzeigen</option>								
												<option value="blockads">InText nicht anzeigen</option>
											</select>
										</td>
									</tr>
									<tr>
										<td>Registrierte Benutzer</td>
										<td>
											<select name="reg_user" class="wpadiro_reg_user">
												<option value="showads">InText anzeigen</option>								
												<option value="blockads">InText nicht anzeigen</option>
											</select>
										</td>
									</tr>
									<tr>
										<td>Administrative Benutzer</td>
										<td>
											<select name="adm_user" class="wpadiro_adm_user">
												<option value="showads">InText anzeigen</option>								
												<option value="blockads">InText nicht anzeigen</option>
											</select>
										</td>
									</tr>
									<tr>
										<td>&nbsp;</td>
										<td>&nbsp;</td>
									</tr>
									<tr>
										<td class="vtop">Phrasen ausschlie&szlig;en</td>
										<td><textarea name="excluded_phrases" class="wpadiro_excluded_phrases"><?=implode("\n", $this->wpadiro_getVar('excluded_phrases'));?></textarea></td>
										<td class="vtop" style="color:#aaaaaa">('ENTER' sepertiert)</td>
									</tr>
								</table>	
								<input type="submit" value="Speichern" />
								<input name="action" value="insert" type="hidden" />
							</form>
						</div>
						<div class="cls"></div>
					</div>
				</div>
				<?php
			}
			
			
		/********************************************************************************
		// frontend
		********************************************************************************/
			
			function inject($content, $affiliateid) {
				if(empty($affiliateid))
					return;
					
				$defaultLayerCode = '<script language="javascript">';
				$defaultLayerCode .= 'document.write(\'<scr\'+\'ipt language="javascript1.1" src="http://adserver.adtech.de/addyn|3.0|1104|###AFFILIATEID###|1|16|ADTECH;loc=100;target=_blank;AdId=5318882;BnId=1;misc=[timestamp]"></scri\'+\'pt>\');';
				$defaultLayerCode .= '</script>';
				$defaultLayerCode = str_replace("###AFFILIATEID###", $affiliateid, $defaultLayerCode);

				return $defaultLayerCode;
			}
			
			function modifyLayerCode(&$defaultLayerCode, $props){
					if(empty($defaultLayerCode))
						return;
					
					$tmp_code = "<script type='text/javascript'>";
					foreach($props as $key=>$val){
						$tmp_code .= "$key = $val;\n";
					}
					$tmp_code .= "</script>";
					$defaultLayerCode =   $tmp_code . $defaultLayerCode;
					
			}

			function initAdInject($content){
				$this->wpadiro_init();
				
				if(is_404()||is_trackback()||is_feed()||$this->wpadiro_filter(get_the_content()) === False)
					return;
				
				$props = array(
						"ContextMatters_InText_aColors" 		=> "{ 'title': '#" . $this->wpadiro_getVar('titlecolor') . "', 'text': '#" . $this->wpadiro_getVar('textcolor') . "', 'url': '#" . $this->wpadiro_getVar('linkcolor') . "' }",
						"ContextMatters_InText_aUnderline"	=> "{ 'color': '#" . $this->wpadiro_getVar('hookcolor') . "', 'normal': '" . $this->wpadiro_getVar('normalsize') . "px " . $this->wpadiro_getVar('normalstyle') . "', 'hover': '" . $this->wpadiro_getVar('hooversize') . "px " . $this->wpadiro_getVar('hooverstyle') . "' }",
						"ContextMatters_InText_nMaxUnderline"	=> $this->wpadiro_getVar('hookcount')
				);
				$layerCode = $this->inject($content, $this->wpadiro_getVar('pub_zone_id'));
				$this->modifyLayerCode($layerCode, $props);
				echo $layerCode;
				return True;
			}

			function wrapContentForCrawler($content){
				return wpadiro_TAGSTART . $content . wpadiro_TAGEND;
			}


			// Adminmenu Optionen erweitern
			function wpadiro_options_add_menu() {
				add_menu_page('wpadiror', 'wpadiro', "update_plugins", __FILE__, array(&$this, 'wpadiro_admin_general'));
				add_submenu_page(__FILE__, 'Allgemein', 'Allgemein', "update_plugins", __FILE__, array(&$this, 'wpadiro_admin_general'));
				add_submenu_page(__FILE__, 'InText', 'InText', "update_plugins", "wpadiro/intext.php");
				add_submenu_page(__FILE__, 'Filter', 'Filter', "update_plugins", "wpadiro/filter.php");
				return True;	
			}
			
		}
	}


$wpadiro = new wpadiro();

//init actions
register_activation_hook(__FILE__, 		array(&$wpadiro, 'wpadiro_activate'));
register_deactivation_hook(__FILE__, 	array(&$wpadiro, 'wpadiro_deactivate'));

//admin actions
add_action('admin_init', 				array(&$wpadiro, 'wpadiro_admin_init'));
add_action('admin_menu',				array(&$wpadiro, 'wpadiro_options_add_menu'));

//frontend actions
add_action('wp_footer',					array(&$wpadiro, 'initAdInject'));
add_filter('the_content',				array(&$wpadiro, 'wrapContentForCrawler'));			
?>
<?php
/**
 * Cumulus
 *
 * Copyright 2009 by Stephane Boulard <lossendae@gmail.com>
 *
 * This file is part of Cumulus, a flash component to show your site tags rotating in 3D.
 *
 * Cumulus is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * Cumulus is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * Cumulus; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 *
 * @package cumulus
 */
/**
 * Main file class for Cumulus
 *
 * @name Cumulus
 * @author Stephane Boulard <lossendae@gmail.com>
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
 * @package cumulus
 */
class Cumulus{
    /**
     * @access protected
     * @var array A collection of preprocessed chunk values.
     */
    protected $chunks = array();
    /**
     * @access public
     * @var modX A reference to the modX object.
     */
    public $modx = null;
    /**
     * @access public
     * @var array A collection of properties to adjust Cumulus behaviour.
     */
    public $config = array();

    /**
     * The Cumulus Constructor.
     *
     * Create a new Cumulus object.
     *
     * @param modX &$modx A reference to the modX object.
     * @param array $config A collection of properties that modify Cumulus
     * behaviour.
     * @return Cumulus A unique Cumulus instance.
     */
    function __construct(modX &$modx,array $config = array()) {
        $this->modx =& $modx;

        $core = $this->modx->getOption('core_path').'components/cumulus/';
        $assets_url = $this->modx->getOption('assets_url').'components/cumulus/';
        $assets_path = $this->modx->getOption('assets_path').'components/cumulus/';
		
        $this->config = array_merge(array(
            'core_path' => $core,
            'model_path' => $core.'model/',
            'processors_path' => $core.'processors/',
            'controllers_path' => $core.'controllers/',
            'chunks_path' => $core.'chunks/',

            'base_url' => $assets_url,
            'css_url' => $assets_url.'css/',
            'js_url' => $assets_url.'js/',
            'flash_url' => $assets_url.'flash/',
            'connector_url' => $assets_url.'connector.php',
			
			'parents' => 1, 
			'landing' => 2,
			
			'depth' => 10,
			
			'days' => 0,
			'min' => 0,		
			

			'tvTags' => "tags",			
			'tagDelimiter' => ",",	
			
			'caseSensitive' => 1,		
				
			'limit' => 0,		
			
			'maxsize' => 17,
            'minsize' => 11,
            'height' => '240',
            'width' => '270',
            'bgcolor' => 'ffffff',
            'showCount' => '0',
            'tcolor' => 'BDCF9B',
            'tcolor2' => '464646',
            'hicolor' => 'C1DF8B',
            'speed' => '100',
            'distr' => 'true',
            'wmode' => 'transparent',
			/* @TODO */
			//Allow &toPlaceholder setting
            'container' => 'cumulus',		
			'soname' => 'so',
			'cumulus_movie' => 'tagcloud.swf',
			
			'usejQuery' => false,
			
        ),$config);

        $this->modx->addPackage('cumulus',$this->config['model_path']);        
		// if ($this->modx->lexicon) {
            // $this->modx->lexicon->load('cumulus:default');
        // }

        /* load debugging settings */
        if ($this->modx->getOption('debug',$this->config,false)) {
            error_reporting(E_ALL); ini_set('display_errors',true);
            $this->modx->setLogTarget('HTML');
            $this->modx->setLogLevel(MODX_LOG_LEVEL_ERROR);
			
            $debugUser = $this->config['debugUser'] == '' ? $this->modx->user->get('username') : 'anonymous';
            $user = $this->modx->getObject('modUser',array('username' => $debugUser));
            if ($user == null) {
                $this->modx->user->set('id',$this->modx->getOption('debugUserId',$this->config,1));
                $this->modx->user->set('username',$debugUser);
            } else {
                $this->modx->user = $user;
            }
        }
    }

    /**
     * Initializes Cumulus.
     *
     * @access public
     * @return string The landing div for the cloud
     */
    public function generate() {
        $output = '';
		
		$parents = explode(',',$this->config['parents']);
		$subEntities = $parents;
		foreach ($parents as $parentDoc) {
			$subDocs = $this->modx->getChildIds($parentDoc, $this->config['depth']);
			$subEntities = array_merge($subDocs,$subEntities);
		}

		$docTags = $this->_getTags( array_unique($subEntities), $this->config['tvTags'], $this->config['days'] );
		// docTags now contains an array indexed by docId, with each value containing an array of tags

		// go through each document, split the works into an array, and add them to a
		// master array with the word as the key, and a value containing an array consisting of count
		$tags  = array();
		$tag_count = 0;
		
		foreach ($docTags as $n => $v) {
			if (is_array($v)){	
				// We should have an array containing only one key (tags)
				$this_tags = explode($this->config['tagDelimiter'],$v['datas']);	
				// Split it by the tag delimiter
				foreach ($this_tags as $tag) {	
					// Each of the new found tags
					$tag = trim($tag);	
					// Remove any whitespace
					$tag = ($caseSensitive) ? $tag : strtolower($tag);	
					// If not case sensitive, lower-case it
					if ($tag != '') {	
						// if its not empty after all this
						$tag_count++;
						if (isset($tags[$tag]['count'])) {	
							// if we've already met this tag, increment its counter
							$tags[$tag]['count']++;	
						} else {	
							// Otherwise create a counter for it
							$tags[$tag]['count'] = 1;
						}
					}
				}
			}
		}
		
		arsort($tags);			
		$maxval = 0;	
		$tagHTMLString = '';		
		
		foreach ($tags as $tag => $data) {
			if ($maxval==0){
				$maxval=$data['count'];
			}
			$minval = $data['count'];
			// if this tag has less than the minimum required count, remove it
			if ($this->config['min'] > 0 && $data['count'] < $this->config['min']) {
				unset($tags[$tag]);
			}			
		}
		
		// How many tags should we display?
		$tags_still_to_show = ($this->config['limit'] == 0 )? count($tags) : $this->config['limit'];
		
		$fact = ($this->config['maxsize'] - $this->config['minsize'])/($maxval - $minval);
		
		foreach ($tags as $tag => $value) {
			if ($tags_still_to_show == 0)
				break;
			$fontSize = $this->config['minsize'] + ($value['count'] - $minval) * $fact;
			$tagSize = 'font-size:'.$fontSize.'pt;';
			$tagString .= '<a href="'.$this->modx->makeUrl($this->config['landing'],'','tag='.$tag,'full').'" style="'.$tagSize.'" rel="nofollow tag" title="'.$tag.'">'.$tag.(($this->config['showCount']==1)?' ('.$value['count'].')</a>':'</a>');
			$tags_still_to_show--;
		}
		
		if($this->config['usejQuery'] == true){
			$this->_jqueryFlashLoader($tagString);		
		}else{
			$this->_swfObjectLoader($tagString);		
		}
		
		/* @TODO : Verify if the landing div id */
		// if(empty($this->config['cumulusDiv'])){
			
		// }else{
			return '<div id="cumulus"></div>';
		// }
    }
	
    /**
     * Get the list of tags found.
     *
     * @access private
     * @param array $cIDs List of resources to search tags for.
     * @param string $tvTags Name of the TV.
     * @param int $days The number of days since the publich date.
     * @return array The tags found.
     */	
	private function _getTags($cIDs, $tvTags, $days) {
		
		if ($days > 0) {
			$pub_date = mktime() - $days*24*60*60;
		} else {
			$pub_date = 0;
		}
		
		$t = $this->modx->newQuery('modTemplateVar');
		$t->setClassAlias('tv');
		$t->leftJoin('modTemplateVarResource','tvValues','tv.id = tvValues.tmplvarid');
		$t->leftJoin('modResource','content','tvValues.contentid = content.id');
		$t->sortby('tvValues.contentid','ASC');
		$t->where(array(
			'tv.name' => $tvTags,
			"tvValues.contentid IN (" . implode($cIDs,",") . ")",
			'content.pub_date:>=' => $pub_date,
			'content.published' => 1,
		));		
		$t->select('tvValues.id, tvValues.contentid, tvValues.value');
		$tags = $this->modx->getCollection('modTemplateVar',$t);
		
		$docTags = array();
		foreach ($tags as $tag) {
			$data['id'] = $tag->get('contentid');
			$data['datas'] = $tag->get('value');
			$docTags[] = $data;
		}
		
		return $docTags;
	}
	
    /**
     * Load jquery flash to show the tags.
     *
     * @access private
     * @param string $tags Processed string of tags.
     * @return void.
     */		
	private function _jqueryFlashLoader($tags)
	{
		$js = '
		<script type="text/javascript">	
			var rnumber = Math.floor(Math.random()*9999999);		
			Cumulus = $.flash.create(   
				{   swf: "'. $this->config['flash_url'].$this->config['cumulus_movie'] .'?"+rnumber,   
					id: "cumulus",
					width: '. $this->config['width'] .',
					height: '. $this->config['height'] .',
					wmode: "'. $this->config['wmode'] .'",
					allowScriptAccess: "always",
					bgcolor: "#'. $this->config['bgcolor'] .'",
					encodeParams: false,
					flashvars: {   						
						tcolor: "0x'. $this->config['tcolor'] .'",
						tcolor2: "0x'. $this->config['tcolor2'] .'",
						hicolor: "0x'. $this->config['hicolor'] .'",
						mode: "tags", 
						distr: '. $this->config['distr'] .', 
						tspeed: '. $this->config['speed'] .',
						// tagcloud: "'. urlencode('<tags>') . urlencode($tags) .urlencode('</tags>') .'"  
						tagcloud: "'. htmlspecialchars('<tags>') . htmlspecialchars($tags) . htmlspecialchars('</tags>') .'"  
					}
				}  
			);
			$(document).ready(function(){
				$("#'.$this->config['container'].'").html(Cumulus);
			});
		</script>';
		
		$this->modx->regClientStartupScript($this->config['js_url'].'jquery.swfobject.1-1-1.min.js');
		$this->modx->regClientStartupHTMLBlock($js);
	}

    /**
     * Load swfoject to show the tags.
     *
     * @access private
     * @param string $tags Processed string of tags.
     * @return void.
     */		
	private function _swfObjectLoader($tags)
	{
		//Setup flashvars
		$flashvars = 'var flashvars = { tcolor: "0x'. $this->config['tcolor'] .'", ';
		$flashvars .= 'tcolor2: "0x'. $this->config['tcolor2'] .'", ';
		$flashvars .= 'hicolor: "0x'. $this->config['hicolor'] .'", ';
		$flashvars .= 'tspeed: "'. $this->config['speed'] .'", ';
		$flashvars .= 'distr: '. $this->config['distr'] .', ';
		$flashvars .= 'mode: "tags", ';
		$flashvars .= 'tagcloud: "'. urlencode('<tags>') . urlencode($tags) .urlencode('</tags>') .'" ';
		$flashvars .= '};';
		
		//Setup params
		$params = 'var params = { wmode: "'. $this->config['wmode'] .'", ';
		$params .= 'bgcolor: "#'. $this->config['bgcolor'] .'", ';
		$params .= 'allowScriptAccess: "always", ';
		$params .= 'movie: "'. $this->config['flash_url'].$this->config['cumulus_movie'] .'?"+rnumber';
		$params .= ' };';
		
		//Setup swfObject
		$swfObject .=	'swfobject.embedSWF("'. $this->config['flash_url'].$this->config['cumulus_movie'] .'?r="+rnumber, "'. $this->config['container'] .'"';
		$swfObject .= ' ,"'. $this->config['width'] .'" ';
		$swfObject .= ' ,"'. $this->config['height'] .'" ';
		$swfObject .= ' ,"9.0.0" ';
		$swfObject .= ' ,{} ';		
		$swfObject .= ' ,flashvars ';
		$swfObject .= ' ,params ';
		$swfObject .= ');';
		
		//Make up javascript and load in header
		$js = '
		<script type="text/javascript">
			var rnumber = Math.floor(Math.random()*9999999);
			'. $flashvars .'
			'. $params .'
			'. $swfObject .'
		</script>';
		
		$this->modx->regClientStartupScript($this->config['js_url'].'swfobject.js');
		$this->modx->regClientStartupHTMLBlock($js);
		
	}
}
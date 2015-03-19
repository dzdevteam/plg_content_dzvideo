<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Content.joomla
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
jimport('joomla.filesystem.file');

class PlgContentDZVideo extends JPlugin
{
    function __construct(&$subject, $config = array()) {
        parent::__construct($subject, $config = array());
        
        // Add include paths for dzvideo models and tables
        JModelLegacy::addIncludePath(JPATH_SITE.'/components/com_dzvideo/models', 'DZVideoModel');
        JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_dzvideo/tables');
    }
    
	public function onContentPrepare($context, &$article, &$params, $page = 0)
	{      
        $this->_replaceVideo($article->text);
        
        return true;
	}
	
	private function _load($model, $id)
	{
        $model = JModelLegacy::getInstance($model, 'DZVideoModel');
        
        return $model->getData($id);
	}
	
	private function _replaceVideo(&$text)
	{
        // Simple check to determine whether bot should continue
        if (strpos($text, 'loadvideo') === false)
            return;
            
        // Regex for load video
        $regexvideo = '/{loadvideo.*?}/i';
        
        // Find hero matches
        preg_match_all($regexvideo, $text, $matches, PREG_SET_ORDER);
        // No matches, skip this
        if ($matches)
        {
            foreach ($matches as $match) {
                $shortcode = $match[0];
                $item = $this->_load('Video', self::_getMainAttribute($shortcode));
                
                $width = self::_getAttribute($shortcode, 'width');
                $height = self::_getAttribute($shortcode, 'height');
                
                $display_image  = JUri::root().'images/dzvideo/120x80.gif';
                $image = $item->images;
                if (isset($image['custom']) && !empty($image['custom']) && JFile::exists(JPATH_ROOT.'/'.$image['custom'])) {
                   $display_image = JUri::root().$image['custom'];
                } elseif (isset($image['medium']) && !empty($image['medium']) && JFile::exists(JPATH_ROOT.'/'.$image['medium'])) {
                    $display_image = JUri::root().$image['medium']; 
                }
                
                $output  =  '<a href="' . $item->videolink . '" class="content-video" title="' . $item->title . '" target="_blank">';
                $output .=      '<img src="' . $display_image . '" alt="' . $item->title . '" ' . ($width ? " width='$width' " : '') . ($height ? " height='$height' " : '') .'/>';
                $output .=  '</a>';
                // We should replace only first occurrence
                $text = preg_replace("|$shortcode|", addcslashes($output, '\\$'), $text, 1);
            }
        }
	}

    private static function _getAttribute($shortcode, $attrib) {
        $regex = "/" . $attrib . "\s*=\s*[\"']?([A-Za-z0-9]+)[\"']?/i";
        
        // Find attribute in the shortcode
        preg_match($regex, $shortcode, $matches);
        if ($matches) {
            return $matches[1];
        } else {
            return null;
        }
    }
    
    private static function _getMainAttribute($shortcode) {
        $regex = "/{.*\s+(\w+)}/";
        preg_match($regex, $shortcode, $matches);
        if ($matches) {
            return $matches[1];
        } else {
            return null;
        }
    }
}

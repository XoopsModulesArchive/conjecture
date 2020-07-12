<?php
// $Id: comments.php $
###############################################################################
##                    XOOPS - PHP Content Management System                  ##
##                       Copyright (c) 2000 XOOPS.org                        ##
##                          <http://www.xoops.org/>                          ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
##                                                                           ##
##  You may not change or alter any portion of this comment or credits       ##
##  of supporting developers from this source code or any supporting         ##
##  source code which is considered copyrighted (c) material of the          ##
##  original comment or credit authors.                                      ##
##                                                                           ##
##  This program is distributed in the hope that it will be useful,          ##
##  but WITHOUT ANY WARRANTY; without even the implied warranty of           ##
##  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            ##
##  GNU General Public License for more details.                             ##
##                                                                           ##
##  You should have received a copy of the GNU General Public License        ##
##  along with this program; if not, write to the Free Software              ##
##  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA ##
###############################################################################

class ConjectureCommentsHandler {
    /**
     * Array of pages for the module
     * To be set in the subclass constructor
     * on the form $this->pages = array('filename' => array('param' => 'name_of_parameter', 'handler' => 'name_of_handler'));
     * filename should be without extension if a .php file
     * 
     * If no handler is set, the subclass MUST override the getTitle() class function
     * If no param is set, or the parameter is not the integer id of the item, 
     * the subclass MUST override the getId() class function
     *
     * @var array
     * @access protected
     */
    protected $pages = array();

    public $module = false;
    public $type = '';
    public $item_id = 0;
    public $topic_id = 0;
    public $forum_id = 0;
    public $show_comments = true;
    
    function __construct() {
        $this->module = is_object($GLOBALS['xoopsModule']) ? $GLOBALS['xoopsModule'] : false;
    }
    
    /**
     * Get comments for the current page
     *
     * @return array|false
     */
    public function &getComments() {
        $post_handler =& xoops_getmodulehandler('post', 'newbb', true);
        if (!$post_handler) {
            return false;
        }
        $ret = array();
        
        $this->getCurrentPage();
        if (!$this->show_comments) {
            return false;
        }
        $this->getTopicId();
        if ($this->topic_id > 0) {
            $criteria = new CriteriaCompo(new Criteria('p.topic_id', $this->topic_id));
            $criteria->add(new Criteria('p.approved', 1));
            $criteria->setSort('post_time');
            $criteria->setOrder('ASC');
            $ret =& $post_handler->getPostsByLimit($criteria, 0);
        }
        return $ret;
    }
    
    /**
     * Get topic ID of current item
     *
     */
    protected function getTopicId() {
        $commenttopic_handler =& xoops_getmodulehandler('commenttopic', 'conjecture');
        $this->topic_id = $commenttopic_handler->getTopicId($this->type, $this->module->getVar('mid'), $this->item_id);
    }
    
    /**
     * Analyse script name for current page type, id and module id
     *
     */
    protected function getCurrentPage() {
        // Get path before query string to ensure that forward slashes in parameters don't mess things up
        $path=explode("?",$_SERVER['SCRIPT_NAME']);
        $this->type = basename($path[0], ".php");
        // Get item id
        $this->getId();
        if (!$this->show_comments) {
            // Don't show comments, so no point in looking up the forum id
            return;
        }
        // Get forum id
        $this->getForumId();
    }
    
    /**
     * Find current ID
     *
     */
    protected function getId() {
        if (isset($_REQUEST[$this->pages[$this->type]['param']])) {
            $this->item_id = intval($_REQUEST[$this->pages[$this->type]['param']]);
        }
        else {
            $this->show_comments = false;
        }
    }
    
    /**
     * Look up forum id for current module and type
     * 
     * @todo implement categories - currently hardcoded to zero
     *
     */
    protected function getForumId() {
        $modcat = xoops_getmodulehandler('modcatforum', 'conjecture')->get(array($this->module->getVar('mid'), 0));
        $this->forum_id = $modcat->getVar('forum_id');
        if (!$this->forum_id) {
            $this->show_comments = false;
        }
    }
    
    /**
     * Return title of item - to be overridden in subclasses without handlers
     *
     * @return string
     */
    public function getTitle() {
        if (!isset($this->pages[$this->type]['handler'])) {
            return '';
        }
        $handler =& xoops_getmodulehandler($this->pages[$this->type]['handler'], $this->module->getVar('dirname'));
        $item = $handler->get($this->item_id);
        return $item->getVar($handler->identifierName, 'e');
    }
}
?>
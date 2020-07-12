<?php
// $Id: modcatforum.php $
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

if (!defined("XOOPS_ROOT_PATH")) {
    die("Cannot access file directly");
}
/**
 * @package Conjecture
 */

/**
 * Make sure object handler is included
 */
if (!class_exists("XoopsPersistableObjectHandler")) {
	include_once(XOOPS_ROOT_PATH."/modules/conjecture/class/object.php");
}

/**
 * @package Conjecture
 */
class ModuleCommentForum extends XoopsObject {
    function __construct() {
        $this->initVar('module_id', XOBJ_DTYPE_INT);
        $this->initVar('category_id', XOBJ_DTYPE_INT);
        $this->initVar('forum_id', XOBJ_DTYPE_INT);
    }
}

/**
 * @package Conjecture
 */
class ConjectureModcatforumHandler extends XoopsPersistableObjectHandler {
    function __construct($db) {
        parent::__construct($db, 'conjec_modcatforum', 'ModuleCommentForum', array('module_id', 'category_id') );
    }
}
?>
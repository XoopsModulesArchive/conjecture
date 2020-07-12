<?php
// $Id: modules.php $
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

include_once("../../../include/cp_header.php");
xoops_cp_header();

if (isset($_REQUEST['set'])) {
    // Assign forum ids to module categories
    foreach ($_REQUEST['modules'] as $mid => $cats) {
        foreach ($cats as $catid => $forum_id) {
            $modcatforum = $handler->get(array(intval($mid), intval($catid)));
            $modcatforum->setVar('module_id', $mid);
            $modcatforum->setVar('category_id', $catid);
            $modcatforum->setVar('forum_id', $forum_id);
            $handler->insert($modcatforum);
        }
    }
}
// Get installed modules and available comment handlers
$modules = xoops_gethandler('module')->getObjects(new Criteria('isactive', 1));
$handler_list = getHandlerList();
// Combine installed modules with available comment handlers
foreach (array_keys($modules) as $i) {
    if (in_array($modules[$i]->getVar('dirname'), $handler_list)) {
        $installed_handlers[] = $modules[$i];
    }
}

$handler =& xoops_getmodulehandler('modcatforum', 'conjecture');
$modcatforums = $handler->getObjects();
foreach ($modcatforums as $modcatforum) {
    $forum_ids[$modcatforum->getVar('module_id')][$modcatforum->getVar('category_id')] = $modcatforum->getVar('forum_id');
}

// Get category and forum listing
$categories = xoops_getmodulehandler('category', 'newbb')->getAllCats();
$forums = xoops_getmodulehandler('forum', 'newbb')->getForums();

foreach (array_keys($forums) as $i) {
    $forum_cats[$forums[$i]->getVar('cat_id')][] =& $forums[$i];
}

// Prepare form
include_once(XOOPS_ROOT_PATH."/class/xoopsformloader.php");
$form = new XoopsThemeForm(_CONJEC_AM_ASSIGNFORUMS, 'modforumform', 'modules.php');

foreach ($installed_handlers as $module) {
    $catid = 0;
    $form->addElement(new XoopsFormLabel($module->getVar('name'), "<select name=\"modules[".$module->getVar('mid')."][".$catid."]\">".getOptions($categories, $forum_cats, $forum_ids[$module->getVar('mid')][$catid])."</select>"));
}

$form->addElement(new XoopsFormButton('', 'set', _SUBMIT, 'submit'));
$form->display();



xoops_cp_footer();
    
function getHandlerList() {
    $non_handlers = array('CVS', 'comments', 'commenttopic', 'object', 'modcatforum');
    $handlers = array();
    $dir = new DirectoryIterator( XOOPS_ROOT_PATH."/modules/conjecture/class/" );
    foreach ( $dir as $file ) {
        $filename = basename($file->getFilename(), ".php");

        if ( !in_array($filename, $non_handlers ) ) {
            $handlers[] = $filename;
        }
    }
    return $handlers;
}

/**
 * Get forum options to a select element ordered by category
 *
 * @param array $categories
 * @param array $forum_cats
 * 
 * @return string
 */
function getOptions($categories, &$forum_cats, $selected = 0) {
    $element = "<option label=\"--\" value=\"0\">"._CONJEC_AM_DISABLE."</option>";
    foreach (array_keys($categories) as $i) {
        $element .= "<optgroup label=\"".$categories[$i]->getVar('cat_title')."\">\n";
        foreach ($forum_cats[$categories[$i]->getVar('cat_id')] as $forum) {
            $element .= "<option label=\"".$forum->getVar('forum_name')."\" value=\"".$forum->getVar('forum_id')."\"";
            if ($selected == $forum->getVar('forum_id')) {
                $element.= " selected=\"selected\"";
            }
            $element .= "> ".$forum->getVar('forum_name')."</option>";
        }
        $element .= "</optgroup>\n";
    }
    return $element;
}
?>
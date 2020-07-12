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

function b_comments_show() {
    global $xoopsModule;
    if (!is_object($xoopsModule)) {
        return false;
    }
    $handler = xoops_getmodulehandler(strtolower($xoopsModule->getVar('dirname')), 'conjecture', true);
    if (!is_object($handler)) {
        // Comments handler for this module doesn't exist
        return false;
    }
    $comments =& $handler->getComments();
//    var_dump(array_keys($ret['comments'][$last_id]->vars));
    if (!$handler->show_comments) {
        return false;
    }

    if (count($comments) > 0) {
        $keys = array_keys($comments);
        $parent_id = $keys[count($keys)-1];
        foreach (array_keys($comments) as $i) {
            $comment['id'] = $comments[$i]->getVar('post_id');
            $comment['text'] = $comments[$i]->getVar('post_text');
            $comment['date_posted'] = formatTimestamp($comments[$i]->getVar('post_time'), 'm');
            $comment['uid'] = $comments[$i]->getVar('uid');
            
            $uids[] = $comments[$i]->getVar('uid');
            
            $ret['comments'][$i] = $comment;
        }
        $member_handler =& xoops_gethandler('member');
        $ret['users'] = $member_handler->getUsers(new Criteria('uid', "(".implode(',', $uids).")", "IN"), true);
    }
    else {
        $parent_id = 0;
    }

    $topic_id = $handler->topic_id;
    $forum_id = $handler->forum_id;
    
    
    if (@!include_once(XOOPS_ROOT_PATH."/modules/newbb/language/".$GLOBALS['xoopsConfig']['language']."/main.php")) {
        include_once(XOOPS_ROOT_PATH."/modules/newbb/language/english/main.php");
    }
    
    include_once XOOPS_ROOT_PATH."/class/xoopsformloader.php";

	$forum_form = new XoopsThemeForm(_MD_POSTREPLY, 'quick_reply', XOOPS_URL."/modules/conjecture/post.php", 'post', true);

	if(!is_object($GLOBALS['xoopsUser'])){
		$user_tray = new XoopsFormElementTray(_MD_ACCOUNT);
		$user_tray->addElement(new XoopsFormText(_MD_NAME, "uname", 26, 255));
		$user_tray->addElement(new XoopsFormPassword(_MD_PASSWORD, "pass", 10, 32));
		$login_checkbox = new XoopsFormCheckBox('', 'login', 1);
		$login_checkbox->addOption(1, _MD_LOGIN);
		$user_tray->addElement($login_checkbox);
		$forum_form->addElement($user_tray, '');
	}

	$editor_object = new XoopsFormDhtmlTextArea('', 'message', '', 10, 55);
	$forum_form->addElement($editor_object, true);

	$forum_form->addElement(new XoopsFormHidden('dohtml', 0));
	$forum_form->addElement(new XoopsFormHidden('dosmiley', 1));
	$forum_form->addElement(new XoopsFormHidden('doxcode', 1));
	$forum_form->addElement(new XoopsFormHidden('dobr', 1));
	$forum_form->addElement(new XoopsFormHidden('attachsig', 1));

	$forum_form->addElement(new XoopsFormHidden('isreply', 1));
	
	$forum_form->addElement(new XoopsFormHidden('subject', $handler->getTitle()));
	$forum_form->addElement(new XoopsFormHidden('pid', $parent_id));
	$forum_form->addElement(new XoopsFormHidden('topic_id', $topic_id));
	$forum_form->addElement(new XoopsFormHidden('forum', $forum_id));
	$forum_form->addElement(new XoopsFormHidden('return_url', addslashes($_SERVER['REQUEST_URI'])));
	$forum_form->addElement(new XoopsFormHidden('type', $handler->type));
	$forum_form->addElement(new XoopsFormHidden('module_id', $handler->module->getVar('mid')));
	$forum_form->addElement(new XoopsFormHidden('item_id', $handler->item_id));

	$forum_form->addElement(new XoopsFormHidden('notify', -1));
	$forum_form->addElement(new XoopsFormHidden('contents_submit', 1));
	$submit_button = new XoopsFormButton('', 'quick_submit', _SUBMIT, "submit");
	$submit_button->setExtra('onclick="if(document.forms.quick_reply.message.value == \'RE\' || document.forms.quick_reply.message.value == \'\'){ alert(\''._MD_QUICKREPLY_EMPTY.'\'); return false;}else{ return true;}"');
	$button_tray = new XoopsFormElementTray('');
	$button_tray->addElement($submit_button);
	$forum_form->addElement($button_tray);

    $ret['form'] = $forum_form->render();
	
    unset($forum_form);
	
    return $ret;
}
?>
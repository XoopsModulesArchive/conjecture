<?php
// $Id: post.php,v 1.3 2005/10/19 17:20:28 phppp Exp $
//  ------------------------------------------------------------------------ //
//                XOOPS - PHP Content Management System                      //
//                    Copyright (c) 2000 XOOPS.org                           //
//                       <http://www.xoops.org/>                             //
//  ------------------------------------------------------------------------ //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //
//  Author: phppp (D.J., infomax@gmail.com)                                  //
//  URL: http://xoopsforge.com, http://xoops.org.cn                          //
//  Project: Article Project                                                 //
//  ------------------------------------------------------------------------ //

include_once '../../mainfile.php';
include_once XOOPS_ROOT_PATH."/modules/newbb/include/vars.php";
include_once XOOPS_ROOT_PATH."/modules/newbb/include/functions.php";
include_once XOOPS_ROOT_PATH."/Frameworks/art/functions.php";
include_once XOOPS_ROOT_PATH . '/modules/newbb/class/uploader.php';

if (@!include_once(XOOPS_ROOT_PATH."/modules/newbb/language/".$GLOBALS['xoopsConfig']['language']."/main.php")) {
    include_once(XOOPS_ROOT_PATH."/modules/newbb/language/english/main.php");
}

foreach (array(
'forum',
'topic_id',
'post_id',
'order',
'pid',
'start',
'isreply',
'isedit'
) as $getint) {
    ${$getint} = isset($_POST[$getint]) ? intval($_POST[$getint]) : 0 ;
}
$op = isset($_POST['op']) ? $_POST['op'] : '';
if ( empty($forum) ) {
    redirect_header(XOOPS_URL.$_POST['return_url'], 2, _MD_ERRORFORUM);
    exit();
}

$forum_handler =& xoops_getmodulehandler('forum', 'newbb');
$topic_handler =& xoops_getmodulehandler('topic', 'newbb');
$post_handler =& xoops_getmodulehandler('post', 'newbb');

if ( !empty($isedit) && $post_id>0 ) {
    $forumpost =& $post_handler->get($post_id);
    $topic_id = $forumpost->getVar("topic_id");
}else{
    $forumpost =& $post_handler->create();
}
$topic =& $topic_handler->get($topic_id);
$forum_id = ($topic_id)?$topic->getVar("forum_id"):$forum;
$forum_obj =& $forum_handler->get($forum_id);
if (!$forum_handler->getPermission($forum_obj)){
    redirect_header(XOOPS_URL.$_POST['return_url'], 2, _MD_NORIGHTTOACCESS);
    exit();
}

if ( !empty($_POST['contents_submit']) ) {
    $token_valid = $GLOBALS['xoopsSecurity']->check();

    if(!is_object($xoopsUser)){
        $uname = !isset($_POST['uname']) ? '' : trim($_POST['uname']);
        $pass = !isset($_POST['pass']) ? '' : trim($_POST['pass']);
        $member_handler =& xoops_gethandler('member');
        $user =& $member_handler->loginUser(addslashes($myts->stripSlashesGPC($uname)), addslashes($myts->stripSlashesGPC($pass)));
        if(is_object($user) && 0 < $user->getVar('level')){
            if(!empty($_POST["login"])){
                $user->setVar('last_login', time());
                if (!$member_handler->insertUser($user)) {
                }
                $_SESSION = array();
                $_SESSION['xoopsUserId'] = $user->getVar('uid');
                $_SESSION['xoopsUserGroups'] = $user->getGroups();
                if ($xoopsConfig['use_mysession'] && $xoopsConfig['session_name'] != '') {
                    setcookie($xoopsConfig['session_name'], session_id(), time()+(60 * $xoopsConfig['session_expire']), '/',  '', 0);
                }
                $user_theme = $user->getVar('theme');
                if (in_array($user_theme, $xoopsConfig['theme_set_allowed'])) {
                    $_SESSION['xoopsUserTheme'] = $user_theme;
                }
            }
            $xoopsUser =& $user;
        }
    }

    $isadmin = newbb_isAdmin($forum_obj);

    $time_valid = true;
    if( !$isadmin && !empty($xoopsModuleConfig['post_timelimit']) ){
        $last_post = newbb_getsession('LP'); // using session might be more secure ...
        if(time()-$last_post < $xoopsModuleConfig['post_timelimit']){
            $time_valid = false;
        }
    }

    if(!$token_valid || !$time_valid){
        $_POST['contents_preview'] = 1;
        $_POST['contents_submit'] = null;
        $_POST['contents_upload'] = null;
        if(!$token_valid) echo "<div class=\"errorMsg\">"._MD_INVALID_SUBMIT."</div>";
        if(!$time_valid) echo "<div class=\"errorMsg\">".sprintf(_MD_POSTING_LIMITED,$xoopsModuleConfig['post_timelimit'])."</div>";
        echo "<br clear=\"both\" />";
    }
}

if ( !empty($_POST['contents_submit']) ) {
    $message =  $_POST['message'];
    if(empty($message)){
        redirect_header(XOOPS_URL.$_POST['return_url'], 1);
        exit();
    }
    if ( !empty($isedit) && $post_id>0) {

        $uid = is_object($xoopsUser)? $xoopsUser->getVar('uid'):0;

        $topic_status = $topic_handler->get($topic_id,'topic_status');
        if ( $topic_handler->getPermission($forum_obj, $topic_status, 'edit')
        && ( $isadmin || ( $forumpost->checkTimelimit('edit_timelimit') && $forumpost->checkIdentity() ))
        ) {}
        else{
            redirect_header(XOOPS_URL.$_POST['return_url'],2,_MD_NORIGHTTOEDIT);
            exit();
        }

        $delete_attach = isset($_POST['delete_attach']) ? $_POST['delete_attach'] : array();
        if (is_array($delete_attach) && count($delete_attach)>0) $forumpost->deleteAttachment($delete_attach);
    }
    else {
        if($topic_id){
            $topic_status = $topic_handler->get($topic_id,'topic_status');
            if (!$topic_handler->getPermission($forum_obj, $topic_status, 'reply')) {
                redirect_header(XOOPS_URL.$_POST['return_url'],2,_MD_NORIGHTTOREPLY);
                exit();
            }
        }else{
            $topic_status = 0;
            if (!$topic_handler->getPermission($forum_obj, $topic_status, 'post')) {
                redirect_header(XOOPS_URL.$_POST['return_url'],2,_MD_NORIGHTTOPOST);
                exit();
            }
        }

        $isreply = 0;
        $isnew = 1;
        if ( !is_object($xoopsUser) || ( !empty($_POST['noname']) && !empty($xoopsModuleConfig['allow_user_anonymous']) ) ) {
            $uid = 0;
        }
        else {
            $uid = $xoopsUser->getVar("uid");
        }
        if (isset($pid) && $pid != "") {
            $forumpost->setVar('pid', $pid);
        }
        if (!empty($topic_id)) {
            $forumpost->setVar('topic_id', $topic_id);
            $isreply = 1;
        }
        $forumpost->setVar('poster_ip', newbb_getIP());
        $forumpost->setVar('uid', $uid);
        $forumpost->setVar('post_time', time());
    }

    if($topic_handler->getPermission($forum_obj, $topic_status, 'noapprove')) $approved = 1;
    else $approved = 0;
    $forumpost->setVar('approved', $approved);

    $forumpost->setVar('forum_id', $forum_obj->getVar('forum_id'));

    $subject = xoops_trim($_POST['subject']);
    $subject = ($subject == '') ? _NOTITLE : $subject;
    $poster_name = !empty($_POST['poster_name'])?xoops_trim($_POST['poster_name']):'';
    $dohtml = !empty($_POST['dohtml']) ? 1 : 0;
    $dosmiley = !empty($_POST['dosmiley']) ? 1 : 0;
    $doxcode = !empty($_POST['doxcode']) ? 1 : 0;
    $dobr = !empty($_POST['dobr']) ? 1 : 0;
    $icon = (!empty($_POST['icon']) && is_file(XOOPS_ROOT_PATH . "/images/subject/" . $_POST['icon']) ) ? $_POST['icon'] : '';
    $attachsig = !empty($_POST['attachsig']) ? 1 : 0;
    $view_require = !empty($_POST['view_require']) ? $_POST['view_require'] : '';
    $post_karma = (($view_require == 'require_karma')&&isset($_POST['post_karma']))?intval($_POST['post_karma']):0;
    $require_reply = ($view_require == 'require_reply')?1:0;
    $forumpost->setVar('subject', $subject);

    // The text filter is far from complete
    // Let's look for some comprehensive handlers
    if($dohtml && !newbb_isAdmin($forum_obj) ) {
        //$message=newbb_textFilter($message);
    }
    $forumpost->setVar('post_text', $message);
    $forumpost->setVar('post_karma', $post_karma);
    $forumpost->setVar('require_reply', $require_reply);
    $forumpost->setVar('poster_name', $poster_name);
    $forumpost->setVar('dohtml', $dohtml);
    $forumpost->setVar('dosmiley', $dosmiley);
    $forumpost->setVar('doxcode', $doxcode);
    $forumpost->setVar('dobr', $dobr);
    $forumpost->setVar('icon', $icon);
    $forumpost->setVar('attachsig', $attachsig);
    $forumpost->setAttachment();
    if ( !empty($post_id) ) $forumpost->setPostEdit($poster_name); // is reply

    $postid = $post_handler->insert($forumpost);
    if (!$postid ) {
        include_once(XOOPS_ROOT_PATH.'/header.php');
        xoops_error('Could not insert forum post');
        xoops_error($forumpost->getErrors());
        include_once(XOOPS_ROOT_PATH.'/footer.php');
        exit();
    }
    
    // CONJECTURE - IF POST IS NEW TOPIC, CONNECT TOPIC TO ITEM
    if ($forumpost->isTopic() ) {
        $commenttopic_handler =& xoops_getmodulehandler('commenttopic', 'conjecture');
        $commenttopic =& $commenttopic_handler->create();
        $commenttopic->setVar('type', $_POST['type']);
        $commenttopic->setVar('module_id', $_POST['module_id']);
        $commenttopic->setVar('item_id', $_POST['item_id']);
        $commenttopic->setVar('topic_id', $forumpost->getVar('topic_id'));
        $commenttopic_handler->insert($commenttopic);
    }
    // CONJECTURE - IF POST IS NEW TOPIC, CONNECT TOPIC TO ITEM
    
    newbb_setsession("LP", time()); // Recording last post time


    if(newbb_checkSubjectPrefixPermission($forum_obj) && !empty($_POST['subject_pre'])){
        $subject_pre = intval($_POST['subject_pre']);
        $sbj_res = $post_handler->insertnewsubject($forumpost->getVar('topic_id'), $subject_pre);
    }

    // RMV-NOTIFY
    // Define tags for notification message
    if($approved && !empty($xoopsModuleConfig['notification_enabled']) && !empty($isnew)){
        $tags = array();
        $tags['THREAD_NAME'] = $_POST['subject'];
        $tags['THREAD_URL'] = XOOPS_URL . '/modules/' . $xoopsModule->dirname() . '/viewtopic.php?post_id='.$postid.'&amp;topic_id=' . $forumpost->getVar('topic_id').'&amp;forum=' . $forumpost->getVar('forum_id');
        $tags['POST_URL'] = $tags['THREAD_URL'] . '#forumpost' . $postid;
        include_once 'include/notification.inc.php';
        $forum_info = newbb_notify_iteminfo ('forum', $forum_obj->getVar('forum_id'));
        $tags['FORUM_NAME'] = $forum_info['name'];
        $tags['FORUM_URL'] = $forum_info['url'];
        $notification_handler =& xoops_gethandler('notification');
        if (empty($isreply)) {
            // Notify of new thread
            $notification_handler->triggerEvent('forum', $forum_obj->getVar('forum_id'), 'new_thread', $tags);
        } else {
            // Notify of new post
            $notification_handler->triggerEvent('thread', $topic_id, 'new_post', $tags);
        }
        $notification_handler->triggerEvent('global', 0, 'new_post', $tags);
        $notification_handler->triggerEvent('forum', $forum_obj->getVar('forum_id'), 'new_post', $tags);
        $myts =& MyTextSanitizer::getInstance();
        $tags['POST_CONTENT'] = $myts->stripSlashesGPC($_POST['message']);
        $tags['POST_NAME'] = $myts->stripSlashesGPC($_POST['subject']);
        $notification_handler->triggerEvent('global', 0, 'new_fullpost', $tags);
        $notification_handler->triggerEvent('forum', $forum_obj->getVar('forum_id'), 'new_fullpost', $tags);
    }

    if($approved){
        $message = _MD_THANKSSUBMIT."<br />";
    }else{
        $message = _MD_THANKSSUBMIT."<br />"._MD_WAITFORAPPROVAL."<br />";
    }
    redirect_header(XOOPS_URL.$_POST['return_url'], 3, $message);
}

redirect_header(XOOPS_URL.$_POST['return_url'], 3);

?>
<?php

class Blog_Admin {

  function main(){
    $previous_version = $title = $message = $content = NULL;
    $panel = & Blog_Admin::cpanel();
    PHPWS_Core::initModClass("version", "Version.php");

    $blog = & new Blog;

    if (isset($_REQUEST['blog_id'])){
      if (Current_User::isRestricted("blog")){
	$unapproved = Version::getUnapproved("blog_entries", $_REQUEST['blog_id']);
	if (!empty($unapproved)){
	  PHPWS_DB::loadObject($blog, $unapproved);
	  $previous_version = TRUE;
	} else {
	  $blog->id = (int)$_REQUEST['blog_id'];
	  $blog->init();
	}
      } else {
	$blog->id = (int)$_REQUEST['blog_id'];
	$blog->init();
      }
    }

    if (isset($_REQUEST['command']))
      $command = $_REQUEST['command'];
    else
      $command = $panel->getCurrentTab();

    switch ($command){
    case "edit":
      $panel->setCurrentTab("list");;
      $title = _("Update Blog Entry");
      if ($previous_version)
	$message = _("This version has not been approved.");
      $content = Blog_Admin::edit($blog);
      break;

    case "new":
      $title = _("New Blog Entry");
      $content = Blog_Admin::edit($blog);
      break;

    case "delete":
      $title = _("Blog Archive");
      $message = _("Blog entry Deleted");
      $blog->kill();
      $content = Blog_Admin::entry_list();
      break;

    case "list":
      $title = _("Blog Archive");
      $content = Blog_Admin::entry_list();
      break;

    case "restore":
      $title = _("Blog Restore") . " : " . $blog->getTitle();
      $content = Blog_Admin::restoreVersion($blog);
      break;

    case "postEntry":
      $panel->setCurrentTab("list");;
      Blog_Admin::postEntry($blog);
      
      if (Current_User::isRestricted("blog"))
	$message = _("Blog entry submitted for approval");
      else
	$message = _("Blog entry updated.");	
		
      $result = $blog->save();
      PHPWS_User::savePermissions("blog", $blog->getId());
      $title = _("Blog Archive");
      $content = Blog_Admin::entry_list();
      break;
    }

    $template['TITLE']   = $title;
    $template['MESSAGE'] = $message;
    $template['CONTENT'] = $content;
    $final = PHPWS_Template::process($template, "blog", "main.tpl");

    $panel->setContent($final);
    $finalPanel = $panel->display();
    Layout::add(PHPWS_ControlPanel::display($finalPanel));

  }

  function &cpanel(){
    PHPWS_Core::initModClass("controlpanel", "Panel.php");
    $newLink = "index.php?module=blog&amp;action=admin";
    $newCommand = array ("title"=>_("New"), "link"=> $newLink);
	
    $listLink = "index.php?module=blog&amp;action=admin";
    $listCommand = array ("title"=>_("Archive"), "link"=> $listLink);

    $tabs['new'] = $newCommand;
    $tabs['list'] = $listCommand;

    $panel = & new PHPWS_Panel("categories");
    $panel->quickSetTabs($tabs);

    $panel->setModule("blog");
    $panel->setPanel("panel.tpl");
    return $panel;
  }

  function edit(&$blog){
    PHPWS_Core::initCoreClass("Editor.php");
    $form = & new PHPWS_Form;
    $form->addHidden("module", "blog");
    $form->addHidden("action", "admin");
    $form->addHidden("command", "postEntry");

    if (isset($blog->id)){
      $form->addHidden("blog_id", $blog->id);
      $submit = _("Update Entry");
    } else
      $submit = _("Add Entry");

    if (Editor::willWork()){
      $editor = & new Editor("htmlarea", "entry", PHPWS_Text::parseOutput($blog->getEntry(), FALSE, FALSE));
      $entry = $editor->get();
      $form->addTplTag("ENTRY", $entry);
      $form->addTplTag("ENTRY_LABEL", PHPWS_Form::makeLabel("entry",_("Entry")));
    } else {
      $form->addTextArea("entry", PHPWS_Text::parseOutput($blog->getEntry(), FALSE, FALSE));
      $form->setRows("entry", "10");
      $form->setWidth("entry", "80%");
      $form->setLabel("entry", _("Entry"));
    }

    $form->addText("title", $blog->title);
    $form->setSize("title", 40);
    $form->setLabel("title", _("Title"));

    $form->addSubmit("submit", $submit);

    $template = $form->getTemplate();
    $assign = PHPWS_User::assignPermissions("blog", $blog->getId());
    $template = array_merge($assign, $template);

    return PHPWS_Template::process($template, "blog", "edit.tpl");
  }

  function getListAction(&$blog){
    $link['action'] = "admin";
    $link['blog_id'] = $blog->getId();

    if (Current_User::allow("blog", "edit_blog", $blog->getId())){
      $link['command'] = "edit";
      $list[] = PHPWS_Text::secureLink(_("Edit"), "blog", $link);
    }
    
    if (Current_User::allow("blog", "delete_blog")){
      $link['command'] = "delete";
      $list[] = PHPWS_Text::secureLink(_("Delete"), "blog", $link);
    }

    if (Current_User::isUnrestricted("blog")){
      $link['command'] = "restore";
      $list[] = PHPWS_Text::secureLink(_("Restore"), "blog", $link);
    }

    if (isset($list))
      return implode(" | ", $list);
    else
      return _("No action");
  }

  function getListEntry(&$blog){
    return substr(strip_tags(PHPWS_Text::parseOutput($blog->entry)), 0, 30) . " . . .";
  }

  function entry_list(){
    PHPWS_Core::initCoreClass("DBPager.php");
    $pager = & new DBPager("blog_entries", "Blog");
    $pager->setModule("blog");
    $pager->setTemplate("list.tpl");
    $pager->setLink("index.php?module=blog&amp;action=admin&amp;authkey=" . Current_User::getAuthKey());
    $pager->addToggle("class=\"toggle1\"");
    $pager->addToggle("class=\"toggle2\"");
    $pager->setMethod("date", "getFormatedDate");
    $pager->addRowTag("entry", "Blog_Admin", "getListEntry");
    $pager->addRowTag("action", "Blog_Admin", "getListAction");
    $content = $pager->get();
    if (empty($content))
      return _("No entries made.");
    else
      return $content;
  }

  function postEntry(&$blog){
    $blog->title = PHPWS_Text::parseInput($_POST['title']);
    $blog->entry = PHPWS_Text::parseInput($_POST['entry']);
    return TRUE;
  }

  /*
  function restoreVersion(&$blog){
    PHPWS_Core::initCoreClass("Version.php");

    $result = Version::get($blog->id, "blog_entries", "blog");

    $tpl = & new PHPWS_Template("blog");
    $tpl->setFile("version.tpl");

    $tpl->setCurrentBlock("repeat_row");

    $count = 0;

    $vars['action'] = "admin";
    $vars['command'] = "restorePrevBlog";
    $vars['current_id'] = $blog->id;

    foreach ($result as $order=>$oldBlog){
      $count++;
      if ($count%2)
	$template['TOGGLE'] = "class=\"toggle1\"";
      else
	$template['TOGGLE'] = "class=\"toggle2\"";

      $vars['replace_order'] = $order;
      $template['BLOG'] = $oldBlog->view(FALSE);
      $template['RESTORE_LINK'] = PHPWS_Text::secureLink(_("Restore this blog"), "blog", $vars);
      $tpl->setData($template);
      $tpl->parseCurrentBlock();
    }

    $tpl->setData(array("INSTRUCTION"=>_("Choose the blog entry you want to restore.")));
    return $tpl->get();
  }
  */
}

?>
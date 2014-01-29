<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * This file contains several classes uses to render the diferent pages
 * of the wiki module
 *
 * @package mod-wikicode
 *
 * @author Jordi Piguillem
 * @author Marc Alier
 * @author David Jimenez
 * @author Josep Arus
 * @author Daniel Serrano
 * @author Kenneth Riba
 * @author Antonio J. González
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/mod/wikicode/edit_form.php');
require_once($CFG->dirroot . '/mod/wikicode/log_form.php');
require_once($CFG->dirroot . '/tag/lib.php');

/**
 * Class page_wikicode contains the common code between all pages
 *
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class page_wikicode {

    /**
     * @var object Current subwiki
     */
    protected $subwiki;

    /**
     * @var int Current page
     */
    protected $page;

    /**
     * @var string Current page title
     */
    protected $title;

    /**
     * @var int Current group ID
     */
    protected $gid;

    /**
     * @var object module context object
     */
    protected $modcontext;

    /**
     * @var int Current user ID
     */
    protected $uid;
    /**
     * @var array The tabs set used in wiki module
     */
    protected $tabs = array('view' => 'view', 'edit' => 'edit', 'history' => 'history', 'log' => 'log', 'admin' => 'admin');
    /**
     * @var array tabs options
     */
    protected $tabs_options = array();
    /**
     * @var object wiki renderer
     */
    protected $wikioutput;

    /**
     * page_wikicode constructor
     *
     * @param $wiki. Current wiki
     * @param $subwiki. Current subwiki.
     * @param $cm. Current course_module.
     */
    function __construct($wiki, $subwiki, $cm) {
        global $PAGE, $CFG;
        $this->subwiki = $subwiki;
        $this->modcontext = get_context_instance(CONTEXT_MODULE, $PAGE->cm->id);

        // initialise wiki renderer
        $this->wikioutput = $PAGE->get_renderer('mod_wikicode');
        $PAGE->set_cacheable(true);
        $PAGE->set_cm($cm);
        $PAGE->set_activity_record($wiki);
        // the search box
        $PAGE->set_button(wikicode_search_form($cm));
    }

    /**
     * This method prints the top of the page.
     */
    function print_header() {
        global $OUTPUT, $PAGE, $CFG, $USER, $SESSION;

        $PAGE->set_heading(format_string($PAGE->course->fullname));

        $this->set_url();

        if (isset($SESSION->wikipreviousurl) && is_array($SESSION->wikipreviousurl)) {
            $this->process_session_url();
        }
        $this->set_session_url();

        $this->create_navbar();
        $this->setup_tabs();

        echo $OUTPUT->header();

        echo $this->wikioutput->wikicode_info();

        // tabs are associated with pageid, so if page is empty, tabs should be disabled
        if (!empty($this->page) && !empty($this->tabs)) {
            echo $this->wikioutput->tabs($this->page, $this->tabs, $this->tabs_options);
        }
    }

    /**
     * Protected method to print current page title.
     */
    protected function print_pagetitle() {
        global $OUTPUT;
        $html = '';

        $html .= $OUTPUT->container_start();
        $html .= $OUTPUT->heading(format_string($this->title), 2, 'wikicode_headingtitle');
        $html .= $OUTPUT->container_end();
        echo $html;
    }

    /**
     * Setup page tabs, if options is empty, will set up active tab automatically
     * @param array $options, tabs options
     */
    protected function setup_tabs($options = array()) {
        global $CFG, $PAGE;
        $groupmode = groups_get_activity_groupmode($PAGE->cm);

        if (empty($CFG->usecomments) || !has_capability('mod/wikicode:viewcomment', $PAGE->context)){
            unset($this->tabs['comments']);
        }

        if (!has_capability('mod/wikicode:editpage', $PAGE->context)){
            unset($this->tabs['edit']);
        }

        if ($groupmode and $groupmode == VISIBLEGROUPS) {
            $currentgroup = groups_get_activity_group($PAGE->cm);
            $manage = has_capability('mod/wikicode:managewiki', $PAGE->cm->context);
            $edit = has_capability('mod/wikicode:editpage', $PAGE->context);
            if (!$manage and !($edit and groups_is_member($currentgroup))) {
                unset($this->tabs['edit']);
            }
        } else {
            if (!has_capability('mod/wikicode:editpage', $PAGE->context)) {
                unset($this->tabs['edit']);
            }
        }


        if (empty($options)) {
            $this->tabs_options = array('activetab' => substr(get_class($this), 10));
        } else {
            $this->tabs_options = $options;
        }

    }

    /**
     * This method must be overwritten to print the page content.
     */
    function print_content() {
        throw new coding_exception('Page wiki class does not implement method print_content()');
    }

    /**
     * Method to set the current page
     *
     * @param object $page Current page
     */
    function set_page($page) {
        global $PAGE;

        $this->page = $page;
        $this->title = $page->title;
        $PAGE->set_title($this->title);
    }

    /**
     * Method to set the current page title.
     * This method must be called when the current page is not created yet.
     * @param string $title Current page title.
     */
    function set_title($title) {
        global $PAGE;

        $this->page = null;
        $this->title = $title;
        $PAGE->set_title($this->title);
    }

    /**
     * Method to set current group id
     * @param int $gid Current group id
     */
    function set_gid($gid) {
        $this->gid = $gid;
    }

    /**
     * Method to set current user id
     * @param int $uid Current user id
     */
    function set_uid($uid) {
        $this->uid = $uid;
    }

    /**
     * Method to set the URL of the page.
     * This method must be overwritten by every type of page.
     */
    protected function set_url() {
        throw new coding_exception('Page wiki class does not implement method set_url()');
    }

    /**
     * Protected method to create the common items of the navbar in every page type.
     */
    protected function create_navbar() {
        global $PAGE, $CFG;

        $PAGE->navbar->add(format_string($this->title), $CFG->wwwroot . '/mod/wikicode/view.php?pageid=' . $this->page->id);
    }

    /**
     * This method print the footer of the page.
     */
    function print_footer() {
        global $OUTPUT;
        echo $OUTPUT->footer();
    }

    protected function process_session_url() {
        global $USER, $SESSION;

        //delete locks if edit
        $url = $SESSION->wikipreviousurl;
        switch ($url['page']) {
        case 'edit':
            wikicode_delete_locks($url['params']['pageid'], $USER->id, $url['params']['section'], false);
            break;
        }
    }

    protected function set_session_url() {
        global $SESSION;
        unset($SESSION->wikipreviousurl);
    }

}

/**
 * View a wiki page
 *
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_wikicode_view extends page_wikicode {
    /**
     * @var int the coursemodule id
     */
    private $coursemodule;

    function print_header() {
        global $PAGE;

        parent::print_header();

        $this->wikioutput->wikicode_print_subwiki_selector($PAGE->activityrecord, $this->subwiki, $this->page, 'view');

        //if (!empty($this->page)) {
        //    echo $this->wikioutput->prettyview_link($this->page);
        //}

        //echo $this->wikioutput->page_index();

        $this->print_pagetitle();
    }

    function print_content() {
        global $PAGE, $CFG;

        if (wikicode_user_can_view($this->subwiki)) {

            if (!empty($this->page)) {
                wikicode_print_page_content($this->page, $this->modcontext, $this->subwiki->id);
                $wiki = $PAGE->activityrecord;
            } else {
                print_string('nocontent', 'wikicode');
                // TODO: fix this part
                $swid = 0;
                if (!empty($this->subwiki)) {
                    $swid = $this->subwiki->id;
                }
            }
        } else {
            // @TODO: Tranlate it
            echo "You can not view this page";
        }
    }

    function set_url() {
        global $PAGE, $CFG;
        $params = array();

        if (isset($this->coursemodule)) {
            $params['id'] = $this->coursemodule;
        } else if (!empty($this->page) and $this->page != null) {
            $params['pageid'] = $this->page->id;
        } else if (!empty($this->gid)) {
            $params['wid'] = $PAGE->cm->instance;
            $params['group'] = $this->gid;
        } else if (!empty($this->title)) {
            $params['swid'] = $this->subwiki->id;
            $params['title'] = $this->title;
        } else {
            print_error(get_string('invalidparameters', 'wikicode'));
        }

        $PAGE->set_url($CFG->wwwroot . '/mod/wikicode/view.php', $params);
    }

    function set_coursemodule($id) {
        $this->coursemodule = $id;
    }

    protected function create_navbar() {
        global $PAGE, $CFG;

        $PAGE->navbar->add(format_string($this->title));
        $PAGE->navbar->add(get_string('view', 'wikicode'));
    }
}

/**
 * Wiki page editing page
 *
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_wikicode_edit extends page_wikicode {

    public static $attachmentoptions;

    protected $sectioncontent;
    /** @var string the section name needed to be edited */
    protected $section;
    protected $overridelock = false;
    protected $versionnumber = -1;
    protected $upload = false;
    protected $attachments = 0;
    protected $deleteuploads = array();
    protected $format;
	protected $compiled = 0;

    function __construct($wiki, $subwiki, $cm) {
        global $CFG, $PAGE;
        parent::__construct($wiki, $subwiki, $cm);
        self::$attachmentoptions = array('subdirs' => false, 'maxfiles' => - 1, 'maxbytes' => $CFG->maxbytes, 'accepted_types' => '*');
        //$PAGE->requires->js_init_call('M.mod_wikicode.renew_lock', null, true);
        $PAGE->requires->yui2_lib('connection');
    }

    protected function print_pagetitle() {
        global $OUTPUT;

        $title = $this->title;
        if (isset($this->section)) {
            $title .= ' : ' . $this->section;
        }
        echo $OUTPUT->container_start('wikicode_clear');
        echo $OUTPUT->heading(format_string($title), 2, 'wikicode_headingtitle');
		echo "<script src=\"js/codemirror.js\" type=\"text/javascript\"></script>";
        echo $OUTPUT->container_end();
    }

    function print_header() {
        global $OUTPUT, $PAGE;
        //$PAGE->requires->data_for_js('wikicode', array('renew_lock_timeout' => LOCK_TIMEOUT - 5, 'pageid' => $this->page->id, 'section' => $this->section));

        parent::print_header();

        $this->print_pagetitle();

        print '<noscript>' . $OUTPUT->box(get_string('javascriptdisabledlocks', 'wikicode'), 'errorbox') . '</noscript>';
    }

    function print_content() {
        global $PAGE;

        if (wikicode_user_can_edit($this->subwiki)) {
            $this->print_edit(null, $compile);
        } else {
            // @TODO: Translate it
            echo "You can not edit this page";
        }
    }

    protected function set_url() {
        global $PAGE, $CFG;

        $params = array('pageid' => $this->page->id);

        if (isset($this->section)) {
            $params['section'] = $this->section;
        }

        $PAGE->set_url($CFG->wwwroot . '/mod/wikicode/edit.php', $params);
    }

    protected function set_session_url() {
        global $SESSION;

        $SESSION->wikipreviousurl = array('page' => 'edit', 'params' => array('pageid' => $this->page->id, 'section' => $this->section));
    }

    protected function process_session_url() {
    }

    function set_section($sectioncontent, $section) {
        $this->sectioncontent = $sectioncontent;
        $this->section = $section;
    }

    public function set_versionnumber($versionnumber) {
        $this->versionnumber = $versionnumber;
    }

    public function set_overridelock($override) {
        $this->overridelock = $override;
    }

    function set_format($format) {
        $this->format = $format;
    }

    public function set_upload($upload) {
        $this->upload = $upload;
    }

    public function set_attachments($attachments) {
        $this->attachments = $attachments;
    }

    public function set_deleteuploads($deleteuploads) {
        $this->deleteuploads = $deleteuploads;
    }
	
	public function set_compiled($compiled) {
		$this->compiled = $compiled;
	}

    protected function create_navbar() {
        global $PAGE, $CFG;

        parent::create_navbar();

        $PAGE->navbar->add(get_string('edit', 'wikicode'));
    }

    protected function check_locks() {
        global $OUTPUT, $USER, $CFG;

        /*if (!wikicode_set_lock($this->page->id, $USER->id, $this->section, true)) {
            print $OUTPUT->box(get_string('pageislocked', 'wikicode'), 'generalbox boxwidthnormal boxaligncenter');

            if ($this->overridelock) {
                $params = 'pageid=' . $this->page->id;

                if ($this->section) {
                    $params .= '&section=' . urlencode($this->section);
                }

                $form = '<form method="post" action="' . $CFG->wwwroot . '/mod/wikicode/overridelocks.php?' . $params . '">';
                $form .= '<input type="hidden" name="sesskey" value="' . sesskey() . '" />';
                $form .= '<input type="submit" value="' . get_string('overridelocks', 'wikicode') . '" />';
                $form .= '</form>';

                print $OUTPUT->box($form, 'generalbox boxwidthnormal boxaligncenter');
            }
            return false;
        }*/
        return true;
    }

    protected function print_edit($content = null) {
        global $CFG, $OUTPUT, $USER, $PAGE;

        if (!$this->check_locks()) {
            return;
        }

        //delete old locks (> 1 hour)
        wikicode_delete_old_locks();

        $version = wikicode_get_current_version($this->page->id);
		$page = wikicode_get_page($this->page->id);
		
        $format = $version->contentformat;

        if ($content == null) {
            if (empty($this->section)) {
                $content = $version->content;
            } else {
                $content = $this->sectioncontent;
            }
        }

        $versionnumber = $version->version;
        if ($this->versionnumber >= 0) {
            if ($version->version != $this->versionnumber) {
                print $OUTPUT->box(get_string('wrongversionlock', 'wikicode'), 'errorbox');
                $versionnumber = $this->versionnumber;
            }
        }

        $url = $CFG->wwwroot . '/mod/wikicode/edit.php?pageid=' . $this->page->id;
        if (!empty($this->section)) {
            $url .= "&section=" . urlencode($this->section);
        }

        $params = array('attachmentoptions' => page_wikicode_edit::$attachmentoptions, 'format' => $version->contentformat, 'version' => $versionnumber, 'pagetitle'=>$this->page->title);

        $data = new StdClass();
        $data->newcontent = wikicode_remove_tags_owner($content);
        $data->version = $versionnumber;
        $data->format = $format;

        switch ($format) {
        case 'html':
            $data->newcontentformat = FORMAT_HTML;
            // Append editor context to editor options, giving preference to existing context.
            page_wikicode_edit::$attachmentoptions = array_merge(array('context' => $this->modcontext), page_wikicode_edit::$attachmentoptions);
            $data = file_prepare_standard_editor($data, 'newcontent', page_wikicode_edit::$attachmentoptions, $this->modcontext, 'mod_wikicode', 'attachments', $this->subwiki->id);
            break;
        default:
            break;
            }

        if ($version->contentformat != 'html') {
            $params['fileitemid'] = $this->subwiki->id;
            $params['contextid']  = $this->modcontext->id;
            $params['component']  = 'mod_wikicode';
            $params['filearea']   = 'attachments';
        }

        if (!empty($CFG->usetags)) {
            $params['tags'] = tag_get_tags_csv('wikicode_pages', $this->page->id, TAG_RETURN_TEXT);
        }

        $form = new mod_wikicode_edit_form($url, $params);

        if ($formdata = $form->get_data()) {
            if (!empty($CFG->usetags)) {
                $data->tags = $formdata->tags;
            }
        } else {
            if (!empty($CFG->usetags)) {
                $data->tags = tag_get_tags_array('wikicode', $this->page->id);
            }
        }
		
		if ( $this->compiled == 1 ) {
			$data->newcontent = wikicode_remove_tags_owner($page->cachedcompile);
			$data->textCompiler = $page->cachedgcc;
		}
		
        $form->set_data($data);
        $form->display();
    }

}

/**
 * Wiki page editing page
 *
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_wikicode_log extends page_wikicode {
	
	protected $sectioncontent;
    /** @var string the section name needed to be edited */
    protected $section;
    protected $overridelock = false;
    protected $versionnumber = -1;

    function __construct($wiki, $subwiki, $cm) {
        global $CFG, $PAGE;
        parent::__construct($wiki, $subwiki, $cm);
        //$PAGE->requires->js_init_call('M.mod_wikicode.renew_lock', null, true);
        $PAGE->requires->yui2_lib('connection');
    }

    protected function print_pagetitle() {
        global $OUTPUT;

        $title = $this->title;
        if (isset($this->section)) {
            $title .= ' : ' . $this->section;
        }
        echo $OUTPUT->container_start('wikicode_clear');
        echo $OUTPUT->heading(format_string($title), 2, 'wikicode_headingtitle');
        echo $OUTPUT->container_end();
    }

    function print_header() {
        global $OUTPUT, $PAGE;

        parent::print_header();

        $this->print_pagetitle();
    }

    function print_content() {
        global $PAGE;

        if (wikicode_user_can_edit($this->subwiki)) {
            $this->print_log();
        } else {
            // @TODO: Translate it
            echo "You can not edit this page";
        }
    }

    protected function set_url() {
        global $PAGE, $CFG;

        $params = array('pageid' => $this->page->id);

        if (isset($this->section)) {
            $params['section'] = $this->section;
        }

        $PAGE->set_url($CFG->wwwroot . '/mod/wikicode/log.php', $params);
    }

    protected function set_session_url() {
        global $SESSION;

        $SESSION->wikipreviousurl = array('page' => 'log', 'params' => array('pageid' => $this->page->id, 'section' => $this->section));
    }

    protected function process_session_url() {
    }

    protected function create_navbar() {
        global $PAGE, $CFG;

        parent::create_navbar();

        $PAGE->navbar->add(get_string('log', 'wikicode'));
    }

    protected function check_locks() {
        global $OUTPUT, $USER, $CFG;

        return true;
    }

    protected function print_log() {
        global $CFG, $OUTPUT, $USER, $PAGE;

        if (!$this->check_locks()) {
            return;
        }

        //delete old locks (> 1 hour)
        wikicode_delete_old_locks();

        $version = wikicode_get_current_version($this->page->id);
		$page = wikicode_get_page($this->page->id);
		
        $format = $version->contentformat;

        $params = array('attachmentoptions' => page_wikicode_edit::$attachmentoptions, 'format' => $version->contentformat, 'version' => $versionnumber, 'pagetitle'=>$this->page->title);

        $data = new StdClass();
		$params['page'] = $this->page;

        $form = new mod_wikicode_log_form($url, $params);
		
        $form->set_data($data);
        $form->display();
    }

}

/**
 * Class that models the behavior of wiki's view comments page
 *
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_wikicode_comments extends page_wikicode {

    function print_header() {

        parent::print_header();

        $this->print_pagetitle();

    }

    function print_content() {
        global $CFG, $OUTPUT, $USER, $PAGE;
        require_once($CFG->dirroot . '/mod/wikicode/locallib.php');

        $page = $this->page;
        $subwiki = $this->subwiki;
        $wiki = $PAGE->activityrecord;
        list($context, $course, $cm) = get_context_info_array($this->modcontext->id);

        require_capability('mod/wikicode:viewcomment', $this->modcontext, NULL, true, 'noviewcommentpermission', 'wikicode');

        $comments = wikicode_get_comments($this->modcontext->id, $page->id);

        if (has_capability('mod/wikicode:editcomment', $this->modcontext)) {
            echo '<div class="midpad"><a href="' . $CFG->wwwroot . '/mod/wikicode/editcomments.php?action=add&amp;pageid=' . $page->id . '">' . get_string('addcomment', 'wikicode') . '</a></div>';
        }

        $options = array('swid' => $this->page->subwikiid, 'pageid' => $page->id);
        $version = wikicode_get_current_version($this->page->id);
        $format = $version->contentformat;

        if (empty($comments)) {
            echo $OUTPUT->heading(get_string('nocomments', 'wikicode'));
        }

        foreach ($comments as $comment) {

            $user = wikicode_get_user_info($comment->userid);

            $fullname = fullname($user, has_capability('moodle/site:viewfullnames', get_context_instance(CONTEXT_COURSE, $course->id)));
            $by = new stdclass();
            $by->name = '<a href="' . $CFG->wwwroot . '/user/view.php?id=' . $user->id . '&amp;course=' . $course->id . '">' . $fullname . '</a>';
            $by->date = userdate($comment->timecreated);

            $t = new html_table();
            $cell1 = new html_table_cell($OUTPUT->user_picture($user, array('popup' => true)));
            $cell2 = new html_table_cell(get_string('bynameondate', 'forum', $by));
            $cell3 = new html_table_cell();
            $cell3->atributtes ['width'] = "80%";
            $cell4 = new html_table_cell();
            $cell5 = new html_table_cell();

            $row1 = new html_table_row();
            $row1->cells[] = $cell1;
            $row1->cells[] = $cell2;
            $row2 = new html_table_row();
            $row2->cells[] = $cell3;

            if ($format != 'html') {
                if ($format == 'creole') {
                    $parsedcontent = wikicode_parse_content('creole', $comment->content, $options);
                } else if ($format == 'nwiki') {
                    $parsedcontent = wikicode_parse_content('nwiki', $comment->content, $options);
                }

                $cell4->text = format_text(html_entity_decode($parsedcontent['parsed_text']), FORMAT_HTML);
            } else {
                $cell4->text = format_text($comment->content, FORMAT_HTML);
            }

            $row2->cells[] = $cell4;

            $t->data = array($row1, $row2);

            $actionicons = false;
            if ((has_capability('mod/wikicode:managecomment', $this->modcontext))) {
                $urledit = new moodle_url('/mod/wikicode/editcomments.php', array('commentid' => $comment->id, 'pageid' => $page->id, 'action' => 'edit'));
                $urldelet = new moodle_url('/mod/wikicode/instancecomments.php', array('commentid' => $comment->id, 'pageid' => $page->id, 'action' => 'delete'));
                $actionicons = true;
            } else if ((has_capability('mod/wikicode:editcomment', $this->modcontext)) and ($USER->id == $user->id)) {
                $urledit = new moodle_url('/mod/wikicode/editcomments.php', array('commentid' => $comment->id, 'pageid' => $page->id, 'action' => 'edit'));
                $urldelet = new moodle_url('/mod/wikicode/instancecomments.php', array('commentid' => $comment->id, 'pageid' => $page->id, 'action' => 'delete'));
                $actionicons = true;
            }

            if ($actionicons) {
                $cell6 = new html_table_cell($OUTPUT->action_icon($urledit, new pix_icon('t/edit', get_string('edit'))) . $OUTPUT->action_icon($urldelet, new pix_icon('t/delete', get_string('delete'))));
                $row3 = new html_table_row();
                $row3->cells[] = $cell5;
                $row3->cells[] = $cell6;
                $t->data[] = $row3;
            }

            echo html_writer::tag('div', html_writer::table($t), array('class'=>'no-overflow'));

        }
    }

    function set_url() {
        global $PAGE, $CFG;
        $PAGE->set_url($CFG->wwwroot . '/mod/wikicode/comments.php', array('pageid' => $this->page->id));
    }

    protected function create_navbar() {
        global $PAGE, $CFG;

        parent::create_navbar();
        $PAGE->navbar->add(get_string('comments', 'wikicode'));
    }

}

/**
 * Class that models the behavior of wiki's edit comment
 *
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_wikicode_editcomment extends page_wikicode {
    private $comment;
    private $action;
    private $form;
    private $format;

    function set_url() {
        global $PAGE, $CFG;
        $PAGE->set_url($CFG->wwwroot . '/mod/wikicode/comments.php', array('pageid' => $this->page->id));
    }

    function print_header() {
        parent::print_header();
        $this->print_pagetitle();
    }

    function print_content() {
        global $PAGE;

        require_capability('mod/wikicode:editcomment', $this->modcontext, NULL, true, 'noeditcommentpermission', 'wikicode');

        if ($this->action == 'add') {
            $this->add_comment_form();
        } else if ($this->action == 'edit') {
            $this->edit_comment_form($this->comment);
        }
    }

    function set_action($action, $comment) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/wikicode/comments_form.php');

        $this->action = $action;
        $this->comment = $comment;
        $version = wikicode_get_current_version($this->page->id);
        $this->format = $version->contentformat;

        if ($this->format == 'html') {
            $destination = $CFG->wwwroot . '/mod/wikicode/instancecomments.php?pageid=' . $this->page->id;
            $this->form = new mod_wikicode_comments_form($destination);
        }
    }

    protected function create_navbar() {
        global $PAGE, $CFG;

        $PAGE->navbar->add(get_string('comments', 'wikicode'), $CFG->wwwroot . '/mod/wikicode/comments.php?pageid=' . $this->page->id);

        if ($this->action == 'add') {
            $PAGE->navbar->add(get_string('insertcomment', 'wikicode'));
        } else {
            $PAGE->navbar->add(get_string('editcomment', 'wikicode'));
        }
    }

    protected function setup_tabs() {
        parent::setup_tabs(array('linkedwhenactive' => 'comments', 'activetab' => 'comments'));
    }

    private function add_comment_form() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/wikicode/editors/wiki_editor.php');

        $pageid = $this->page->id;

        if ($this->format == 'html') {
            $com = new stdClass();
            $com->action = 'add';
            $com->commentoptions = array('trusttext' => true, 'maxfiles' => 0);
            $this->form->set_data($com);
            $this->form->display();
        } else {
            wikicode_print_editor_wiki($this->page->id, null, $this->format, -1, null, false, null, 'addcomments');
        }
    }

    private function edit_comment_form($com) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/wikicode/comments_form.php');
        require_once($CFG->dirroot . '/mod/wikicode/editors/wiki_editor.php');

        if ($this->format == 'html') {
            $com->action = 'edit';
            $com->entrycomment_editor['text'] = $com->content;
            $com->commentoptions = array('trusttext' => true, 'maxfiles' => 0);

            $this->form->set_data($com);
            $this->form->display();
        } else {
            wikicode_print_editor_wiki($this->page->id, $com->content, $this->format, -1, null, false, array(), 'editcomments', $com->id);
        }

    }

}

/**
 * Wiki page search page
 *
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_wikicode_search extends page_wikicode {
    private $search_result;

    protected function create_navbar() {
        global $PAGE, $CFG;

        $PAGE->navbar->add(format_string($this->title));
    }

    function set_search_string($search, $searchcontent) {
        $swid = $this->subwiki->id;
        if ($searchcontent) {
            $this->search_result = wikicode_search_all($swid, $search);
        } else {
            $this->search_result = wikicode_search_title($swid, $search);
        }

    }

    function set_url() {
        global $PAGE, $CFG;
        $PAGE->set_url($CFG->wwwroot . '/mod/wikicode/search.php');
    }
    function print_content() {
        global $PAGE;

        require_capability('mod/wikicode:viewpage', $this->modcontext, NULL, true, 'noviewpagepermission', 'wikicode');

        echo $this->wikioutput->search_result($this->search_result, $this->subwiki);
    }
}

/**
 *
 * Class that models the behavior of wiki's
 * create page
 *
 */
class page_wikicode_create extends page_wikicode {

    private $format;
    private $swid;
    private $wid;
    private $action;
    private $mform;

    function print_header() {
        $this->set_url();
        parent::print_header();
    }

    function set_url() {
        global $PAGE, $CFG;

        $params = array();
        if ($this->action == 'new') {
            $params['action'] = 'new';
            $params['swid'] = $this->swid;
            $params['wid'] = $this->wid;
            if ($this->title != get_string('newpage', 'wikicode')) {
                $params['title'] = $this->title;
            }
            $PAGE->set_url($CFG->wwwroot . '/mod/wikicode/create.php', $params);
        } else {
            $params['action'] = 'create';
            $params['swid'] = $this->swid;
            $PAGE->set_url($CFG->wwwroot . '/mod/wikicode/create.php', $params);
        }
    }

    function set_format($format) {
        $this->format = $format;
    }

    function set_wid($wid) {
        $this->wid = $wid;
    }

    function set_swid($swid) {
        $this->swid = $swid;
    }

    function set_action($action) {
        global $PAGE;
        $this->action = $action;

        require_once(dirname(__FILE__) . '/create_form.php');
        $url = new moodle_url('/mod/wikicode/create.php', array('action' => 'create', 'wid' => $PAGE->activityrecord->id, 'gid' => $this->gid, 'uid' => $this->uid));
        $formats = wikicode_get_formats();
        $options = array('formats' => $formats, 'defaultformat' => $PAGE->activityrecord->defaultformat, 'forceformat' => $PAGE->activityrecord->forceformat);
        if ($this->title != get_string('newpage', 'wikicode')) {
            $options['disable_pagetitle'] = true;
        }
        $this->mform = new mod_wikicode_create_form($url->out(false), $options);
    }

    protected function create_navbar() {
        global $PAGE;

        $PAGE->navbar->add($this->title);
    }

    function print_content($pagetitle = '') {
        global $PAGE;

        // @TODO: Change this to has_capability and show an alternative interface.
        require_capability('mod/wikicode:createpage', $this->modcontext, NULL, true, 'nocreatepermission', 'wikicode');
        $data = new stdClass();
        if (!empty($pagetitle)) {
            $data->pagetitle = $pagetitle;
        }
        $data->pageformat = $PAGE->activityrecord->defaultformat;

        $this->mform->set_data($data);
        $this->mform->display();
    }

    function create_page($pagetitle) {
        global $USER, $CFG, $PAGE;
        $data = $this->mform->get_data();
        if (empty($this->subwiki)) {
            $swid = wikicode_add_subwiki($PAGE->activityrecord->id, $this->gid, $this->uid);
            $this->subwiki = wikicode_get_subwiki($swid);
        }
        if ($data) {
            $id = wikicode_create_page($this->subwiki->id, $data->pagetitle, $data->pageformat, $USER->id);
        } else {
            $id = wikicode_create_page($this->subwiki->id, $pagetitle, 'C', $USER->id);
        }
        redirect($CFG->wwwroot . '/mod/wikicode/edit.php?pageid=' . $id);
    }
}

/**
 * Class that models the behavior of wiki's
 * compile page
 *
 */
class page_wikicode_compile extends page_wikicode_edit {

    private $newcontent, $download;

    function print_header() {
    }

    function print_content() {
        global $PAGE;

        $context = get_context_instance(CONTEXT_MODULE, $PAGE->cm->id);
        require_capability('mod/wikicode:editpage', $context, NULL, true, 'noeditpermission', 'wikicode');

        $this->print_compile();
    }

    function set_newcontent($newcontent) {
        $this->newcontent = $newcontent;
    }
	
	function set_download($download) {
		$this->download = $download;
	}

    protected function set_session_url() {
    }

    protected function print_compile() {
        global $CFG, $USER, $OUTPUT, $PAGE;

        $url = $CFG->wwwroot . '/mod/wikicode/edit.php?pageid=' . $this->page->id;
        if (!empty($this->section)) {
            $url .= "&section=" . urlencode($this->section);
        }

        $params = array('attachmentoptions' => page_wikicode_edit::$attachmentoptions, 'format' => $this->format, 'version' => $this->versionnumber);

        if ($this->format != 'html') {
            $params['fileitemid'] = $this->page->id;
            $params['contextid']  = $this->modcontext->id;
            $params['component']  = 'mod_wikicode';
            $params['filearea']   = 'attachments';
        }

        $form = new mod_wikicode_edit_form($url, $params);

        $save = false;
        $data = false;
        if ($data = $form->get_data()) {

            $save = wikicode_compile_page($this->page, $data->newcontent, $USER->id, $this->download);
      
            //deleting old locks
            wikicode_delete_locks($this->page->id, $USER->id, $this->section);

            redirect($CFG->wwwroot . '/mod/wikicode/edit.php?compiled=1&pageid=' . $this->page->id);
        } else {
            print_error('savingerror', 'wikicode');
        }
    }
}

/**
 *
 * Class that models the behavior of wiki's
 * view differences
 *
 */
class page_wikicode_diff extends page_wikicode {

    private $compare;
    private $comparewith;

    function print_header() {
        global $OUTPUT;

        parent::print_header();

        $this->print_pagetitle();
        $vstring = new stdClass();
        $vstring->old = $this->compare;
        $vstring->new = $this->comparewith;
        echo $OUTPUT->heading(get_string('comparewith', 'wikicode', $vstring));
    }

    /**
     * Print the diff view
     */
    function print_content() {
        global $PAGE;

        require_capability('mod/wikicode:viewpage', $this->modcontext, NULL, true, 'noviewpagepermission', 'wikicode');

        $this->print_diff_content();
    }

    function set_url() {
        global $PAGE, $CFG;

        $PAGE->set_url($CFG->wwwroot . '/mod/wikicode/diff.php', array('pageid' => $this->page->id, 'comparewith' => $this->comparewith, 'compare' => $this->compare));
    }

    function set_comparison($compare, $comparewith) {
        $this->compare = $compare;
        $this->comparewith = $comparewith;
    }

    protected function create_navbar() {
        global $PAGE, $CFG;

        parent::create_navbar();
        $PAGE->navbar->add(get_string('history', 'wikicode'), $CFG->wwwroot . '/mod/wikicode/history.php?pageid' . $this->page->id);
        $PAGE->navbar->add(get_string('diff', 'wikicode'));
    }

    protected function setup_tabs() {
        parent::setup_tabs(array('linkedwhenactive' => 'history', 'activetab' => 'history'));
    }

    /**
     * Given two versions of a page, prints a page displaying the differences between them.
     *
     * @global object $CFG
     * @global object $OUTPUT
     * @global object $PAGE
     */
    private function print_diff_content() {
        global $CFG, $OUTPUT, $PAGE;

        $pageid = $this->page->id;
        $total = wikicode_count_wikicode_page_versions($pageid) - 1;

        $oldversion = wikicode_get_wikicode_page_version($pageid, $this->compare);

        $newversion = wikicode_get_wikicode_page_version($pageid, $this->comparewith);

        if ($oldversion && $newversion) {

            $oldtext = format_text(wikicode_remove_tags($oldversion->content), FORMAT_PLAIN, array('overflowdiv'=>true));
			$newtext = format_text(wikicode_remove_tags($newversion->content), FORMAT_PLAIN, array('overflowdiv'=>true));
            list($diff1, $diff2) = ouwiki_diff_html($oldtext, $newtext);
            $oldversion->diff = $diff1;
            $oldversion->user = wikicode_get_user_info($oldversion->userid);
            $newversion->diff = $diff2;
            $newversion->user = wikicode_get_user_info($newversion->userid);

            echo $this->wikioutput->diff($pageid, $oldversion, $newversion, array('total' => $total));
        } else {
            print_error('versionerror', 'wikicode');
        }
    }
}

/**
 *
 * Class that models the behavior of wiki's history page
 *
 */
class page_wikicode_history extends page_wikicode {
    /**
     * @var int $paging current page
     */
    private $paging;

    /**
     * @var int @rowsperpage Items per page
     */
    private $rowsperpage = 10;

    /**
     * @var int $allversion if $allversion != 0, all versions will be printed in a signle table
     */
    private $allversion;

    function __construct($wiki, $subwiki, $cm) {
        global $PAGE;
        parent::__construct($wiki, $subwiki, $cm);
        $PAGE->requires->js_init_call('M.mod_wikicode.history', null, true);
    }

    function print_header() {
        parent::print_header();
        $this->print_pagetitle();
    }

    function print_pagetitle() {
        global $OUTPUT;
        $html = '';

        $html .= $OUTPUT->container_start();
        $html .= $OUTPUT->heading_with_help(format_string($this->title), 'history', 'wikicode');
        $html .= $OUTPUT->container_end();
        echo $html;
    }

    function print_content() {
        global $PAGE;

        require_capability('mod/wikicode:viewpage', $this->modcontext, NULL, true, 'noviewpagepermission', 'wikicode');

        $this->print_history_content();
    }

    function set_url() {
        global $PAGE, $CFG;
        $PAGE->set_url($CFG->wwwroot . '/mod/wikicode/history.php', array('pageid' => $this->page->id));
    }

    function set_paging($paging) {
        $this->paging = $paging;
    }

    function set_allversion($allversion) {
        $this->allversion = $allversion;
    }

    protected function create_navbar() {
        global $PAGE, $CFG;

        parent::create_navbar();
        $PAGE->navbar->add(get_string('history', 'wikicode'));
    }

    /**
     * Prints the history for a given wiki page
     *
     * @global object $CFG
     * @global object $OUTPUT
     * @global object $PAGE
     */
    private function print_history_content() {
        global $CFG, $OUTPUT, $PAGE;

        $pageid = $this->page->id;
        $offset = $this->paging * $this->rowsperpage;
        // vcount is the latest version
        $vcount = wikicode_count_wikicode_page_versions($pageid) - 1;
        if ($this->allversion) {
            $versions = wikicode_get_wikicode_page_versions($pageid, 0, $vcount);
        } else {
            $versions = wikicode_get_wikicode_page_versions($pageid, $offset, $this->rowsperpage);
        }
        // We don't want version 0 to be displayed
        // version 0 is blank page
        if (end($versions)->version == 0) {
            array_pop($versions);
        }

        $contents = array();

        $version0page = wikicode_get_wikicode_page_version($this->page->id, 0);
        $creator = wikicode_get_user_info($version0page->userid);
        $a = new StdClass;
        $a->date = userdate($this->page->timecreated, get_string('strftimedaydatetime', 'langconfig'));
        $a->username = fullname($creator);
        echo $OUTPUT->heading(get_string('createddate', 'wikicode', $a), 4, 'wikicode_headingtime');
        if ($vcount > 0) {

            /// If there is only one version, we don't need radios nor forms
            if (count($versions) == 1) {

                $row = array_shift($versions);

                $username = wikicode_get_user_info($row->userid);
                $picture = $OUTPUT->user_picture($username);
                $date = userdate($row->timecreated, get_string('strftimedate', 'langconfig'));
                $time = userdate($row->timecreated, get_string('strftimetime', 'langconfig'));
                $versionid = wikicode_get_version($row->id);
                $versionlink = new moodle_url('/mod/wikicode/viewversion.php', array('pageid' => $pageid, 'versionid' => $versionid->id));
                $userlink = new moodle_url('/user/view.php', array('id' => $username->id));
                $contents[] = array('', html_writer::link($versionlink->out(false), $row->version), $picture . html_writer::link($userlink->out(false), fullname($username)), $time, $OUTPUT->container($date, 'wikicode_histdate'));

                $table = new html_table();
                $table->head = array('', get_string('version'), get_string('user'), get_string('modified'), '');
                $table->data = $contents;
                $table->attributes['class'] = 'mdl-align';

                echo html_writer::table($table);

            } else {

                $checked = $vcount - $offset;
                $rowclass = array();

                foreach ($versions as $version) {
                    $user = wikicode_get_user_info($version->userid);
                    $picture = $OUTPUT->user_picture($user, array('popup' => true));
                    $date = userdate($version->timecreated, get_string('strftimedate'));
                    $rowclass[] = 'wikicode_histnewdate';
                    $time = userdate($version->timecreated, get_string('strftimetime', 'langconfig'));
                    $versionid = wikicode_get_version($version->id);
                    if ($versionid) {
                        $url = new moodle_url('/mod/wikicode/viewversion.php', array('pageid' => $pageid, 'versionid' => $versionid->id));
                        $viewlink = html_writer::link($url->out(false), $version->version);
                    } else {
                        $viewlink = $version->version;
                    }
                    $userlink = new moodle_url('/user/view.php', array('id' => $version->userid));
                    $contents[] = array($this->choose_from_radio(array($version->version  => null), 'compare', 'M.mod_wikicode.history()', $checked - 1, true) . $this->choose_from_radio(array($version->version  => null), 'comparewith', 'M.mod_wikicode.history()', $checked, true), $viewlink, $picture . html_writer::link($userlink->out(false), fullname($user)), $time, $OUTPUT->container($date, 'wikicode_histdate'));
                }

                $table = new html_table();

                $icon = $OUTPUT->help_icon('diff', 'wikicode');

                $table->head = array(get_string('diff', 'wikicode') . $icon, get_string('version'), get_string('user'), get_string('modified'), '');
                $table->data = $contents;
                $table->attributes['class'] = 'generaltable mdl-align';
                $table->rowclasses = $rowclass;

                /*$table = new StdClass();
                 $table->head = array(helpbutton('diff', 'diff', 'wikicode', true, false, '', true, ''),
                 get_string('version'),
                 get_string('user'),
                 get_string('modified'),
                 '');
                 $table->data = $contents;
                 $table->class = 'mdl-align';
                 $table->rowclass = $rowclass;*/

                ///Print the form
                echo html_writer::start_tag('form', array('action'=>new moodle_url('/mod/wikicode/diff.php'), 'method'=>'get', 'id'=>'diff'));
                echo html_writer::tag('div', html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'pageid', 'value'=>$pageid)));
                echo html_writer::table($table);
                echo html_writer::start_tag('div', array('class'=>'mdl-align'));
                echo html_writer::empty_tag('input', array('type'=>'submit', 'class'=>'wikicode_form-button', 'value'=>get_string('comparesel', 'wikicode')));
                echo html_writer::end_tag('div');
                echo html_writer::end_tag('form');
            }
        } else {
            print_string('nohistory', 'wikicode');
        }
        if (!$this->allversion) {
            //$pagingbar = moodle_paging_bar::make($vcount, $this->paging, $this->rowsperpage, $CFG->wwwroot.'/mod/wikicode/history.php?pageid='.$pageid.'&amp;');
            // $pagingbar->pagevar = $pagevar;
            echo $OUTPUT->paging_bar($vcount, $this->paging, $this->rowsperpage, $CFG->wwwroot . '/mod/wikicode/history.php?pageid=' . $pageid . '&amp;');
            //print_paging_bar($vcount, $paging, $rowsperpage,$CFG->wwwroot.'/mod/wikicode/history.php?pageid='.$pageid.'&amp;','paging');
            } else {
            $link = new moodle_url('/mod/wikicode/history.php', array('pageid' => $pageid));
            $OUTPUT->container(html_writer::link($link->out(false), get_string('viewperpage', 'wikicode', $this->rowsperpage)), 'mdl-align');
        }
        if ($vcount > $this->rowsperpage && !$this->allversion) {
            $link = new moodle_url('/mod/wikicode/history.php', array('pageid' => $pageid, 'allversion' => 1));
            $OUTPUT->container(html_writer::link($link->out(false), get_string('viewallhistory', 'wikicode')), 'mdl-align');
        }
    }

    /**
     * Given an array of values, creates a group of radio buttons to be part of a form
     *
     * @param array  $options  An array of value-label pairs for the radio group (values as keys).
     * @param string $name     Name of the radiogroup (unique in the form).
     * @param string $onclick  Function to be executed when the radios are clicked.
     * @param string $checked  The value that is already checked.
     * @param bool   $return   If true, return the HTML as a string, otherwise print it.
     *
     * @return mixed If $return is false, returns nothing, otherwise returns a string of HTML.
     */
    private function choose_from_radio($options, $name, $onclick = '', $checked = '', $return = false) {

        static $idcounter = 0;

        if (!$name) {
            $name = 'unnamed';
        }

        $output = '<span class="radiogroup ' . $name . "\">\n";

        if (!empty($options)) {
            $currentradio = 0;
            foreach ($options as $value => $label) {
                $htmlid = 'auto-rb' . sprintf('%04d', ++$idcounter);
                $output .= ' <span class="radioelement ' . $name . ' rb' . $currentradio . "\">";
                $output .= '<input name="' . $name . '" id="' . $htmlid . '" type="radio" value="' . $value . '"';
                if ($value == $checked) {
                    $output .= ' checked="checked"';
                }
                if ($onclick) {
                    $output .= ' onclick="' . $onclick . '"';
                }
                if ($label === '') {
                    $output .= ' /> <label for="' . $htmlid . '">' . $value . '</label></span>' . "\n";
                } else {
                    $output .= ' /> <label for="' . $htmlid . '">' . $label . '</label></span>' . "\n";
                }
                $currentradio = ($currentradio + 1) % 2;
            }
        }

        $output .= '</span>' . "\n";

        if ($return) {
            return $output;
        } else {
            echo $output;
        }
    }
}

/**
 * Class that models the behavior of wiki's map page
 *
 */
class page_wikicode_map extends page_wikicode {

    /**
     * @var int wiki view option
     */
    private $view;

    function print_header() {
        parent::print_header();
        $this->print_pagetitle();
    }

    function print_content() {
        global $CFG, $PAGE;

        require_capability('mod/wikicode:viewpage', $this->modcontext, NULL, true, 'noviewpagepermission', 'wikicode');

        if ($this->view > 0) {
            //echo '<div><a href="' . $CFG->wwwroot . '/mod/wikicode/map.php?pageid=' . $this->page->id . '">' . get_string('backtomapmenu', 'wikicode') . '</a></div>';
        }

        switch ($this->view) {
        case 1:
            echo $this->wikioutput->menu_map($this->page->id, $this->view);
            $this->print_contributions_content();
            break;
        case 2:
            echo $this->wikioutput->menu_map($this->page->id, $this->view);
            $this->print_navigation_content();
            break;
        case 3:
            echo $this->wikioutput->menu_map($this->page->id, $this->view);
            $this->print_orphaned_content();
            break;
        case 4:
            echo $this->wikioutput->menu_map($this->page->id, $this->view);
            $this->print_index_content();
            break;
        case 5:
            echo $this->wikioutput->menu_map($this->page->id, $this->view);
            $this->print_page_list_content();
            break;
        case 6:
            echo $this->wikioutput->menu_map($this->page->id, $this->view);
            $this->print_updated_content();
            break;
        default:
            echo $this->wikioutput->menu_map($this->page->id, $this->view);
            $this->print_page_list_content();
        }
    }

    function set_view($option) {
        $this->view = $option;
    }

    function set_url() {
        global $PAGE, $CFG;
        $PAGE->set_url($CFG->wwwroot . '/mod/wikicode/map.php', array('pageid' => $this->page->id));
    }

    protected function create_navbar() {
        global $PAGE;

        parent::create_navbar();
        $PAGE->navbar->add(get_string('map', 'wikicode'));
    }

    /**
     * Prints the contributions tab content
     *
     * @uses $OUTPUT, $USER
     *
     */
    private function print_contributions_content() {
        global $CFG, $OUTPUT, $USER;
        $page = $this->page;

        if ($page->timerendered + wikicode_REFRESH_CACHE_TIME < time()) {
            $fresh = wikicode_refresh_cachedcontent($page);
            $page = $fresh['page'];
        }

        $swid = $this->subwiki->id;

        $table = new html_table();
        $table->head = array(get_string('contributions', 'wikicode') . $OUTPUT->help_icon('contributions', 'wikicode'));
        $table->attributes['class'] = 'wikicode_editor generalbox';
        $table->data = array();
        $table->rowclasses = array();

        $lastversions = array();
        $pages = array();
        $users = array();

        if ($contribs = wikicode_get_contributions($swid, $USER->id)) {
            foreach ($contribs as $contrib) {
                if (!array_key_exists($contrib->pageid, $pages)) {
                    $page = wikicode_get_page($contrib->pageid);
                    $pages[$contrib->pageid] = $page;
                } else {
                    continue;
                }

                if (!array_key_exists($page->id, $lastversions)) {
                    $version = wikicode_get_last_version($page->id);
                    $lastversions[$page->id] = $version;
                } else {
                    $version = $lastversions[$page->id];
                }

                if (!array_key_exists($version->userid, $users)) {
                    $user = wikicode_get_user_info($version->userid);
                    $users[$version->userid] = $user;
                } else {
                    $user = $users[$version->userid];
                }

                $link = wikicode_parser_link(format_string($page->title), array('swid' => $swid));
                $class = ($link['new']) ? 'class="wiki_newentry"' : '';

                $linkpage = '<a href="' . $link['url'] . '"' . $class . '>' . $link['content'] . '</a>';
                $icon = $OUTPUT->user_picture($user, array('popup' => true));

                $table->data[] = array("$icon&nbsp;$linkpage");
            }
        } else {
            $table->data[] = array(get_string('nocontribs', 'wikicode'));
        }
        echo html_writer::table($table);
    }

    /**
     * Prints the navigation tab content
     *
     * @uses $OUTPUT
     *
     */
    private function print_navigation_content() {
        global $OUTPUT;
        $page = $this->page;

        if ($page->timerendered + wikicode_REFRESH_CACHE_TIME < time()) {
            $fresh = wikicode_refresh_cachedcontent($page);
            $page = $fresh['page'];
        }

        $tolinks = wikicode_get_linked_to_pages($page->id);
        $fromlinks = wikicode_get_linked_from_pages($page->id);

        $table = new html_table();
        $table->attributes['class'] = 'wikicode_navigation_from';
        $table->head = array(get_string('navigationfrom', 'wikicode') . $OUTPUT->help_icon('navigationfrom', 'wikicode') . ':');
        $table->data = array();
        $table->rowclasses = array();
        foreach ($fromlinks as $link) {
            $lpage = wikicode_get_page($link->frompageid);
            $link = new moodle_url('/mod/wikicode/view.php', array('pageid' => $lpage->id));
            $table->data[] = array(html_writer::link($link->out(false), format_string($lpage->title)));
            $table->rowclasses[] = 'mdl-align';
        }

        $table_left = html_writer::table($table);

        $table = new html_table();
        $table->attributes['class'] = 'wikicode_navigation_to';
        $table->head = array(get_string('navigationto', 'wikicode') . $OUTPUT->help_icon('navigationto', 'wikicode') . ':');
        $table->data = array();
        $table->rowclasses = array();
        foreach ($tolinks as $link) {
            if ($link->tomissingpage) {
                $viewlink = new moodle_url('/mod/wikicode/create.php', array('swid' => $page->subwikiid, 'title' => $link->tomissingpage, 'action' => 'new'));
                $table->data[] = array(html_writer::link($viewlink->out(false), format_string($link->tomissingpage), array('class' => 'wikicode_newentry')));
            } else {
                $lpage = wikicode_get_page($link->topageid);
                $viewlink = new moodle_url('/mod/wikicode/view.php', array('pageid' => $lpage->id));
                $table->data[] = array(html_writer::link($viewlink->out(false), format_string($lpage->title)));
            }
            $table->rowclasses[] = 'mdl-align';
        }
        $table_right = html_writer::table($table);
        echo $OUTPUT->container($table_left . $table_right, 'wikicode_navigation_container');
    }

    /**
     * Prints the index page tab content
     *
     *
     */
    private function print_index_content() {
        global $OUTPUT;
        $page = $this->page;

        if ($page->timerendered + wikicode_REFRESH_CACHE_TIME < time()) {
            $fresh = wikicode_refresh_cachedcontent($page);
            $page = $fresh['page'];
        }

        $node = new navigation_node($page->title);

        $keys = array();
        $tree = array();
        $tree = wikicode_build_tree($page, $node, $keys);

        $table = new html_table();
        $table->head = array(get_string('pageindex', 'wikicode') . $OUTPUT->help_icon('pageindex', 'wikicode'));
        $table->attributes['class'] = 'wikicode_editor generalbox';
        $table->data[] = array($this->render_navigation_node($tree));

        echo html_writer::table($table);
    }

    /**
     * Prints the page list tab content
     *
     *
     */
    private function print_page_list_content() {
        global $OUTPUT;
        $page = $this->page;

        if ($page->timerendered + wikicode_REFRESH_CACHE_TIME < time()) {
            $fresh = wikicode_refresh_cachedcontent($page);
            $page = $fresh['page'];
        }

        $pages = wikicode_get_page_list($this->subwiki->id);

        $stdaux = new stdClass();
        $strspecial = get_string('special', 'wikicode');

        foreach ($pages as $page) {
            $letter = textlib::strtoupper(textlib::substr($page->title, 0, 1));
            if (preg_match('/[A-Z]/', $letter)) {
                $stdaux->{
                    $letter}
                [] = wikicode_parser_link($page);
            } else {
                $stdaux->{
                    $strspecial}
                [] = wikicode_parser_link($page);
            }
        }

        $table = new html_table();
        $table->head = array(get_string('pagelist', 'wikicode') . $OUTPUT->help_icon('pagelist', 'wikicode'));
        $table->attributes['class'] = 'wikicode_editor generalbox';
        $table->align = array('center');
        foreach ($stdaux as $key => $elem) {
            $table->data[] = array($key);
            foreach ($elem as $e) {
                $table->data[] = array(html_writer::link($e['url'], $e['content']));
            }
        }
        echo html_writer::table($table);
    }

    /**
     * Prints the orphaned tab content
     *
     *
     */
    private function print_orphaned_content() {
        global $OUTPUT;

        $page = $this->page;

        if ($page->timerendered + wikicode_REFRESH_CACHE_TIME < time()) {
            $fresh = wikicode_refresh_cachedcontent($page);
            $page = $fresh['page'];
        }

        $swid = $this->subwiki->id;

        $table = new html_table();
        $table->head = array(get_string('orphaned', 'wikicode') . $OUTPUT->help_icon('orphaned', 'wikicode'));
        $table->attributes['class'] = 'wikicode_editor generalbox';
        $table->data = array();
        $table->rowclasses = array();

        if ($orphanedpages = wikicode_get_orphaned_pages($swid)) {
            foreach ($orphanedpages as $page) {
                $link = wikicode_parser_link($page->title, array('swid' => $swid));
                $class = ($link['new']) ? 'class="wiki_newentry"' : '';
                $table->data[] = array('<a href="' . $link['url'] . '"' . $class . '>' . format_string($link['content']) . '</a>');
            }
        } else {
            $table->data[] = array(get_string('noorphanedpages', 'wikicode'));
        }

        echo html_writer::table($table);
    }

    /**
     * Prints the updated tab content
     *
     * @uses $COURSE, $OUTPUT
     *
     */
    private function print_updated_content() {
        global $COURSE, $OUTPUT;
        $page = $this->page;

        if ($page->timerendered + wikicode_REFRESH_CACHE_TIME < time()) {
            $fresh = wikicode_refresh_cachedcontent($page);
            $page = $fresh['page'];
        }

        $swid = $this->subwiki->id;

        $table = new html_table();
        $table->head = array(get_string('updatedpages', 'wikicode') . $OUTPUT->help_icon('updatedpages', 'wikicode'));
        $table->attributes['class'] = 'wikicode_editor generalbox';
        $table->data = array();
        $table->rowclasses = array();

        if ($pages = wikicode_get_updated_pages_by_subwiki($swid)) {
            $strdataux = '';
            foreach ($pages as $page) {
                $user = wikicode_get_user_info($page->userid);
                $strdata = strftime('%d %b %Y', $page->timemodified);
                if ($strdata != $strdataux) {
                    $table->data[] = array($OUTPUT->heading($strdata, 4));
                    $strdataux = $strdata;
                }
                $link = wikicode_parser_link($page->title, array('swid' => $swid));
                $class = ($link['new']) ? 'class="wiki_newentry"' : '';

                $linkpage = '<a href="' . $link['url'] . '"' . $class . '>' . format_string($link['content']) . '</a>';
                $icon = $OUTPUT->user_picture($user, array($COURSE->id));
                $table->data[] = array("$icon&nbsp;$linkpage");
            }
        } else {
            $table->data[] = array(get_string('noupdatedpages', 'wikicode'));
        }

        echo html_writer::table($table);
    }

    protected function render_navigation_node($items, $attrs = array(), $expansionlimit = null, $depth = 1) {

        // exit if empty, we don't want an empty ul element
        if (count($items) == 0) {
            return '';
        }

        // array of nested li elements
        $lis = array();
        foreach ($items as $item) {
            if (!$item->display) {
                continue;
            }
            $content = $item->get_content();
            $title = $item->get_title();
            if ($item->icon instanceof renderable) {
                $icon = $this->wikioutput->render($item->icon);
                $content = $icon . '&nbsp;' . $content; // use CSS for spacing of icons
                }
            if ($item->helpbutton !== null) {
                $content = trim($item->helpbutton) . html_writer::tag('span', $content, array('class' => 'clearhelpbutton'));
            }

            if ($content === '') {
                continue;
            }

            if ($item->action instanceof action_link) {
                //TODO: to be replaced with something else
                $link = $item->action;
                if ($item->hidden) {
                    $link->add_class('dimmed');
                }
                $content = $this->output->render($link);
            } else if ($item->action instanceof moodle_url) {
                $attributes = array();
                if ($title !== '') {
                    $attributes['title'] = $title;
                }
                if ($item->hidden) {
                    $attributes['class'] = 'dimmed_text';
                }
                $content = html_writer::link($item->action, $content, $attributes);

            } else if (is_string($item->action) || empty($item->action)) {
                $attributes = array();
                if ($title !== '') {
                    $attributes['title'] = $title;
                }
                if ($item->hidden) {
                    $attributes['class'] = 'dimmed_text';
                }
                $content = html_writer::tag('span', $content, $attributes);
            }

            // this applies to the li item which contains all child lists too
            $liclasses = array($item->get_css_type(), 'depth_' . $depth);
            if ($item->has_children() && (!$item->forceopen || $item->collapse)) {
                $liclasses[] = 'collapsed';
            }
            if ($item->isactive === true) {
                $liclasses[] = 'current_branch';
            }
            $liattr = array('class' => join(' ', $liclasses));
            // class attribute on the div item which only contains the item content
            $divclasses = array('tree_item');
            if ((empty($expansionlimit) || $item->type != $expansionlimit) && ($item->children->count() > 0 || ($item->nodetype == navigation_node::NODETYPE_BRANCH && $item->children->count() == 0 && isloggedin()))) {
                $divclasses[] = 'branch';
            } else {
                $divclasses[] = 'leaf';
            }
            if (!empty($item->classes) && count($item->classes) > 0) {
                $divclasses[] = join(' ', $item->classes);
            }
            $divattr = array('class' => join(' ', $divclasses));
            if (!empty($item->id)) {
                $divattr['id'] = $item->id;
            }
            $content = html_writer::tag('p', $content, $divattr) . $this->render_navigation_node($item->children, array(), $expansionlimit, $depth + 1);
            if (!empty($item->preceedwithhr) && $item->preceedwithhr === true) {
                $content = html_writer::empty_tag('hr') . $content;
            }
            $content = html_writer::tag('li', $content, $liattr);
            $lis[] = $content;
        }

        if (count($lis)) {
            return html_writer::tag('ul', implode("\n", $lis), $attrs);
        } else {
            return '';
        }
    }

}

/**
 * Class that models the behavior of wiki's restore version page
 *
 */
class page_wikicode_restoreversion extends page_wikicode {
    private $version;

    function print_header() {
        parent::print_header();
        $this->print_pagetitle();
    }

    function print_content() {
        global $CFG, $PAGE;

        require_capability('mod/wikicode:managewiki', $this->modcontext, NULL, true, 'nomanagewikipermission', 'wikicode');

        $this->print_restoreversion();
    }

    function set_url() {
        global $PAGE, $CFG;
        $PAGE->set_url($CFG->wwwroot . '/mod/wikicode/viewversion.php', array('pageid' => $this->page->id, 'versionid' => $this->version->id));
    }

    function set_versionid($versionid) {
        $this->version = wikicode_get_version($versionid);
    }

    protected function create_navbar() {
        global $PAGE, $CFG;

        parent::create_navbar();
        $PAGE->navbar->add(get_string('restoreversion', 'wikicode'));
    }

    protected function setup_tabs() {
        parent::setup_tabs(array('linkedwhenactive' => 'history', 'activetab' => 'history'));
    }

    /**
     * Prints the restore version content
     *
     * @uses $CFG
     *
     * @param page $page The page whose version will be restored
     * @param int  $versionid The version to be restored
     * @param bool $confirm If false, shows a yes/no confirmation page.
     *     If true, restores the old version and redirects the user to the 'view' tab.
     */
    private function print_restoreversion() {
        global $OUTPUT;

        $version = wikicode_get_version($this->version->id);

        $optionsyes = array('confirm'=>1, 'pageid'=>$this->page->id, 'versionid'=>$version->id, 'sesskey'=>sesskey());
        $restoreurl = new moodle_url('/mod/wikicode/restoreversion.php', $optionsyes);
        $return = new moodle_url('/mod/wikicode/viewversion.php', array('pageid'=>$this->page->id, 'versionid'=>$version->id));

        echo $OUTPUT->heading(get_string('restoreconfirm', 'wikicode', $version->version), 2);
        print_container_start(false, 'wikicode_restoreform');
        echo '<form class="wiki_restore_yes" action="' . $restoreurl . '" method="post" id="restoreversion">';
        echo '<div><input type="submit" name="confirm" value="' . get_string('yes') . '" /></div>';
        echo '</form>';
        echo '<form class="wiki_restore_no" action="' . $return . '" method="post">';
        echo '<div><input type="submit" name="norestore" value="' . get_string('no') . '" /></div>';
        echo '</form>';
        print_container_end();
    }
}
/**
 * Class that models the behavior of wiki's delete comment confirmation page
 *
 */
class page_wikicode_deletecomment extends page_wikicode {
    private $commentid;

    function print_header() {
        parent::print_header();
        $this->print_pagetitle();
    }

    function print_content() {
        $this->printconfirmdelete();
    }

    function set_url() {
        global $PAGE;
        $PAGE->set_url('/mod/wikicode/instancecomments.php', array('pageid' => $this->page->id, 'commentid' => $this->commentid));
    }

    public function set_action($action, $commentid, $content) {
        $this->action = $action;
        $this->commentid = $commentid;
        $this->content = $content;
    }

    protected function create_navbar() {
        global $PAGE;

        parent::create_navbar();
        $PAGE->navbar->add(get_string('deletecommentcheck', 'wikicode'));
    }

    protected function setup_tabs() {
        parent::setup_tabs(array('linkedwhenactive' => 'comments', 'activetab' => 'comments'));
    }

    /**
     * Prints the comment deletion confirmation form
     *
     * @param page $page The page whose version will be restored
     * @param int  $versionid The version to be restored
     * @param bool $confirm If false, shows a yes/no confirmation page.
     *     If true, restores the old version and redirects the user to the 'view' tab.
     */
    private function printconfirmdelete() {
        global $OUTPUT;

        $strdeletecheck = get_string('deletecommentcheck', 'wikicode');
        $strdeletecheckfull = get_string('deletecommentcheckfull', 'wikicode');

        //ask confirmation
        $optionsyes = array('confirm'=>1, 'pageid'=>$this->page->id, 'action'=>'delete', 'commentid'=>$this->commentid, 'sesskey'=>sesskey());
        $deleteurl = new moodle_url('/mod/wikicode/instancecomments.php', $optionsyes);
        $return = new moodle_url('/mod/wikicode/comments.php', array('pageid'=>$this->page->id));

        echo $OUTPUT->heading($strdeletecheckfull);
        print_container_start(false, 'wikicode_deletecommentform');
        echo '<form class="wiki_deletecomment_yes" action="' . $deleteurl . '" method="post" id="deletecomment">';
        echo '<div><input type="submit" name="confirmdeletecomment" value="' . get_string('yes') . '" /></div>';
        echo '</form>';
        echo '<form class="wiki_deletecomment_no" action="' . $return . '" method="post">';
        echo '<div><input type="submit" name="norestore" value="' . get_string('no') . '" /></div>';
        echo '</form>';
        print_container_end();
    }
}

/**
 * Class that models the behavior of wiki's
 * save page
 *
 */
class page_wikicode_save extends page_wikicode_edit {

    private $newcontent;

    function print_header() {
    }

    function print_content() {
        global $PAGE;

        $context = get_context_instance(CONTEXT_MODULE, $PAGE->cm->id);
        require_capability('mod/wikicode:editpage', $context, NULL, true, 'noeditpermission', 'wikicode');

        $this->print_save();
    }

    function set_newcontent($newcontent) {
        $this->newcontent = $newcontent;
    }

    protected function set_session_url() {
    }

    protected function print_save() {
        global $CFG, $USER, $OUTPUT, $PAGE;

        $url = $CFG->wwwroot . '/mod/wikicode/edit.php?pageid=' . $this->page->id;
        if (!empty($this->section)) {
            $url .= "&section=" . urlencode($this->section);
        }

        $params = array('attachmentoptions' => page_wikicode_edit::$attachmentoptions, 'format' => $this->format, 'version' => $this->versionnumber);

        if ($this->format != 'html') {
            $params['fileitemid'] = $this->page->id;
            $params['contextid']  = $this->modcontext->id;
            $params['component']  = 'mod_wikicode';
            $params['filearea']   = 'attachments';
        }

        $form = new mod_wikicode_edit_form($url, $params);

        $save = false;
        $data = false;
        if ($data = $form->get_data()) {
            if ($this->format == 'html') {
                $data = file_postupdate_standard_editor($data, 'newcontent', page_wikicode_edit::$attachmentoptions, $this->modcontext, 'mod_wikicode', 'attachments', $this->subwiki->id);
            }

            if (isset($this->section)) {
                $save = wikicode_save_section($this->page, $this->section, $data->newcontent, $USER->id);
            } else {
                $save = wikicode_save_page($this->page, $data->newcontent, $USER->id);
            }
        }

        if ($save && $data) {
            if (!empty($CFG->usetags)) {
                tag_set('wikicode_pages', $this->page->id, $data->tags);
            }

            $message = '<p>' . get_string('saving', 'wikicode') . '</p>';

            if (!empty($save['sections'])) {
                foreach ($save['sections'] as $s) {
                    $message .= '<p>' . get_string('repeatedsection', 'wikicode', $s) . '</p>';
                }
            }

            if ($this->versionnumber + 1 != $save['version']) {
                $message .= '<p>' . get_string('wrongversionsave', 'wikicode') . '</p>';
            }

            if (isset($errors) && !empty($errors)) {
                foreach ($errors as $e) {
                    $message .= "<p>" . get_string('filenotuploadederror', 'wikicode', $e->get_filename()) . "</p>";
                }
            }

            //deleting old locks
            wikicode_delete_locks($this->page->id, $USER->id, $this->section);

            redirect($CFG->wwwroot . '/mod/wikicode/edit.php?pageid=' . $this->page->id);
        } else {
            print_error('savingerror', 'wikicode');
        }
    }
}

/**
 * Class that models the behavior of wiki's view an old version of a page
 *
 */
class page_wikicode_viewversion extends page_wikicode {

    private $version;

    function print_header() {
        parent::print_header();
        $this->print_pagetitle();
    }

    function print_content() {
        global $PAGE;

        require_capability('mod/wikicode:viewpage', $this->modcontext, NULL, true, 'noviewpagepermission', 'wikicode');

        $this->print_version_view();
    }

    function set_url() {
        global $PAGE, $CFG;
        $PAGE->set_url($CFG->wwwroot . '/mod/wikicode/viewversion.php', array('pageid' => $this->page->id, 'versionid' => $this->version->id));
    }

    function set_versionid($versionid) {
        $this->version = wikicode_get_version($versionid);
    }

    protected function create_navbar() {
        global $PAGE, $CFG;

        parent::create_navbar();
        $PAGE->navbar->add(get_string('history', 'wikicode'), $CFG->wwwroot . '/mod/wikicode/history.php?pageid' . $this->page->id);
        $PAGE->navbar->add(get_string('versionnum', 'wikicode', $this->version->version));
    }

    protected function setup_tabs() {
        parent::setup_tabs(array('linkedwhenactive' => 'history', 'activetab' => 'history', 'inactivetabs' => array('edit')));
    }

    /**
     * Given an old page version, output the version content
     *
     * @global object $CFG
     * @global object $OUTPUT
     * @global object $PAGE
     */
    private function print_version_view() {
        global $CFG, $OUTPUT, $PAGE;
        $pageversion = wikicode_get_version($this->version->id);

        if ($pageversion) {
            $restorelink = new moodle_url('/mod/wikicode/restoreversion.php', array('pageid' => $this->page->id, 'versionid' => $this->version->id));
            echo $OUTPUT->heading(get_string('viewversion', 'wikicode', $pageversion->version) . '<br />' . html_writer::link($restorelink->out(false), '(' . get_string('restorethis', 'wikicode') . ')', array('class' => 'wikicode_restore')) . '&nbsp;', 4);
            $userinfo = wikicode_get_user_info($pageversion->userid);
            $heading = '<p><strong>' . get_string('modified', 'wikicode') . ':</strong>&nbsp;' . userdate($pageversion->timecreated, get_string('strftimedatetime', 'langconfig'));
            $viewlink = new moodle_url('/user/view.php', array('id' => $userinfo->id));
            $heading .= '&nbsp;&nbsp;&nbsp;<strong>' . get_string('user') . ':</strong>&nbsp;' . html_writer::link($viewlink->out(false), fullname($userinfo));
            $heading .= '&nbsp;&nbsp;&rarr;&nbsp;' . $OUTPUT->user_picture(wikicode_get_user_info($pageversion->userid), array('popup' => true)) . '</p>';
            print_container($heading, false, 'mdl-align wikicode_modifieduser wikicode_headingtime');
            $options = array('swid' => $this->subwiki->id, 'pretty_print' => true, 'pageid' => $this->page->id);

            $pageversion->content = file_rewrite_pluginfile_urls(wikicode_remove_tags($pageversion->content), 'pluginfile.php', $this->modcontext->id, 'mod_wikicode', 'attachments', $this->subwiki->id);
    		$content = format_text($pageversion->content, FORMAT_PLAIN, array('overflowdiv'=>true));
			
            echo $OUTPUT->box($content, 'generalbox wikicode_contentbox');

        } else {
            print_error('versionerror', 'wikicode');
        }
    }
}

class page_wikicode_confirmrestore extends page_wikicode_save {

    private $version;

    function set_url() {
        global $PAGE, $CFG;
        $PAGE->set_url($CFG->wwwroot . '/mod/wikicode/viewversion.php', array('pageid' => $this->page->id, 'versionid' => $this->version->id));
    }

    function print_content() {
        global $CFG, $PAGE;

        require_capability('mod/wikicode:managewiki', $this->modcontext, NULL, true, 'nomanagewikipermission', 'wikicode');

        $version = wikicode_get_version($this->version->id);
        if (wikicode_restore_page($this->page, $version->content, $version->userid)) {
            redirect($CFG->wwwroot . '/mod/wikicode/view.php?pageid=' . $this->page->id, get_string('restoring', 'wikicode', $version->version), 3);
        } else {
            print_error('restoreerror', 'wikicode', $version->version);
        }
    }

    function set_versionid($versionid) {
        $this->version = wikicode_get_version($versionid);
    }
}

class page_wikicode_prettyview extends page_wikicode {

    function print_header() {
        global $CFG, $PAGE, $OUTPUT;
        $PAGE->set_pagelayout('embedded');
        echo $OUTPUT->header();

        echo '<h1 id="wiki_printable_title">' . format_string($this->title) . '</h1>';
    }

    function print_content() {
        global $PAGE;

        require_capability('mod/wikicode:viewpage', $this->modcontext, NULL, true, 'noviewpagepermission', 'wikicode');

        $this->print_pretty_view();
    }

    function set_url() {
        global $PAGE, $CFG;

        $PAGE->set_url($CFG->wwwroot . '/mod/wikicode/prettyview.php', array('pageid' => $this->page->id));
    }

    private function print_pretty_view() {
        $version = wikicode_get_current_version($this->page->id);

        $content = wikicode_parse_content($version->contentformat, $version->content, array('printable' => true, 'swid' => $this->subwiki->id, 'pageid' => $this->page->id, 'pretty_print' => true));

        echo '<div id="wiki_printable_content">';
        echo format_text($content['parsed_text'], FORMAT_HTML);
        echo '</div>';
    }
}

class page_wikicode_handlecomments extends page_wikicode {
    private $action;
    private $content;
    private $commentid;
    private $format;

    function print_header() {
        $this->set_url();
    }

    public function print_content() {
        global $CFG, $PAGE, $USER;

        if ($this->action == 'add') {
            if (has_capability('mod/wikicode:editcomment', $this->modcontext)) {
                $this->add_comment($this->content, $this->commentid);
            }
        } else if ($this->action == 'edit') {
            $comment = wikicode_get_comment($this->commentid);
            $edit = has_capability('mod/wikicode:editcomment', $this->modcontext);
            $owner = ($comment->userid == $USER->id);
            if ($owner && $edit) {
                $this->add_comment($this->content, $this->commentid);
            }
        } else if ($this->action == 'delete') {
            $comment = wikicode_get_comment($this->commentid);
            $manage = has_capability('mod/wikicode:managecomment', $this->modcontext);
            $owner = ($comment->userid == $USER->id);
            if ($owner || $manage) {
                $this->delete_comment($this->commentid);
                redirect($CFG->wwwroot . '/mod/wikicode/comments.php?pageid=' . $this->page->id, get_string('deletecomment', 'wikicode'), 2);
            }
        }

    }

    public function set_url() {
        global $PAGE, $CFG;
        $PAGE->set_url($CFG->wwwroot . '/mod/wikicode/comments.php', array('pageid' => $this->page->id));
    }

    public function set_action($action, $commentid, $content) {
        $this->action = $action;
        $this->commentid = $commentid;
        $this->content = $content;

        $version = wikicode_get_current_version($this->page->id);
        $format = $version->contentformat;

        $this->format = $format;
    }

    private function add_comment($content, $idcomment) {
        global $CFG, $PAGE;
        require_once($CFG->dirroot . "/mod/wikicode/locallib.php");

        $pageid = $this->page->id;

        wikicode_add_comment($this->modcontext, $pageid, $content, $this->format);

        if (!$idcomment) {
            redirect($CFG->wwwroot . '/mod/wikicode/comments.php?pageid=' . $pageid, get_string('createcomment', 'wikicode'), 2);
        } else {
            $this->delete_comment($idcomment);
            redirect($CFG->wwwroot . '/mod/wikicode/comments.php?pageid=' . $pageid, get_string('editingcomment', 'wikicode'), 2);
        }
    }

    private function delete_comment($commentid) {
        global $CFG, $PAGE;

        $pageid = $this->page->id;

        wikicode_delete_comment($commentid, $this->modcontext, $pageid);
    }

}

class page_wikicode_lock extends page_wikicode_edit {

    public function print_header() {
        $this->set_url();
    }

    protected function set_url() {
        global $PAGE, $CFG;

        $params = array('pageid' => $this->page->id);

        if ($this->section) {
            $params['section'] = $this->section;
        }

        $PAGE->set_url($CFG->wwwroot . '/mod/wikicode/lock.php', $params);
    }

    protected function set_session_url() {
    }

    public function print_content() {
        global $USER, $PAGE;

        require_capability('mod/wikicode:editpage', $this->modcontext, NULL, true, 'noeditpermission', 'wikicode');

        wikicode_set_lock($this->page->id, $USER->id, $this->section);
    }

    public function print_footer() {
    }
}

class page_wikicode_overridelocks extends page_wikicode_edit {
    function print_header() {
        $this->set_url();
    }

    function print_content() {
        global $CFG, $PAGE;

        require_capability('mod/wikicode:overridelock', $this->modcontext, NULL, true, 'nooverridelockpermission', 'wikicode');

        wikicode_delete_locks($this->page->id, null, $this->section, true, true);

        $args = "pageid=" . $this->page->id;

        if (!empty($this->section)) {
            $args .= "&section=" . urlencode($this->section);
        }

        redirect($CFG->wwwroot . '/mod/wikicode/edit.php?' . $args, get_string('overridinglocks', 'wikicode'), 2);
    }

    function set_url() {
        global $PAGE, $CFG;

        $params = array('pageid' => $this->page->id);

        if (!empty($this->section)) {
            $params['section'] = $this->section;
        }

        $PAGE->set_url($CFG->wwwroot . '/mod/wikicode/overridelocks.php', $params);
    }

    protected function set_session_url() {
    }

    private function print_overridelocks() {
        global $CFG;

        wikicode_delete_locks($this->page->id, null, $this->section, true, true);

        $args = "pageid=" . $this->page->id;

        if (!empty($this->section)) {
            $args .= "&section=" . urlencode($this->section);
        }

        redirect($CFG->wwwroot . '/mod/wikicode/edit.php?' . $args, get_string('overridinglocks', 'wikicode'), 2);
    }

}

/**
 * This class will let user to delete wiki pages and page versions
 *
 */
class page_wikicode_admin extends page_wikicode {

    public $view, $action;
    public $listorphan = false;

    /**
     * Constructor
     *
     * @global object $PAGE
     * @param mixed $wiki instance of wiki
     * @param mixed $subwiki instance of subwiki
     * @param stdClass $cm course module
     */
    function __construct($wiki, $subwiki, $cm) {
        global $PAGE;
        parent::__construct($wiki, $subwiki, $cm);
        $PAGE->requires->js_init_call('M.mod_wikicode.deleteversion', null, true);
    }

    /**
     * Prints header for wiki page
     */
    function print_header() {
        parent::print_header();
        $this->print_pagetitle();
    }

    /**
     * This function will display administration view to users with managewiki capability
     */
    function print_content() {
        //make sure anyone trying to access this page has managewiki capabilities
        require_capability('mod/wikicode:managewiki', $this->modcontext, NULL, true, 'noviewpagepermission', 'wikicode');

        //update wiki cache if timedout
        $page = $this->page;
        if ($page->timerendered + wikicode_REFRESH_CACHE_TIME < time()) {
            $fresh = wikicode_refresh_cachedcontent($page);
            $page = $fresh['page'];
        }

        //dispaly admin menu
        echo $this->wikioutput->menu_admin($this->page->id, $this->view);

        //Display appropriate admin view
        switch ($this->view) {
            case 1: //delete page view
                $this->print_delete_content($this->listorphan);
                break;
            case 2: //delete version view
                $this->print_delete_version();
                break;
            default: //default is delete view
                $this->print_delete_content($this->listorphan);
                break;
        }
    }

    /**
     * Sets admin view option
     *
     * @param int $view page view id
     * @param bool $listorphan is only valid for view 1.
     */
    public function set_view($view, $listorphan = true) {
        $this->view = $view;
        $this->listorphan = $listorphan;
    }

    /**
     * Sets page url
     *
     * @global object $PAGE
     * @global object $CFG
     */
    function set_url() {
        global $PAGE, $CFG;
        $PAGE->set_url($CFG->wwwroot . '/mod/wikicode/admin.php', array('pageid' => $this->page->id));
    }

    /**
     * sets navigation bar for the page
     *
     * @global object $PAGE
     */
    protected function create_navbar() {
        global $PAGE;

        parent::create_navbar();
        $PAGE->navbar->add(get_string('admin', 'wikicode'));
    }

    /**
     * Show wiki page delete options
     *
     * @param bool $showorphan
     */
    protected function print_delete_content($showorphan = true) {
        $contents = array();
        $table = new html_table();
        $table->head = array('','Page name');
        $table->attributes['class'] = 'generaltable mdl-align';
        $swid = $this->subwiki->id;
        if ($showorphan) {
            if ($orphanedpages = wikicode_get_orphaned_pages($swid)) {
                $this->add_page_delete_options($orphanedpages, $swid, $table);
            } else {
                $table->data[] = array('', get_string('noorphanedpages', 'wikicode'));
            }
        } else {
            if ($pages = wikicode_get_page_list($swid)) {
                $this->add_page_delete_options($pages, $swid, $table);
            } else {
                $table->data[] = array('', get_string('nopages', 'wikicode'));
            }
        }

        ///Print the form
        echo html_writer::start_tag('form', array(
                                                'action' => new moodle_url('/mod/wikicode/admin.php'),
                                                'method' => 'post'));
        echo html_writer::tag('div', html_writer::empty_tag('input', array(
                                                                         'type'  => 'hidden',
                                                                         'name'  => 'pageid',
                                                                         'value' => $this->page->id)));

        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'option', 'value' => $this->view));
        echo html_writer::table($table);
        echo html_writer::start_tag('div', array('class' => 'mdl-align'));
        if (!$showorphan) {
            echo html_writer::empty_tag('input', array(
                                                     'type'    => 'submit',
                                                     'class'   => 'wikicode_form-button',
                                                     'value'   => get_string('listorphan', 'wikicode'),
                                                     'sesskey' => sesskey()));
        } else {
            echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'listall', 'value'=>'1'));
            echo html_writer::empty_tag('input', array(
                                                     'type'    => 'submit',
                                                     'class'   => 'wikicode_form-button',
                                                     'value'   => get_string('listall', 'wikicode'),
                                                     'sesskey' => sesskey()));
        }
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('form');
    }

    /**
     * helper function for print_delete_content. This will add data to the table.
     *
     * @global object $OUTPUT
     * @param array $pages objects of wiki pages in subwiki
     * @param int $swid id of subwiki
     * @param object $table reference to the table in which data needs to be added
     */
    protected function add_page_delete_options($pages, $swid, &$table) {
        global $OUTPUT;
        foreach ($pages as $page) {
            $link = wikicode_parser_link($page->title, array('swid' => $swid));
            $class = ($link['new']) ? 'class="wiki_newentry"' : '';
            $pagelink = '<a href="' . $link['url'] . '"' . $class . '>' . format_string($link['content']) . '</a>';
            $urledit = new moodle_url('/mod/wikicode/edit.php', array('pageid' => $page->id, 'sesskey' => sesskey()));
            $urldelete = new moodle_url('/mod/wikicode/admin.php', array(
                                                                   'pageid'  => $this->page->id,
                                                                   'delete'  => $page->id,
                                                                   'option'  => $this->view,
                                                                   'listall' => !$this->listorphan?'1': '',
                                                                   'sesskey' => sesskey()));

            $editlinks = $OUTPUT->action_icon($urledit, new pix_icon('t/edit', get_string('edit')));
            $editlinks .= $OUTPUT->action_icon($urldelete, new pix_icon('t/delete', get_string('delete')));
            $table->data[] = array($editlinks, $pagelink);
        }
    }

    /**
     * Prints lists of versions which can be deleted
     *
     * @global object $OUTPUT
     */
    private function print_delete_version() {
        global $OUTPUT;
        $pageid = $this->page->id;

        // versioncount is the latest version
        $versioncount = wikicode_count_wikicode_page_versions($pageid) - 1;
        $versions = wikicode_get_wikicode_page_versions($pageid, 0, $versioncount);

        // We don't want version 0 to be displayed
        // version 0 is blank page
        if (end($versions)->version == 0) {
            array_pop($versions);
        }

        $contents = array();
        $version0page = wikicode_get_wikicode_page_version($this->page->id, 0);
        $creator = wikicode_get_user_info($version0page->userid);
        $a = new stdClass();
        $a->date = userdate($this->page->timecreated, get_string('strftimedaydatetime', 'langconfig'));
        $a->username = fullname($creator);
        echo $OUTPUT->heading(get_string('createddate', 'wikicode', $a), 4, 'wikicode_headingtime');
        if ($versioncount > 0) {
            /// If there is only one version, we don't need radios nor forms
            if (count($versions) == 1) {
                $row = array_shift($versions);
                $username = wikicode_get_user_info($row->userid);
                $picture = $OUTPUT->user_picture($username);
                $date = userdate($row->timecreated, get_string('strftimedate', 'langconfig'));
                $time = userdate($row->timecreated, get_string('strftimetime', 'langconfig'));
                $versionid = wikicode_get_version($row->id);
                $versionlink = new moodle_url('/mod/wikicode/viewversion.php', array('pageid' => $pageid, 'versionid' => $versionid->id));
                $userlink = new moodle_url('/user/view.php', array('id' => $username->id));
                $picturelink = $picture . html_writer::link($userlink->out(false), fullname($username));
                $historydate = $OUTPUT->container($date, 'wikicode_histdate');
                $contents[] = array('', html_writer::link($versionlink->out(false), $row->version), $picturelink, $time, $historydate);

                //Show current version
                $table = new html_table();
                $table->head = array('', get_string('version'), get_string('user'), get_string('modified'), '');
                $table->data = $contents;
                $table->attributes['class'] = 'mdl-align';

                echo html_writer::table($table);
            } else {
                $lastdate = '';
                $rowclass = array();

                foreach ($versions as $version) {
                    $user = wikicode_get_user_info($version->userid);
                    $picture = $OUTPUT->user_picture($user, array('popup' => true));
                    $date = userdate($version->timecreated, get_string('strftimedate'));
                    if ($date == $lastdate) {
                        $date = '';
                        $rowclass[] = '';
                    } else {
                        $lastdate = $date;
                        $rowclass[] = 'wikicode_histnewdate';
                    }

                    $time = userdate($version->timecreated, get_string('strftimetime', 'langconfig'));
                    $versionid = wikicode_get_version($version->id);
                    if ($versionid) {
                        $url = new moodle_url('/mod/wikicode/viewversion.php', array('pageid' => $pageid, 'versionid' => $versionid->id));
                        $viewlink = html_writer::link($url->out(false), $version->version);
                    } else {
                        $viewlink = $version->version;
                    }

                    $userlink = new moodle_url('/user/view.php', array('id' => $version->userid));
                    $picturelink = $picture . html_writer::link($userlink->out(false), fullname($user));
                    $historydate = $OUTPUT->container($date, 'wikicode_histdate');
                    $radiofromelement = $this->choose_from_radio(array($version->version  => null), 'fromversion', 'M.mod_wikicode.deleteversion()', $versioncount, true);
                    $radiotoelement = $this->choose_from_radio(array($version->version  => null), 'toversion', 'M.mod_wikicode.deleteversion()', $versioncount, true);
                    $contents[] = array( $radiofromelement . $radiotoelement, $viewlink, $picturelink, $time, $historydate);
                }

                $table = new html_table();
                $table->head = array(get_string('deleteversions', 'wikicode'), get_string('version'), get_string('user'), get_string('modified'), '');
                $table->data = $contents;
                $table->attributes['class'] = 'generaltable mdl-align';
                $table->rowclasses = $rowclass;

                ///Print the form
                echo html_writer::start_tag('form', array('action'=>new moodle_url('/mod/wikicode/admin.php'), 'method' => 'post'));
                echo html_writer::tag('div', html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'pageid', 'value' => $pageid)));
                echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'option', 'value' => $this->view));
                echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' =>  sesskey()));
                echo html_writer::table($table);
                echo html_writer::start_tag('div', array('class' => 'mdl-align'));
                echo html_writer::empty_tag('input', array('type' => 'submit', 'class' => 'wikicode_form-button', 'value' => get_string('deleteversions', 'wikicode')));
                echo html_writer::end_tag('div');
                echo html_writer::end_tag('form');
            }
        } else {
            print_string('nohistory', 'wikicode');
        }
    }

    /**
     * Given an array of values, creates a group of radio buttons to be part of a form
     * helper function for print_delete_version
     *
     * @param array  $options  An array of value-label pairs for the radio group (values as keys).
     * @param string $name     Name of the radiogroup (unique in the form).
     * @param string $onclick  Function to be executed when the radios are clicked.
     * @param string $checked  The value that is already checked.
     * @param bool   $return   If true, return the HTML as a string, otherwise print it.
     *
     * @return mixed If $return is false, returns nothing, otherwise returns a string of HTML.
     */
    private function choose_from_radio($options, $name, $onclick = '', $checked = '', $return = false) {

        static $idcounter = 0;

        if (!$name) {
            $name = 'unnamed';
        }

        $output = '<span class="radiogroup ' . $name . "\">\n";

        if (!empty($options)) {
            $currentradio = 0;
            foreach ($options as $value => $label) {
                $htmlid = 'auto-rb' . sprintf('%04d', ++$idcounter);
                $output .= ' <span class="radioelement ' . $name . ' rb' . $currentradio . "\">";
                $output .= '<input name="' . $name . '" id="' . $htmlid . '" type="radio" value="' . $value . '"';
                if ($value == $checked) {
                    $output .= ' checked="checked"';
                }
                if ($onclick) {
                    $output .= ' onclick="' . $onclick . '"';
                }
                if ($label === '') {
                    $output .= ' /> <label for="' . $htmlid . '">' . $value . '</label></span>' . "\n";
                } else {
                    $output .= ' /> <label for="' . $htmlid . '">' . $label . '</label></span>' . "\n";
                }
                $currentradio = ($currentradio + 1) % 2;
            }
        }

        $output .= '</span>' . "\n";

        if ($return) {
            return $output;
        } else {
            echo $output;
        }
    }
}

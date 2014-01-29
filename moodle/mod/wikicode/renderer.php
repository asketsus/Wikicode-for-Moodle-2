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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Moodle Wikicode 2.0 Renderer
 *
 * @package   mod-wikicode
 * @copyright 2010 Dongsheng Cai <dongsheng@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class mod_wikicode_renderer extends plugin_renderer_base {
    public function page_index() {
        global $CFG;
        $output = '';
        // Checking wiki instance
        if (!$wiki = wikicode_get_wiki($this->page->cm->instance)) {
            return false;
        }

        // @TODO: Fix call to wikicode_get_subwiki_by_group
        $gid = groups_get_activity_group($this->page->cm);
        $gid = !empty($gid) ? $gid : 0;
        if (!$subwiki = wikicode_get_subwiki_by_group($this->page->cm->instance, $gid)) {
            return false;
        }
        $swid = $subwiki->id;
        $pages = wikicode_get_page_list($swid);
        $selectoptions = array();
        foreach ($pages as $page) {
            $selectoptions[$page->id] = $page->title;
        }
        $label = get_string('pageindex', 'wikicode') . ': ';
        $select = new single_select(new moodle_url('/mod/wikicode/view.php'), 'pageid', $selectoptions);
        $select->label = $label;
        return $this->output->container($this->output->render($select), 'wikicode_index');
    }

    public function search_result($records, $subwiki) {
        global $CFG, $PAGE;
        $table = new html_table();
        $context = get_context_instance(CONTEXT_MODULE, $PAGE->cm->id);
        $strsearchresults = get_string('searchresult', 'wikicode');
        $totalcount = count($records);
        $html = $this->output->heading("$strsearchresults $totalcount");
        foreach ($records as $page) {
            $table->head = array('title' => format_string($page->title) . ' (' . html_writer::link($CFG->wwwroot . '/mod/wikicode/view.php?pageid=' . $page->id, get_string('view', 'wikicode')) . ')');
            $table->align = array('title' => 'left');
            $table->width = '100%';
            $table->data = array(array(file_rewrite_pluginfile_urls(format_text($page->cachedcontent, FORMAT_HTML), 'pluginfile.php', $context->id, 'mod_wikicode', 'attachments', $subwiki->id)));
            $table->colclasses = array('wikisearchresults');
            $html .= html_writer::table($table);
        }
        $html = html_writer::tag('div', $html, array('class'=>'no-overflow'));
        return $this->output->container($html);
    }

    public function diff($pageid, $old, $new, $options = array()) {
        global $CFG;
        if (!empty($options['total'])) {
            $total = $options['total'];
        } else {
            $total = 0;
        }
        $diff1 = format_text($old->diff, FORMAT_HTML, array('overflowdiv'=>true));
        $diff2 = format_text($new->diff, FORMAT_HTML, array('overflowdiv'=>true));
        $strdatetime = get_string('strftimedatetime', 'langconfig');

        $olduser = $old->user;
        $versionlink = new moodle_url('/mod/wikicode/viewversion.php', array('pageid' => $pageid, 'versionid' => $old->id));
        $restorelink = new moodle_url('/mod/wikicode/restoreversion.php', array('pageid' => $pageid, 'versionid' => $old->id));
        $userlink = new moodle_url('/user/view.php', array('id' => $olduser->id));
        // view version link
        $oldversionview = ' ';
        $oldversionview .= html_writer::link($versionlink->out(false), get_string('view', 'wikicode'), array('class' => 'wikicode_diffview'));
        $oldversionview .= ' ';
        // restore version link
        $oldversionview .= html_writer::link($restorelink->out(false), get_string('restore', 'wikicode'), array('class' => 'wikicode_diffview'));

        // userinfo container
        $oldheading = $this->output->container_start('wikicode_diffuserleft');
        // username
        $oldheading .= html_writer::link($CFG->wwwroot . '/user/view.php?id=' . $olduser->id, fullname($olduser)) . '&nbsp;';
        // user picture
        $oldheading .= html_writer::link($userlink->out(false), $this->output->user_picture($olduser, array('popup' => true)), array('class' => 'notunderlined'));
        $oldheading .= $this->output->container_end();

        // version number container
        $oldheading .= $this->output->container_start('wikicode_diffversion');
        $oldheading .= get_string('version') . ' ' . $old->version . $oldversionview;
        $oldheading .= $this->output->container_end();
        // userdate container
        $oldheading .= $this->output->container_start('wikicode_difftime');
        $oldheading .= userdate($old->timecreated, $strdatetime);
        $oldheading .= $this->output->container_end();

        $newuser = $new->user;
        $versionlink = new moodle_url('/mod/wikicode/viewversion.php', array('pageid' => $pageid, 'versionid' => $new->id));
        $restorelink = new moodle_url('/mod/wikicode/restoreversion.php', array('pageid' => $pageid, 'versionid' => $new->id));
        $userlink = new moodle_url('/user/view.php', array('id' => $newuser->id));

        $newversionview = ' ';
        $newversionview .= html_writer::link($versionlink->out(false), get_string('view', 'wikicode'), array('class' => 'wikicode_diffview'));
        // new user info
        $newheading = $this->output->container_start('wikicode_diffuserright');
        $newheading .= $this->output->user_picture($newuser, array('popup' => true));

        $newheading .= html_writer::link($userlink->out(false), fullname($newuser), array('class' => 'notunderlined'));
        $newheading .= $this->output->container_end();

        // version
        $newheading .= $this->output->container_start('wikicode_diffversion');
        $newheading .= get_string('version') . '&nbsp;' . $new->version . $newversionview;
        $newheading .= $this->output->container_end();
        // userdate
        $newheading .= $this->output->container_start('wikicode_difftime');
        $newheading .= userdate($new->timecreated, $strdatetime);
        $newheading .= $this->output->container_end();

        $oldheading = html_writer::tag('div', $oldheading, array('class'=>'wiki-diff-heading header clearfix'));
        $newheading = html_writer::tag('div', $newheading, array('class'=>'wiki-diff-heading header clearfix'));

        $output  = '';
        $output .= html_writer::start_tag('div', array('class'=>'wiki-diff-container clearfix'));
        $output .= html_writer::tag('div', $oldheading.$diff1, array('class'=>'wiki-diff-leftside'));
        $output .= html_writer::tag('div', $newheading.$diff2, array('class'=>'wiki-diff-rightside'));
        $output .= html_writer::end_tag('div');

        if (!empty($total)) {
            $output .= '<div class="wikicode_diff_paging">';
            $output .= $this->output->container($this->diff_paging_bar(1, $new->version - 1, $old->version, $CFG->wwwroot . '/mod/wikicode/diff.php?pageid=' . $pageid . '&amp;comparewith=' . $new->version . '&amp;', 'compare', false, true), 'wikicode_diff_oldpaging');
            $output .= $this->output->container($this->diff_paging_bar($old->version + 1, $total, $new->version, $CFG->wwwroot . '/mod/wikicode/diff.php?pageid=' . $pageid . '&amp;compare=' . $old->version . '&amp;', 'comparewith', false, true), 'wikicode_diff_newpaging');
            $output .= '</div>';
        }

        return $output;
    }

    /**
     * Prints a single paging bar to provide access to other versions
     *
     * @param int $minpage First page to be displayed in the bar
     * @param int $maxpage Last page to be displayed in the bar
     * @param int $page The page you are currently viewing
     * @param mixed $baseurl If this  is a string then it is the url which will be appended with $pagevar, an equals sign and the page number.
     *                          If this is a moodle_url object then the pagevar param will be replaced by the page no, for each page.
     * @param string $pagevar This is the variable name that you use for the page number in your code (ie. 'tablepage', 'blogpage', etc)
     * @param bool $nocurr do not display the current page as a link
     * @param bool $return whether to return an output string or echo now
     * @return bool or string
     */
    public function diff_paging_bar($minpage, $maxpage, $page, $baseurl, $pagevar = 'page', $nocurr = false) {
        $totalcount = $maxpage - $minpage;
        $maxdisplay = 2;
        $output = '';

        if ($totalcount > 0) {
            $output .= '<div class="paging">';
            $output .= get_string('version', 'wikicode') . ':';
            if ($page - $minpage > 0) {
                $pagenum = $page - 1;
                if (!is_a($baseurl, 'moodle_url')) {
                    $output .= '&nbsp;(<a class="previous" href="' . $baseurl . $pagevar . '=' . $pagenum . '">' . get_string('previous') . '</a>)&nbsp;';
                } else {
                    $output .= '&nbsp;(<a class="previous" href="' . $baseurl->out(false, array($pagevar => $pagenum)) . '">' . get_string('previous') . '</a>)&nbsp;';
                }
            }

            if ($page - $minpage > 4) {
                $startpage = $page - 3;
                if (!is_a($baseurl, 'moodle_url')) {
                    $output .= '&nbsp;<a href="' . $baseurl . $pagevar . '=' . $minpage . '">' . $minpage . '</a>&nbsp;...';
                } else {
                    $output .= '&nbsp;<a href="' . $baseurl->out(false, array($pagevar => $minpage)) . '">' . $minpage . '</a>&nbsp;...';
                }
            } else {
                $startpage = $minpage;
            }
            $currpage = $startpage;
            $displaycount = 0;
            while ($displaycount < $maxdisplay and $currpage <= $maxpage) {
                if ($page == $currpage && empty($nocurr)) {
                    $output .= '&nbsp;&nbsp;' . $currpage;
                } else {
                    if (!is_a($baseurl, 'moodle_url')) {
                        $output .= '&nbsp;&nbsp;<a href="' . $baseurl . $pagevar . '=' . $currpage . '">' . $currpage . '</a>';
                    } else {
                        $output .= '&nbsp;&nbsp;<a href="' . $baseurl->out(false, array($pagevar => $currpage)) . '">' . $currpage . '</a>';
                    }

                }
                $displaycount++;
                $currpage++;
            }
            if ($currpage < $maxpage) {
                if (!is_a($baseurl, 'moodle_url')) {
                    $output .= '&nbsp;...<a href="' . $baseurl . $pagevar . '=' . $maxpage . '">' . $maxpage . '</a>&nbsp;';
                } else {
                    $output .= '&nbsp;...<a href="' . $baseurl->out(false, array($pagevar => $maxpage)) . '">' . $maxpage . '</a>&nbsp;';
                }
            } else if ($currpage == $maxpage) {
                if (!is_a($baseurl, 'moodle_url')) {
                    $output .= '&nbsp;&nbsp;<a href="' . $baseurl . $pagevar . '=' . $currpage . '">' . $currpage . '</a>';
                } else {
                    $output .= '&nbsp;&nbsp;<a href="' . $baseurl->out(false, array($pagevar => $currpage)) . '">' . $currpage . '</a>';
                }
            }
            $pagenum = $page + 1;
            if ($page != $maxpage) {
                if (!is_a($baseurl, 'moodle_url')) {
                    $output .= '&nbsp;&nbsp;(<a class="next" href="' . $baseurl . $pagevar . '=' . $pagenum . '">' . get_string('next') . '</a>)';
                } else {
                    $output .= '&nbsp;&nbsp;(<a class="next" href="' . $baseurl->out(false, array($pagevar => $pagenum)) . '">' . get_string('next') . '</a>)';
                }
            }
            $output .= '</div>';
        }

        return $output;
    }
    public function wikicode_info() {
        global $PAGE;
        return $this->output->box(format_module_intro('wikicode', $this->page->activityrecord, $PAGE->cm->id), 'generalbox', 'intro');
    }

    public function tabs($page, $tabitems, $options) {
        global $CFG;
        $tabs = array();
        $baseurl = $CFG->wwwroot . '/mod/wikicode/';
        $context = get_context_instance(CONTEXT_MODULE, $this->page->cm->id);

        $pageid = null;
        if (!empty($page)) {
            $pageid = $page->id;
        }

        $selected = $options['activetab'];

        // make specific tab linked even it is active
        if (!empty($options['linkedwhenactive'])) {
            $linked = $options['linkedwhenactive'];
        } else {
            $linked = '';
        }

        if (!empty($options['inactivetabs'])) {
            $inactive = $options['inactivetabs'];
        } else {
            $inactive = array();
        }

        foreach ($tabitems as $tab) {
            if ($tab == 'edit' && !has_capability('mod/wikicode:editpage', $context)) {
                continue;
            }
            if ($tab == 'comments' && !has_capability('mod/wikicode:viewcomment', $context)) {
                continue;
            }
            if ($tab == 'files' && !has_capability('mod/wikicode:viewpage', $context)) {
                continue;
            }
            if (($tab == 'view' || $tab == 'map' || $tab == 'history') && !has_capability('mod/wikicode:viewpage', $context)) {
                continue;
            }
            if ($tab == 'admin' && !has_capability('mod/wikicode:managewiki', $context)) {
                continue;
            }
            $link = $baseurl . $tab . '.php?pageid=' . $pageid;
            if ($linked == $tab) {
                $tabs[] = new tabobject($tab, $link, get_string($tab, 'wikicode'), '', true);
            } else {
                $tabs[] = new tabobject($tab, $link, get_string($tab, 'wikicode'));
            }
        }

        return print_tabs(array($tabs), $selected, $inactive, null, true);
    }

    public function prettyview_link($page) {
        $html = '';
        $link = new moodle_url('/mod/wikicode/prettyview.php', array('pageid' => $page->id));
        $html .= $this->output->container_start('wikicode_right');
        $html .= $this->output->action_link($link, get_string('prettyprint', 'wikicode'), new popup_action('click', $link));
        $html .= $this->output->container_end();
        return $html;
    }

    public function wikicode_print_subwiki_selector($wiki, $subwiki, $page, $pagetype = 'view') {
        global $CFG, $USER;
        require_once($CFG->dirroot . '/user/lib.php');
        switch ($pagetype) {
        case 'files':
            $baseurl = new moodle_url('/mod/wikicode/files.php');
            break;
        case 'view':
        default:
            $baseurl = new moodle_url('/mod/wikicode/view.php');
            break;
        }

        $cm = get_coursemodule_from_instance('wikicode', $wiki->id);
        $context = get_context_instance(CONTEXT_MODULE, $cm->id);
        // @TODO: A plenty of duplicated code below this lines.
        // Create private functions.
        switch (groups_get_activity_groupmode($cm)) {
        case NOGROUPS:
            if ($wiki->wikimode == 'collaborative') {
                // No need to print anything
                return;
            } else if ($wiki->wikimode == 'individual') {
                // We have private wikis here

                $view = has_capability('mod/wikicode:viewpage', $context);
                $manage = has_capability('mod/wikicode:managewiki', $context);

                // Only people with these capabilities can view all wikis
                if ($view && $manage) {
                    // @TODO: Print here a combo that contains all users.
                    $users = get_enrolled_users($context);
                    $options = array();
                    foreach ($users as $user) {
                        $options[$user->id] = fullname($user);
                    }

                    echo $this->output->container_start('wikicode_right');
                    $params = array('wid' => $wiki->id, 'title' => $page->title);
                    if ($pagetype == 'files') {
                        $params['pageid'] = $page->id;
                    }
                    $baseurl->params($params);
                    $name = 'uid';
                    $selected = $subwiki->userid;
                    echo $this->output->single_select($baseurl, $name, $options, $selected);
                    echo $this->output->container_end();
                }
                return;
            } else {
                // error
                return;
            }
        case SEPARATEGROUPS:
            if ($wiki->wikimode == 'collaborative') {
                // We need to print a select to choose a course group

                $params = array('wid'=>$wiki->id, 'title'=>$page->title);
                if ($pagetype == 'files') {
                    $params['pageid'] = $page->id;
                }
                $baseurl->params($params);

                echo $this->output->container_start('wikicode_right');
                groups_print_activity_menu($cm, $baseurl->out());
                echo $this->output->container_end();
                return;
            } else if ($wiki->wikimode == 'individual') {
                //  @TODO: Print here a combo that contains all users of that subwiki.
                $view = has_capability('mod/wikicode:viewpage', $context);
                $manage = has_capability('mod/wikicode:managewiki', $context);

                // Only people with these capabilities can view all wikis
                if ($view && $manage) {
                    $users = get_enrolled_users($context);
                    $options = array();
                    foreach ($users as $user) {
                        $groups = groups_get_all_groups($cm->course, $user->id);
                        if (!empty($groups)) {
                            foreach ($groups as $group) {
                                $options[$group->id][$group->name][$group->id . '-' . $user->id] = fullname($user);
                            }
                        } else {
                            $name = get_string('notingroup', 'wikicode');
                            $options[0][$name]['0' . '-' . $user->id] = fullname($user);
                        }
                    }
                } else {
                    $group = groups_get_group($subwiki->groupid);
                    $users = groups_get_members($subwiki->groupid);
                    foreach ($users as $user) {
                        $options[$group->id][$group->name][$group->id . '-' . $user->id] = fullname($user);
                    }
                }
                echo $this->output->container_start('wikicode_right');
                $params = array('wid' => $wiki->id, 'title' => $page->title);
                if ($pagetype == 'files') {
                    $params['pageid'] = $page->id;
                }
                $baseurl->params($params);
                $name = 'groupanduser';
                $selected = $subwiki->groupid . '-' . $subwiki->userid;
                echo $this->output->single_select($baseurl, $name, $options, $selected);
                echo $this->output->container_end();

                return;

            } else {
                // error
                return;
            }
        CASE VISIBLEGROUPS:
            if ($wiki->wikimode == 'collaborative') {
                // We need to print a select to choose a course group
                $params = array('wid'=>$wiki->id, 'title'=>urlencode($page->title));
                if ($pagetype == 'files') {
                    $params['pageid'] = $page->id;
                }
                $baseurl->params($params);

                echo $this->output->container_start('wikicode_right');
                groups_print_activity_menu($cm, $baseurl->out());
                echo $this->output->container_end();
                return;

            } else if ($wiki->wikimode == 'individual') {
                $users = get_enrolled_users($context);
                $options = array();
                foreach ($users as $user) {
                    $groups = groups_get_all_groups($cm->course, $user->id);
                    if (!empty($groups)) {
                        foreach ($groups as $group) {
                            $options[$group->id][$group->name][$group->id . '-' . $user->id] = fullname($user);
                        }
                    } else {
                        $name = get_string('notingroup', 'wikicode');
                        $options[0][$name]['0' . '-' . $user->id] = fullname($user);
                    }
                }

                echo $this->output->container_start('wikicode_right');
                $params = array('wid' => $wiki->id, 'title' => $page->title);
                if ($pagetype == 'files') {
                    $params['pageid'] = $page->id;
                }
                $baseurl->params($params);
                $name = 'groupanduser';
                $selected = $subwiki->groupid . '-' . $subwiki->userid;
                echo $this->output->single_select($baseurl, $name, $options, $selected);
                echo $this->output->container_end();

                return;

            } else {
                // error
                return;
            }
        default:
            // error
            return;

        }

    }

    function menu_map($pageid, $currentselect) {
        $options = array('contributions', 'links', 'orphaned', 'pageindex', 'pagelist', 'updatedpages');
        $items = array();
        foreach ($options as $opt) {
            $items[] = get_string($opt, 'wikicode');
        }
        $selectoptions = array();
        foreach ($items as $key => $item) {
            $selectoptions[$key + 1] = $item;
        }
        $select = new single_select(new moodle_url('/mod/wikicode/map.php', array('pageid' => $pageid)), 'option', $selectoptions, $currentselect);
        $select->label = get_string('mapmenu', 'wikicode') . ': ';
        return $this->output->container($this->output->render($select), 'midpad');
    }
    public function wikicode_files_tree($context, $subwiki) {
        return $this->render(new wikicode_files_tree($context, $subwiki));
    }
    public function render_wikicode_files_tree(wikicode_files_tree $tree) {
        if (empty($tree->dir['subdirs']) && empty($tree->dir['files'])) {
            $html = $this->output->box(get_string('nofilesavailable', 'repository'));
        } else {
            $htmlid = 'wikicode_files_tree_'.uniqid();
            $module = array('name'=>'mod_wikicode', 'fullpath'=>'/mod/wikicode/module.js');
            $this->page->requires->js_init_call('M.mod_wikicode.init_tree', array(false, $htmlid), false, $module);
            $html = '<div id="'.$htmlid.'">';
            $html .= $this->htmllize_tree($tree, $tree->dir);
            $html .= '</div>';
        }
        return $html;
    }

    function menu_admin($pageid, $currentselect) {
        $options = array('removepages', 'deleteversions');
        $items = array();
        foreach ($options as $opt) {
            $items[] = get_string($opt, 'wikicode');
        }
        $selectoptions = array();
        foreach ($items as $key => $item) {
            $selectoptions[$key + 1] = $item;
        }
        $select = new single_select(new moodle_url('/mod/wikicode/admin.php', array('pageid' => $pageid)), 'option', $selectoptions, $currentselect);
        $select->label = get_string('adminmenu', 'wikicode') . ': ';
        return $this->output->container($this->output->render($select), 'midpad');
    }

    /**
     * Internal function - creates htmls structure suitable for YUI tree.
     */
    protected function htmllize_tree($tree, $dir) {
        global $CFG;
        $yuiconfig = array();
        $yuiconfig['type'] = 'html';

        if (empty($dir['subdirs']) and empty($dir['files'])) {
            return '';
        }
        $result = '<ul>';
        foreach ($dir['subdirs'] as $subdir) {
            $image = $this->output->pix_icon("f/folder", $subdir['dirname'], 'moodle', array('class'=>'icon'));
            $result .= '<li yuiConfig=\''.json_encode($yuiconfig).'\'><div>'.$image.' '.s($subdir['dirname']).'</div> '.$this->htmllize_tree($tree, $subdir).'</li>';
        }
        foreach ($dir['files'] as $file) {
            $url = file_encode_url("$CFG->wwwroot/pluginfile.php", '/'.$tree->context->id.'/mod_wiki/attachments/' . $tree->subwiki->id . '/'. $file->get_filepath() . $file->get_filename(), true);
            $filename = $file->get_filename();
            $icon = mimeinfo("icon", $filename);
            $image = $this->output->pix_icon("f/$icon", $filename, 'moodle', array('class'=>'icon'));
            $result .= '<li yuiConfig=\''.json_encode($yuiconfig).'\'><div>'.$image.' '.html_writer::link($url, $filename).'</div></li>';
        }
        $result .= '</ul>';

        return $result;
    }
}

class wikicode_files_tree implements renderable {
    public $context;
    public $dir;
    public $subwiki;
    public function __construct($context, $subwiki) {
        $fs = get_file_storage();
        $this->context = $context;
        $this->subwiki = $subwiki;
        $this->dir = $fs->get_area_tree($context->id, 'mod_wikicode', 'attachments', $subwiki->id);
    }
}

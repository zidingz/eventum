<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005 MySQL AB                              |
// |                                                                      |
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License as published by |
// | the Free Software Foundation; either version 2 of the License, or    |
// | (at your option) any later version.                                  |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to:                           |
// |                                                                      |
// | Free Software Foundation, Inc.                                       |
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: Bryan Alsdorf <bryan@mysql.com>                             |
// +----------------------------------------------------------------------+
//
// @(#) $Id$
//

include_once(APP_INC_PATH . "class.user.php");

/**
 * Class to handle parsing content for links.
 * 
 * @author  Bryan Alsdorf <bryan@mysql.com>
 */
class Link_Filter
{
    /**
     * Returns information about a specific link filter.
     * 
     * @access  public
     * @param   integer $lfi_id The ID of the link filter to return info about.
     * @return  array An array of information.
     */
    function getDetails($lfi_id)
    {
        $sql = "SELECT
                    lfi_id,
                    lfi_description,
                    lfi_usr_role,
                    lfi_pattern,
                    lfi_replacement
                FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "link_filter
                WHERE
                    lfi_id = $lfi_id";
        $res = $GLOBALS["db_api"]->dbh->getRow($sql, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } elseif (count($res) > 0) {
            $sql = "SELECT
                        plf_prj_id
                    FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_link_filter
                    WHERE
                        plf_lfi_id = " . $res['lfi_id'];
            $projects = $GLOBALS["db_api"]->dbh->getCol($sql);
            if (PEAR::isError($projects)) {
                Error_Handler::logError(array($projects->getMessage(), $projects->getDebugInfo()), __FILE__, __LINE__);
                $projects = array();
            } elseif (is_null($projects)) {
                $projects = array();
            }
            $res["projects"] = $projects;
        }
        return $res;
    }


    /**
     * Lists the link filters currently in the system.
     * 
     * @return array An array of information.
     */
    function getList()
    {
        $sql = "SELECT
                    lfi_id,
                    lfi_description,
                    lfi_usr_role,
                    lfi_pattern,
                    lfi_replacement
                FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "link_filter
                ORDER BY
                    lfi_id";
        $res = $GLOBALS["db_api"]->dbh->getAll($sql, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        }
        for ($i = 0; $i < count($res); $i++) {
            $sql = "SELECT
                        plf_prj_id,
                        prj_title
                    FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_link_filter,
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project
                    WHERE
                        prj_id = plf_prj_id AND
                        plf_lfi_id = " . $res[$i]['lfi_id'];
            $projects = $GLOBALS["db_api"]->dbh->getAssoc($sql);
            if (PEAR::isError($projects)) {
                Error_Handler::logError(array($projects->getMessage(), $projects->getDebugInfo()), __FILE__, __LINE__);
                $projects = array();
            } elseif (is_null($projects)) {
                $projects = array();
            }
            $res[$i]["projects"] = array_keys($projects);
            $res[$i]["project_names"] = array_values($projects);
            $res[$i]["min_usr_role_name"] = User::getRole($res[$i]["lfi_usr_role"]);
        }
        return $res;
    }


    /**
     * Inserts a new link filter into the database.
     * 
     * @return integer 1 if insert was successful, -1 otherwise
     */
    function insert()
    {
        $sql = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "link_filter
                (
                    lfi_pattern,
                    lfi_replacement,
                    lfi_usr_role,
                    lfi_description
                ) VALUES (
                    '" . Misc::escapeString($_REQUEST["pattern"]) . "',
                    '" . Misc::escapeString($_REQUEST["replacement"]) . "',
                    '" . $_REQUEST["usr_role"] . "',
                    '" . Misc::escapeString($_REQUEST["description"]) . "'
                )";
        $res = $GLOBALS["db_api"]->dbh->query($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $lfi_id = $GLOBALS["db_api"]->get_last_insert_id();
            foreach ($_REQUEST["projects"] as $prj_id) {
                $sql = "INSERT INTO
                            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_link_filter
                        (
                            plf_prj_id,
                            plf_lfi_id
                        ) VALUES (
                            $prj_id,
                            $lfi_id
                        )";
                $res = $GLOBALS["db_api"]->dbh->query($sql);
                if (PEAR::isError($res)) {
                    Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                    return -1;
                }
            }
            return 1;
        }
    }


    /**
     * Removes link filters from the database
     * 
     * @return integer 1 if delete was successful, -1 otherwise.
     */
    function remove()
    {
        $sql = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "link_filter
                WHERE
                    lfi_id IN(" . join(',', $_REQUEST["items"]) . ")";
        $res = $GLOBALS["db_api"]->dbh->query($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        }
        $sql = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_link_filter
                WHERE
                    plf_lfi_id IN(" . join(',', $_REQUEST["items"]) . ")";
        $res = $GLOBALS["db_api"]->dbh->query($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        }
        return 1;
    }


    /**
     * Updates link filter information.
     * 
     * @return integer 1 if insert was successful, -1 otherwise
     */
    function update()
    {
        $sql = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "link_filter
                SET
                    lfi_pattern = '" . Misc::escapeString($_REQUEST["pattern"]) . "',
                    lfi_replacement = '" . Misc::escapeString($_REQUEST["replacement"]) . "',
                    lfi_usr_role = '" . $_REQUEST["usr_role"] . "',
                    lfi_description = '" . Misc::escapeString($_REQUEST["description"]) . "'
                WHERE
                    lfi_id = " . $_REQUEST["id"];
        $res = $GLOBALS["db_api"]->dbh->query($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $sql = "DELETE FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_link_filter
                    WHERE
                        plf_lfi_id = " . $_REQUEST["id"];
            $res = $GLOBALS["db_api"]->dbh->query($sql);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return -1;
            }
            foreach ($_REQUEST["projects"] as $prj_id) {
                $sql = "INSERT INTO
                            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_link_filter
                        (
                            plf_prj_id,
                            plf_lfi_id
                        ) VALUES (
                            $prj_id,
                            " . $_REQUEST["id"] . "
                        )";
                $res = $GLOBALS["db_api"]->dbh->query($sql);
                if (PEAR::isError($res)) {
                    Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                    return -1;
                }
            }
            return 1;
        }
    }


    /**
     * Processes text through all link filters.
     * 
     * @access  public
     * @param   integer $prj_id The ID of the project
     * @param   string $text The text to process
     * @param   string $class The CSS class to use on the actual links
     * @return  string The processed text.
     */
    function processText($prj_id, $text, $class = "link")
    {
        $filters = Link_Filter::getFilters($prj_id);
        
        if (count($filters) > 0) {
            foreach ($filters as $filter) {
                $text = preg_replace('/' . $filter[0] . '/i', $filter[1], $text);
            }
        }
        // process issue link seperatly since it has to do something special
        $text = Link_Filter::processIssueSpecificLinks($text);
        
        return $text;
    }


    /**
     * Callback function to be used from template class.
     * 
     * @access  public
     * @param   string $text The text to process
     * @return  string the processed text.
     */
    function activateLinks($text)
    {
        return Link_Filter::processText(Auth::getCurrentProject(), $text);
    }


    /**
     * Returns an array of patterns and replacements.
     * 
     * @access  private
     * @param   integer $prj_id The ID of the project
     * @return  array An array of patterns and replacements
     */
    function getFilters($prj_id)
    {
        static $filters;
        if (is_null($filters)) {
            $sql = "SELECT
                        lfi_pattern,
                        lfi_replacement
                    FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "link_filter,
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_link_filter
                    WHERE
                        lfi_id = plf_lfi_id AND
                        lfi_usr_role < " . Auth::getCurrentRole() . " AND
                        plf_prj_id = $prj_id
                    ORDER BY
                        lfi_id";
            $res = $GLOBALS["db_api"]->dbh->getAll($sql);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return array();
            }
            $filters = $res;
        }
        return $filters;
    }


    /**
     * Method used as a callback with the regular expression code that parses
     * text and creates links to other issues.
     *
     * @access  public
     * @param   array $matches Regular expression matches
     * @return  string The link to the appropriate issue
     */
    function callbackIssueLinks($matches)
    {
        include_once(APP_INC_PATH . "class.issue.php");
        // check if the issue is still open
        if (Issue::isClosed($matches[5])) {
            $class = 'closed_link';
        } else {
            $class = 'link';
        }
        $issue_title = Issue::getTitle($matches[5]);
        return "<a title=\"issue " . $matches[5] . " - $issue_title\" class=\"" . $class . "\" href=\"view.php?id=" . $matches[5] . "\">" . $matches[1] . $matches[2] . $matches[3] . $matches[4] . $matches[5] . "</a>";
    }


    /**
     * Method used to parse the given string for references to issues in the
     * system, and creating links to those if any are found.
     *
     * @access  private
     * @param   string $text The text to search against
     * @param   string $class The CSS class to use on the actual links
     * @return  string The parsed string
     */
    function processIssueSpecificLinks($text, $class = "link")
    {
        $text = preg_replace_callback("/(issue)(:)?(\s)(\#)?(\d+)/i", array('Link_Filter', 'callbackIssueLinks'), $text);
        return $text;
    }
}
?>

<?php
/**
 * Global Moderators 0.0.1

 * Copyright 2016 Matthew Rogowski

 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at

 ** http://www.apache.org/licenses/LICENSE-2.0

 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
**/

if(!defined("IN_MYBB"))
{
	header("HTTP/1.0 404 Not Found");
	exit;
}

// set the priority to -1000000 to make sure it is always the first plugin run - we'll be editing the core permission arrays; if it's loaded right at the start, all core code and plugins will use the custom permissions
$plugins->add_hook("global_start", "globalmoderators_load", -1000000);
$plugins->add_hook("forumdisplay_end", "globalmoderators_modlist");
$plugins->add_hook("admin_user_menu", "globalmoderators_admin_user_menu");
$plugins->add_hook("admin_user_action_handler", "globalmoderators_admin_user_action_handler");
$plugins->add_hook("admin_user_permissions", "globalmoderators_admin_user_permissions");

function globalmoderators_info()
{
	return array(
		'name' => 'Global Moderators',
		'description' => 'Apply moderator permissions globally to all forums',
		'website' => 'https://github.com/MattRogowski/Global-Moderators',
		'author' => 'Matt Rogowski',
		'authorsite' => 'https://matt.rogow.ski',
		'version' => '0.0.1',
		'compatibility' => '18*',
		'codename' => 'globalmoderators'
	);
}

function globalmoderators_install()
{
	global $db, $globalmoderators_uninstall_confirm_override;

	// this is so we override the confirmation when trying to uninstall, so we can just run the uninstall code
	$globalmoderators_uninstall_confirm_override = true;
	globalmoderators_uninstall();

	if(!$db->table_exists("globalmoderators"))
	{
		$db->write_query("
			CREATE TABLE `".TABLE_PREFIX."globalmoderators` (
				`gmid` smallint(5) NOT NULL AUTO_INCREMENT,
				`type` enum('user','usergroup') DEFAULT NULL,
				`id` int(10) NOT NULL,
				`perms` text NOT NULL,
				`active` int(1) NOT NULL DEFAULT '0',
				PRIMARY KEY (`gmid`)
			) ENGINE=MyISAM DEFAULT CHARSET=latin1;
		");
	}

	change_admin_permission("user", "globalmoderators", 1);
}

function globalmoderators_is_installed()
{
	global $db;

	return $db->table_exists("globalmoderators");
}

function globalmoderators_uninstall()
{
	global $mybb, $db, $cache, $globalmoderators_uninstall_confirm_override;

	// this is a check to make sure we want to uninstall
	// if 'No' was chosen on the confirmation screen, redirect back to the plugins page
	if($mybb->input['no'])
	{
		admin_redirect("index.php?module=config-plugins");
	}
	else
	{
		// there's a post request so we submitted the form and selected yes
		// or the confirmation is being overridden by the installation function; this is for when globalmoderators_uninstall() is called at the start of globalmoderators_install(), we just want to execute the uninstall code at this point
		if($mybb->request_method == "post" || $globalmoderators_uninstall_confirm_override === true || $mybb->input['action'] == "delete")
		{
			if($db->table_exists("globalmoderators"))
			{
				$db->drop_table("globalmoderators");
			}

			$cache->delete('globalmoderators');
		}
		// need to show the confirmation
		else
		{
			global $lang, $page;

			$lang->load("user_globalmoderators");

			$query = $db->simple_select("globalmoderators", "COUNT(*) AS globalmoderators");
			$globalmoderators = $db->fetch_field($query, "globalmoderators");
			if($globalmoderators > 0)
			{
				$lang->globalmoderators_uninstall_warning .= " " . $lang->sprintf($lang->globalmoderators_uninstall_warning_count, $globalmoderators);
			}

			$page->output_confirm_action("index.php?module=config-plugins&action=deactivate&uninstall=1&plugin=globalmoderators&my_post_key={$mybb->post_code}", $lang->globalmoderators_uninstall_warning);
		}
	}
}

function globalmoderators_activate()
{
	globalmoderators_cache();
}

function globalmoderators_deactivate()
{

}

function globalmoderators_load()
{
	if(!preg_match('/^(forumdisplay|showthread|moderation)\.php$/', THIS_SCRIPT))
	{
		return;
	}

	global $db, $cache, $global_moderators;

	$cache->read('moderators');

	$global_moderators = array('users' => array(), 'usergroups' => array());
	$global_moderators_data = array(
		'users' => array(
			/*2 => array(
				'canopenclosethreads' => 1,
				'canstickunstickthreads' => 1,
			)*/
		),
		'usergroups' => array(
			/*2 => array(
				'canopenclosethreads' => 1,
				'canstickunstickthreads' => 1,
			)*/
		)
	);

	$forums = $cache->read('forums');
	foreach($forums as $fid => $forum)
	{
		if($forum['type'] != 'c')
		{
			continue;
		}

		foreach($global_moderators_data['users'] as $id => $perms)
		{
			$data = array(
				'mid' => 0,
				'fid' => $fid,
				'id' => $id,
				'isgroup' => 0,
				'title' => '',
			);
			foreach($perms as $key => $val)
			{
				$data[$key] = $val;
			}
			$cache->cache['moderators'][$fid]['users'][$id] = $data;

			$global_moderators['users'][$id] = $id;
		}

		foreach($global_moderators_data['usergroups'] as $id => $perms)
		{
			$data = array(
				'mid' => 0,
				'fid' => $fid,
				'id' => $id,
				'isgroup' => 1,
				'title' => '',
			);
			foreach($perms as $key => $val)
			{
				$data[$key] = $val;
			}
			$cache->cache['moderators'][$fid]['usergroups'][$id] = $data;

			$global_moderators['usergroups'][$id] = $id;
			$query = $db->simple_select('users', 'uid', 'usergroup = '.intval($id));
			while($uid = $db->fetch_field($query, 'uid'))
			{
				$global_moderators['users'][$uid] = $uid;
			}
		}
	}
}

function globalmoderators_modlist()
{
	// this is copied from forumdisplay.php
	// we don't want to show global moderators in the 'moderated by' list
	// the same as super moderators don't show up in this list
	// the only actual difference is the in_array check with $global_moderators

	global $lang, $templates, $moderatorcache, $parentlist, $moderatedby, $global_moderators;

	$done_moderators = array(
		"users" => array(),
		"groups" => array()
	);

	$moderators = '';
	$parentlistexploded = explode(",", $parentlist);

	foreach($parentlistexploded as $mfid)
	{
		// This forum has moderators
		if(is_array($moderatorcache[$mfid]))
		{
			// Fetch each moderator from the cache and format it, appending it to the list
			foreach($moderatorcache[$mfid] as $modtype)
			{
				foreach($modtype as $moderator)
				{
					if($moderator['isgroup'])
					{
						if(in_array($moderator['id'], $done_moderators['groups']) || (in_array($moderator['id'], $global_moderators['usergroups']) && !$moderator['mid']))
						{
							continue;
						}

						$moderator['title'] = htmlspecialchars_uni($moderator['title']);

						eval("\$moderators .= \"".$templates->get("forumbit_moderators_group", 1, 0)."\";");
						$done_moderators['groups'][] = $moderator['id'];
					}
					else
					{
						if(in_array($moderator['id'], $done_moderators['users']) || (in_array($moderator['id'], $global_moderators['users']) && !$moderator['mid']))
						{
							continue;
						}

						$moderator['profilelink'] = get_profile_link($moderator['id']);
						$moderator['username'] = format_name(htmlspecialchars_uni($moderator['username']), $moderator['usergroup'], $moderator['displaygroup']);

						eval("\$moderators .= \"".$templates->get("forumbit_moderators_user", 1, 0)."\";");
						$done_moderators['users'][] = $moderator['id'];
					}
					$comma = $lang->comma;
				}
			}
		}

		if(!empty($forum_stats[$mfid]['announcements']))
		{
			$has_announcements = true;
		}
	}
	$comma = '';

	// If we have a moderators list, load the template
	if($moderators)
	{
		eval("\$moderatedby = \"".$templates->get("forumdisplay_moderatedby")."\";");
	}
	else
	{
		$moderatedby = '';
	}
}

function globalmoderators_admin_user_menu($sub_menu)
{
	global $lang;

	$lang->load("user_globalmoderators");

	$sub_menu[] = array("id" => "globalmoderators", "title" => $lang->globalmoderators, "link" => "index.php?module=user-globalmoderators");

	return $sub_menu;
}

function globalmoderators_admin_user_action_handler($actions)
{
	$actions['globalmoderators'] = array(
		"active" => "globalmoderators",
		"file" => "globalmoderators.php"
	);

	return $actions;
}

function globalmoderators_admin_user_permissions($admin_permissions)
{
	global $lang;

	$lang->load("user_globalmoderators");

	$admin_permissions['globalmoderators'] = $lang->can_manage_globalmoderators;

	return $admin_permissions;
}

function globalmoderators_cache()
{
	global $db, $cache;

	$globalmoderators = array('user' => array(), 'usergroup' => array());

	$query = $db->simple_select('globalmoderators', '*', 'active = 1');
	while($globalmoderator = $db->fetch_array($query))
	{
		$globalmoderators[$globalmoderator['type']][$globalmoderator['id']] = unserialize($globalmoderator['perms']);
	}

	$cache->update('globalmoderators', $globalmoderators);
}

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
//$plugins->add_hook("global_start", "globalmoderators_load", -1000000);
//$plugins->add_hook("forumdisplay_end", "globalmoderators_modlist");

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

function globalmoderators_activate()
{

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

	$original_moderators = $cache->read('moderators');

	$global_moderators = array('users' => array(), 'usergroups' => array());
	$global_moderators_data = array(
		'users' => array(
			/*2 => array(
				'canopenclosethreads' => 1,
				'canstickunstickthreads' => 1,
			)*/
		),
		'usergroups' => array(
			2 => array(
				'canopenclosethreads' => 1,
				'canstickunstickthreads' => 1,
			)
		)
	);

	foreach($global_moderators_data['users'] as $id => $perms)
	{
		$data = array(
			'mid' => 0,
			'fid' => 1,
			'id' => $id,
			'isgroup' => 0,
			'title' => 'faked - test',
		);
		foreach($perms as $key => $val)
		{
			$data[$key] = $val;
		}
		$cache->cache['moderators'][1]['users'][$id] = $data;

		$global_moderators['users'][$id] = $id;
	}

	foreach($global_moderators_data['usergroups'] as $id => $perms)
	{
		$data = array(
			'mid' => 0,
			'fid' => 1,
			'id' => $id,
			'isgroup' => 1,
			'title' => 'faked - registered',
		);
		foreach($perms as $key => $val)
		{
			$data[$key] = $val;
		}
		$cache->cache['moderators'][1]['usergroups'][$id] = $data;

		$global_moderators['usergroups'][$id] = $id;
		$query = $db->simple_select('users', 'uid', 'usergroup = '.intval($id));
		while($uid = $db->fetch_field($query, 'uid'))
		{
			$global_moderators['users'][$uid] = $uid;
		}
	}

	//print_r($original_moderators);
	//print_r($cache->cache['moderators']);
}

function globalmoderators_modlist()
{
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
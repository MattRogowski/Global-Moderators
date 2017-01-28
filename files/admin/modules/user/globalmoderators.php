<?php
/**
 * Global Moderators 0.0.1 - Admin File

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

$page->add_breadcrumb_item($lang->globalmoderators, "index.php?module=user-globalmoderators");

if($mybb->input['action'] == "do_edit")
{
	if(!verify_post_check($mybb->input['my_post_key']))
	{
		flash_message($lang->invalid_post_verify_key2, 'error');
		admin_redirect("index.php?module=user-globalmoderators");
	}

	$gmid = intval($mybb->input['gmid']);

	$query = $db->simple_select("globalmoderators", "perms", "gmid = '{$gmid}'");
	if($db->num_rows($query) == 0)
	{
		flash_message($lang->globalmoderators_invalid, 'error');
		admin_redirect("index.php?module=user-globalmoderators");
	}

	$perms = $db->fetch_field($query, "perms");
	$perms = unserialize($perms);

	foreach($mybb->input['perms'] as $perm => $value)
	{
		if(should_add_perms($mybb->input['perms'], $perm, $value))
		{
			$perms[$perm] = $value;
		}
	}

	$perms = serialize($perms);

	$update = array(
		"perms" => $db->escape_string($perms)
	);
	$db->update_query("globalmoderators", $update, "gmid = '{$gmid}'");

	globalmoderators_cache();

	flash_message($lang->globalmoderators_updated, 'success');
	admin_redirect("index.php?module=user-globalmoderators&action=edit&gmid={$gmid}");
}
elseif($mybb->input['action'] == "edit")
{
	$gmid = intval($mybb->input['gmid']);

	$query = $db->simple_select("globalmoderators", "type, id, perms", "gmid = '{$gmid}'");
	if($db->num_rows($query) == 0)
	{
		flash_message($lang->globalmoderators_invalid, 'error');
		admin_redirect("index.php?module=user-globalmoderators");
	}

	$globalmoderator = $db->fetch_array($query);

	switch($globalmoderator['type'])
	{
		case 'user':
			$nav_text = $lang->globalmoderators_edit_nav_user;

			$query = $db->simple_select("users", "username", "uid = '" . $globalmoderator['id'] . "'", array("limit" => 1));
			$user = $db->fetch_array($query);

			$form_container_text = $lang->sprintf($lang->globalmoderators_edit_user, $user['username']);
			break;
		case 'usergroup':
			$nav_text = $lang->globalmoderators_edit_nav_usergroup;

			$query = $db->simple_select("usergroups", "title", "gid = '" . $globalmoderator['id'] . "'", array("limit" => 1));
			$usergroup = $db->fetch_array($query);

			$form_container_text = $lang->sprintf($lang->globalmoderators_edit_usergroup, $usergroup['title']);
			break;
	}

	$perms = unserialize($globalmoderator['perms']);

	$sub_tabs = array();
	$sub_tabs['globalmoderators'] = array(
		'title' => $lang->globalmoderators,
		'link' => "index.php?module=user-globalmoderators",
		'description' => $lang->globalmoderators_nav
	);
	$sub_tabs['globalmoderators_edit'] = array(
		'title' => $lang->globalmoderators_edit,
		'link' => "index.php?module=user-globalmoderators&amp;action=edit&amp;gmid={$gmid}",
		'description' => $nav_text
	);

	$page->add_breadcrumb_item($lang->globalmoderators_edit, "index.php?module=user-globalmoderators&amp;action=edit");
	$page->output_header($lang->globalmoderators);
	$page->output_nav_tabs($sub_tabs, "globalmoderators_edit");

	echo "<script type=\"text/javascript\">
	jQuery(document).ready(function() {
		jQuery('.set_all').click(function() {
			jQuery('input[type=\"radio\"][name^=\"perms\"]').prop('checked', false);
			jQuery('input[type=\"radio\"][name^=\"perms\"][value=\"'+jQuery(this).data('value')+'\"]').prop('checked', true);
		});
	});
	</script>";

	$permissions = array(
		"moderator_permissions" => array(
			"caneditposts" => array(
				"lang" => "can_edit_posts",
				"type" => "yesno"
			),
			"cansoftdeleteposts" => array(
				"lang" => "can_soft_delete_posts",
				"type" => "yesno"
			),
			"canrestoreposts" => array(
				"lang" => "can_restore_posts",
				"type" => "yesno"
			),
			"candeleteposts" => array(
				"lang" => "can_delete_posts",
				"type" => "yesno"
			),
			"cansoftdeletethreads" => array(
				"lang" => "can_soft_delete_threads",
				"type" => "yesno"
			),
			"canrestorethreads" => array(
				"lang" => "can_restore_threads",
				"type" => "yesno"
			),
			"candeletethreads" => array(
				"lang" => "can_delete_threads",
				"type" => "yesno"
			),
			"canviewips" => array(
				"lang" => "can_view_ips",
				"type" => "yesno"
			),
			"canviewunapprove" => array(
				"lang" => "can_view_unapprove",
				"type" => "yesno"
			),
			"canviewdeleted" => array(
				"lang" => "can_view_deleted",
				"type" => "yesno"
			),
			"canopenclosethreads" => array(
				"lang" => "can_open_close_threads",
				"type" => "yesno"
			),
			"canstickunstickthreads" => array(
				"lang" => "can_stick_unstick_threads",
				"type" => "yesno"
			),
			"canapproveunapprovethreads" => array(
				"lang" => "can_approve_unapprove_threads",
				"type" => "yesno"
			),
			"canapproveunapproveposts" => array(
				"lang" => "can_approve_unapprove_posts",
				"type" => "yesno"
			),
			"canapproveunapproveattachs" => array(
				"lang" => "can_approve_unapprove_attachments",
				"type" => "yesno"
			),
			"canmanagethreads" => array(
				"lang" => "can_manage_threads",
				"type" => "yesno"
			),
			"canmanagepolls" => array(
				"lang" => "can_manage_polls",
				"type" => "yesno"
			),
			"canpostclosedthreads" => array(
				"lang" => "can_post_closed_threads",
				"type" => "yesno"
			),
			"canmovetononmodforum" => array(
				"lang" => "can_move_to_other_forums",
				"type" => "yesno"
			),
			"canusecustomtools" => array(
				"lang" => "can_use_custom_tools",
				"type" => "yesno"
			),
		),
		"globalmoderators_moderator_cp_permissions" => array(
			"canmanageannouncements" => array(
				"lang" => "globalmoderators_can_manage_announcements",
				"type" => "yesno"
			),
			"canmanagereportedposts" => array(
				"lang" => "globalmoderators_can_manage_reported_posts",
				"type" => "yesno"
			),
			"canviewmodlog" => array(
				"lang" => "globalmoderators_can_view_mod_log",
				"type" => "yesno"
			),
		)
	);

	$lang->load("forum_management");

	$form = new Form("index.php?module=user-globalmoderators&amp;action=do_edit", "post");
	$form_container = new FormContainer($form_container_text);
	$form_container->output_row_header($lang->permission, array("class" => "align_center", 'style' => 'width: 30%'));
	$form_container->output_row_header($lang->controls, array("class" => "align_center", "colspan" => 3));

	generate_permissions($permissions, $perms);

	$form_container->end();

	echo $form->generate_hidden_field("gmid", $gmid);

	$buttons[] = $form->generate_submit_button($lang->submit);
	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}
elseif($mybb->input['action'] == "do_add")
{
	if(!verify_post_check($mybb->input['my_post_key']))
	{
		flash_message($lang->invalid_post_verify_key2, 'error');
		admin_redirect("index.php?module=user-globalmoderators");
	}

	switch($mybb->input['type'])
	{
		case 'user':
			$query = $db->simple_select("users", "uid", "username = '" . $db->escape_string($mybb->input['username']) . "'", array("limit" => 1));
			$user = $db->fetch_array($query);

			if(!$user)
			{
				flash_message($lang->globalmoderators_invalid_user, 'error');
				admin_redirect("index.php?module=user-globalmoderators");
			}

			$existing = $db->simple_select('globalmoderators', '*', "type = 'user' and id = ".intval($user['uid']));
			if($existing->num_rows != 0)
			{
				flash_message($lang->globalmoderators_duplicate_user, 'error');
				admin_redirect("index.php?module=user-globalmoderators");
			}

			$insert = array(
				'type' => 'user',
				'id' => intval($user['uid']),
				'active' => 1
			);
			$db->insert_query("globalmoderators", $insert);

			globalmoderators_cache();

			flash_message($lang->globalmoderators_user_added, 'success');
			admin_redirect("index.php?module=user-globalmoderators");

			break;
		case 'usergroup':
			$query = $db->simple_select("usergroups", "gid", "gid = '" . $db->escape_string($mybb->input['usergroup']) . "'", array("limit" => 1));
			$usergroup = $db->fetch_array($query);

			if(!$usergroup)
			{
				flash_message($lang->globalmoderators_invalid_usergroup, 'error');
				admin_redirect("index.php?module=user-globalmoderators");
			}

			$existing = $db->simple_select('globalmoderators', '*', "type = 'usergroup' and id = ".intval($usergroup['gid']));
			if($existing->num_rows != 0)
			{
				flash_message($lang->globalmoderators_duplicate_usergroup, 'error');
				admin_redirect("index.php?module=user-globalmoderators");
			}

			$insert = array(
				'type' => 'usergroup',
				'id' => intval($usergroup['gid']),
				'active' => 1
			);
			$db->insert_query("globalmoderators", $insert);

			globalmoderators_cache();

			flash_message($lang->globalmoderators_usergroup_added, 'success');
			admin_redirect("index.php?module=user-globalmoderators");
			break;
	}
}
elseif($mybb->input['action'] == "do_delete")
{
	$gmid = intval($mybb->input['gmid']);

	if($mybb->input['no'])
	{
		admin_redirect("index.php?module=user-globalmoderators");
	}
	else
	{
		if(!verify_post_check($mybb->input['my_post_key']))
		{
			flash_message($lang->invalid_post_verify_key2, 'error');
			admin_redirect("index.php?module=user-globalmoderators");
		}

		$query = $db->simple_select("globalmoderators", "*", "gmid = '{$gmid}'");
		$globalmoderator = $db->fetch_array($query);

		if(!$globalmoderator['gmid'])
		{
			flash_message($lang->globalmoderators_invalid, 'error');
			admin_redirect("index.php?module=user-globalmoderators");
		}
		else
		{
			$db->delete_query("globalmoderators", "gmid = '{$gmid}'");

			globalmoderators_cache();

			flash_message($lang->globalmoderators_deleted, 'success');
			admin_redirect("index.php?module=user-globalmoderators");
		}
	}
}
elseif($mybb->input['action'] == "delete")
{
	$page->output_confirm_action("index.php?module=user-globalmoderators&action=do_delete&gmid={$mybb->input['gmid']}&my_post_key={$mybb->post_code}", $lang->globalmoderators_delete);
}
elseif($mybb->input['action'] == "status")
{
	if(!verify_post_check($mybb->input['my_post_key']))
	{
		flash_message($lang->invalid_post_verify_key2, 'error');
		admin_redirect("index.php?module=user-globalmoderators");
	}

	$gmid = intval($mybb->input['gmid']);

	$query = $db->simple_select("globalmoderators", "*", "gmid = '{$gmid}'");

	if($db->num_rows($query) == 0)
	{
		flash_message($lang->globalmoderators_invalid, 'error');
		admin_redirect("index.php?module=user-globalmoderators");
	}

	$globalmoderator = $db->fetch_array($query);

	if($globalmoderator['active'] == 1)
	{
		$active = 0;
		$flash_message = $lang->globalmoderators_perms_deactivated;
	}
	else
	{
		$active = 1;
		$flash_message = $lang->globalmoderators_perms_activated;
	}

	$update = array(
		"active" => $active
	);
	$db->update_query("globalmoderators", $update, "gmid = '{$gmid}'");

	globalmoderators_cache();

	flash_message($flash_message, 'success');
	admin_redirect("index.php?module=user-globalmoderators");
}
else
{
	$lang->load("forum_management");

	$page->output_header($lang->globalmoderators);

	$sub_tabs = array();
	$sub_tabs['globalmoderators'] = array(
		'title' => $lang->globalmoderators,
		'link' => "index.php?module=user-globalmoderators",
		'description' => $lang->globalmoderators_nav
	);

	$page->output_nav_tabs($sub_tabs, "globalmoderators");

	$query = $db->write_query("
		SELECT gm.*, u.username
		FROM " . TABLE_PREFIX . "globalmoderators gm
		LEFT JOIN " . TABLE_PREFIX . "users u
		ON (gm.id = u.uid)
		WHERE gm.type = 'user'
		ORDER BY u.username ASC
	");
	//SELECT p.*, u.username FROM mybb_globalmoderators p LEFT JOIN mybb_users u ON (p.uid = u.uid) ORDER BY p.gmid ASC
	if($db->num_rows($query) > 0)
	{
		$table = new Table;

		$table->construct_header($lang->username);
		$table->construct_header($lang->controls, array("colspan" => 3, 'class' => 'align_center'));

		while($perm = $db->fetch_array($query))
		{
			if($perm['active'] == 1)
			{
				$status = $lang->deactivate;
			}
			else
			{
				$status = $lang->activate;
			}

			$table->construct_cell($perm['username'], array('width' => '20%'));
			$table->construct_cell("<a href=\"index.php?module=user-globalmoderators&amp;action=edit&amp;gmid={$perm['gmid']}\">{$lang->view_edit}</a>", array('class' => 'align_center', 'width' => '15%'));
			$table->construct_cell("<a href=\"index.php?module=user-globalmoderators&amp;action=status&amp;gmid={$perm['gmid']}&amp;my_post_key={$mybb->post_code}\">{$status}</a>", array('class' => 'align_center', 'width' => '15%'));
			$table->construct_cell("<a href=\"index.php?module=user-globalmoderators&amp;action=delete&amp;gmid={$perm['gmid']}\">{$lang->delete}</a>", array('class' => 'align_center', 'width' => '15%'));
			$table->construct_row();
		}

		$table->output($lang->globalmoderators_current_user);
	}

	$form = new Form("index.php?module=user-globalmoderators&amp;action=do_add", "post");
	$form_container = new FormContainer($lang->add_user_as_moderator);

	$globalmoderators_add_user_name = $form->generate_text_box("username", '', array('id' => 'username'));
	$form_container->output_row($lang->username . " <em>*</em>", '', $globalmoderators_add_user_name);

	$form_container->end();

	// Autocompletion for usernames
	echo '
	<link rel="stylesheet" href="../jscripts/select2/select2.css">
	<script type="text/javascript" src="../jscripts/select2/select2.min.js"></script>
	<script type="text/javascript">
	<!--
	$("#username").select2({
		placeholder: "Search for a user",
		minimumInputLength: 3,
		maximumSelectionSize: 3,
		multiple: false,
		ajax: { // instead of writing the function to execute the request we use Select2\'s convenient helper
			url: "../xmlhttp.php?action=get_users",
			dataType: \'json\',
			data: function (term, page) {
				return {
					query: term // search term
				};
			},
			results: function (data, page) { // parse the results into the format expected by Select2.
				// since we are using custom formatting functions we do not need to alter remote JSON data
				return {results: data};
			}
		},
		initSelection: function(element, callback) {
			var query = $(element).val();
			if (query !== "") {
				$.ajax("../xmlhttp.php?action=get_users&getone=1", {
					data: {
						query: query
					},
					dataType: "json"
				}).done(function(data) { callback(data); });
			}
		}
	});
	// -->
	</script>';

	echo $form->generate_hidden_field('type', 'user');

	$buttons = array($form->generate_submit_button($lang->add_user_moderator));
	$form->output_submit_wrapper($buttons);
	$form->end();

	echo '<br />';

	$query = $db->write_query("
		SELECT gm.*, ug.title
		FROM " . TABLE_PREFIX . "globalmoderators gm
		LEFT JOIN " . TABLE_PREFIX . "usergroups ug
		ON (gm.id = ug.gid)
		WHERE gm.type = 'usergroup'
		ORDER BY ug.gid ASC
	");
	//SELECT p.*, u.username FROM mybb_globalmoderators p LEFT JOIN mybb_users u ON (p.uid = u.uid) ORDER BY p.gmid ASC
	if($db->num_rows($query) > 0)
	{
		$table = new Table;

		$table->construct_header($lang->username);
		$table->construct_header($lang->controls, array("colspan" => 3, 'class' => 'align_center'));

		while($perm = $db->fetch_array($query))
		{
			if($perm['active'] == 1)
			{
				$status = $lang->deactivate;
			}
			else
			{
				$status = $lang->activate;
			}

			$table->construct_cell($perm['title'], array('width' => '20%'));
			$table->construct_cell("<a href=\"index.php?module=user-globalmoderators&amp;action=edit&amp;gmid={$perm['gmid']}\">{$lang->view_edit}</a>", array('class' => 'align_center', 'width' => '15%'));
			$table->construct_cell("<a href=\"index.php?module=user-globalmoderators&amp;action=status&amp;gmid={$perm['gmid']}&amp;my_post_key={$mybb->post_code}\">{$status}</a>", array('class' => 'align_center', 'width' => '15%'));
			$table->construct_cell("<a href=\"index.php?module=user-globalmoderators&amp;action=delete&amp;gmid={$perm['gmid']}\">{$lang->delete}</a>", array('class' => 'align_center', 'width' => '15%'));
			$table->construct_row();
		}

		$table->output($lang->globalmoderators_current_usergroup);
	}

	$usergroups = $cache->read('usergroups');
	$modgroups = array();
	foreach($usergroups as $group)
	{
		$modgroups[$group['gid']] = $lang->usergroup." ".$group['gid'].": ".htmlspecialchars_uni($group['title']);
	}

	$form = new Form("index.php?module=user-globalmoderators&amp;action=do_add", "post");
	$form_container = new FormContainer($lang->add_usergroup_as_moderator);

	$globalmoderators_add_usergroup_group = $form->generate_select_box('usergroup', $modgroups, $mybb->input['usergroup'], array('id' => 'usergroup'));
	$form_container->output_row($lang->usergroup . " <em>*</em>", '', $globalmoderators_add_usergroup_group);

	$form_container->end();

	echo $form->generate_hidden_field('type', 'usergroup');

	$buttons = array($form->generate_submit_button($lang->add_usergroup_moderator));
	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

function check_radio_button($perms, $perm, $value)
{
	// either this permission is set and the value we're checking is what it's set to, or it's not set and we're checking the 'inherit' option
	if((isset($perms[$perm]) && $perms[$perm] == $value) || (!isset($perms[$perm]) && $value == -1))
	{
		return 1;
	}
	else
	{
		return 0;
	}
}

function should_add_perms($perms, $perm, $value)
{
	// if this is a numerical value option, store it if any value is set, even if it's not set to be used
	if(substr($perm, -6) == "_value")
	{
		if(!empty($value))
		{
			return true;
		}
	}
	// if this is the option to choose whether to use a numerical value, store it as enabled if it's set to 1 and the actual value isn't empty
	elseif(array_key_exists($perm . "_value", $perms))
	{
		if($value == 1 && !empty($perms[$perm . "_value"]))
		{
			return true;
		}
	}
	// else it's just a standard option, if it's not set to inherit, store it
	elseif($value != -1)
	{
		return true;
	}
	// don't store it, it's set to inherit
	else
	{
		return false;
	}
}

function generate_permissions($permissions, $user_perms)
{
	global $lang, $form, $form_container;

	$done_groups = 0;
	foreach($permissions as $group => $perms)
	{
		if($done_groups == 0)
		{
			$form_container->output_cell("<strong>" . $lang->$group . "</strong>");
			$form_container->output_cell("<a href='javascript:void(0)' class='set_all' data-value='1'>" . $lang->yes_all . "</a>", array('style' => 'text-align: center;'));
			$form_container->output_cell("<a href='javascript:void(0)' class='set_all' data-value='0'>" . $lang->no_all . "</a>", array('style' => 'text-align: center;'));
			$form_container->construct_row();
		}
		else
		{
			$form_container->output_cell("<strong>" . $lang->$group . "</strong>", array("colspan" => 4));
			$form_container->construct_row();
		}
		foreach($perms as $perm => $info)
		{
			$info['description'] = "";
			if($info['type'] == "text")
			{
				$description = $info['lang'] . "_desc";
				$info['description'] = "<br /><small class=\"input\">" . $lang->$description . "</small> ";
			}
			$form_container->output_cell($lang->{$info['lang']} . $info['description']);
			if($info['type'] == "yesno")
			{
				$form_container->output_cell($form->generate_radio_button("perms[{$perm}]", 1, $lang->yes, array("checked" => check_radio_button($user_perms, $perm, 1))), array("class" => "align_center"));
				$form_container->output_cell($form->generate_radio_button("perms[{$perm}]", 0, $lang->no, array("checked" => check_radio_button($user_perms, $perm, 0))), array("class" => "align_center"));
			}
			elseif($info['type'] == "text")
			{
				$form_container->output_cell($form->generate_radio_button("perms[{$perm}]", 1, $form->generate_text_box("perms[{$perm}_value]", $user_perms[$perm . "_value"], array("style" => "width: {$info['size']}px;")), array("checked" => check_radio_button($user_perms, $perm, 1))), array("class" => "align_center", "colspan" => 2));
			}
			$form_container->construct_row();
		}
		$done_groups++;
	}
}

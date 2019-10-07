<?php

/**
* @package   s9e\Reckless
* @copyright Copyright (c) 2018-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\reckless\migrations;

use phpbb\db\migration\migration;

class v000001 extends migration
{
	public function revert_data()
	{
		$data = [['config.remove', ['banlist_last_update']]];
		if ($this->config['auth_method'] === 'reckless')
		{
			$data[] = ['config.update', ['auth_method', 'db']];
		}

		return $data;
	}

	public function revert_schema()
	{
		return [
			'add_index' => [
				$this->table_prefix . 'bbcodes' => [
					'display_on_post' => ['display_on_posting']
				],
				$this->table_prefix . 'forums_watch' => [
					'user_id' => ['user_id']
				],
				$this->table_prefix . 'sessions' => [
					'session_fid'     => ['session_forum_id'],
					'session_user_id' => ['session_user_id']
				],
				$this->table_prefix . 'topics_watch' => [
					'user_id' => ['user_id']
				],
				$this->table_prefix . 'user_group' => [
					'user_id' => ['user_id']
				]
			],
			'drop_keys' => [
				$this->table_prefix . 'bbcodes'        => ['display_on_post'],
				$this->table_prefix . 'drafts'         => ['user_drafts'],
				$this->table_prefix . 'forums_watch'   => ['user_id'],
				$this->table_prefix . 'groups'         => ['group_auth'],
				$this->table_prefix . 'notifications'  => ['most_recent'],
				$this->table_prefix . 'posts'          => ['reading_order'],
				$this->table_prefix . 'profile_fields' => ['display'],
				$this->table_prefix . 'sessions'       => ['session_fid', 'session_user_id'],
				$this->table_prefix . 'topics'         => ['listing_order'],
				$this->table_prefix . 'topics_watch'   => ['user_id'],
				$this->table_prefix . 'user_group'     => ['user_id']
			]
		];
	}

	public function update_data()
	{
		$data = [
			['config.add',    ['banlist_last_update',       0]],
			['config.update', ['enable_accurate_pm_button', 0]],
			['config.update', ['img_max_height',            0]],
			['config.update', ['img_max_width',             0]],
			['config.update', ['load_tplcompile',           0]]
		];
		if ($this->config['auth_method'] === 'db')
		{
			$data[] = ['config.update', ['auth_method', 'reckless']];
		}

		return $data;
	}

	public function update_schema()
	{
		return [
			'add_index' => [
				$this->table_prefix . 'bbcodes' => [
					'display_on_post' => [
						'display_on_posting',
						'bbcode_tag'
					]
				],
				$this->table_prefix . 'drafts' => [
					'user_drafts' => [
						'user_id',
						'forum_id',
						'topic_id',
						'save_time'
					]
				],
				$this->table_prefix . 'forums_watch' => [
					'user_id' => [
						'user_id',
						'forum_id'
					]
				],
				$this->table_prefix . 'groups' => [
					'group_auth' => [
						'group_id',
						'group_skip_auth'
					]
				],
				$this->table_prefix . 'notifications' => [
					'most_recent' => [
						'user_id',
						'notification_time'
					]
				],
				$this->table_prefix . 'posts' => [
					'reading_order' => [
						'topic_id',
						'post_visibility',
						'post_time',
						'post_id'
					]
				],
				$this->table_prefix . 'profile_fields' => [
					'display' => [
						'field_active',
						'field_no_view',
						'field_hide',
						'field_order'
					]
				],
				$this->table_prefix . 'sessions' => [
					'session_fid' => [
						'session_forum_id',
						'session_user_id',
						'session_time'
					],
					'session_user_id' => [
						'session_user_id',
						'session_time',
						'session_viewonline'
					]
				],
				$this->table_prefix . 'topics' => [
					'listing_order' => [
						'forum_id',
						'topic_visibility',
						'topic_type',
						'topic_last_post_time',
						'topic_last_post_id'
					]
				],
				$this->table_prefix . 'topics_watch' => [
					'user_id' => [
						'user_id',
						'topic_id'
					]
				],
				$this->table_prefix . 'user_group' => [
					'user_id' => [
						'user_id',
						'user_pending'
					]
				]
			],
			'drop_keys' => [
				$this->table_prefix . 'bbcodes'      => ['display_on_post'],
				$this->table_prefix . 'forums_watch' => ['user_id'],
				$this->table_prefix . 'sessions'     => ['session_fid', 'session_user_id'],
				$this->table_prefix . 'topics_watch' => ['user_id'],
				$this->table_prefix . 'user_group'   => ['user_id']
			]
		];
	}
}
<?php

/**
* @package   s9e\reckless
* @copyright Copyright (c) 2018 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\reckless\migrations;

use phpbb\db\migration\migration;

class indexes extends migration
{
	public function update_schema()
	{
		return [
			'add_index' => [
				$this->table_prefix . 'forums_watch' => [
					'user_id' => [
						'user_id',
						'forum_id'
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
				$this->table_prefix . 'sessions' => [
					'session_fid' => [
						'session_forum_id',
						'session_user_id'
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
				]
			],
			'drop_keys' => [
				$this->table_prefix . 'forums_watch' => ['user_id'],
				$this->table_prefix . 'sessions'     => ['session_fid'],
				$this->table_prefix . 'topics_watch' => ['user_id']
			]
		];
	}

	public function revert_schema()
	{
		return [
			'add_index' => [
				$this->table_prefix . 'forums_watch' => [
					'user_id' => ['user_id']
				],
				$this->table_prefix . 'sessions' => [
					'session_fid' => ['session_forum_id']
				],
				$this->table_prefix . 'topics_watch' => [
					'user_id' => ['user_id']
				]
			],
			'drop_keys' => [
				$this->table_prefix . 'forums_watch'  => ['user_id'],
				$this->table_prefix . 'notifications' => ['most_recent'],
				$this->table_prefix . 'posts'         => ['reading_order'],
				$this->table_prefix . 'sessions'      => ['session_fid'],
				$this->table_prefix . 'topics'        => ['listing_order'],
				$this->table_prefix . 'topics_watch'  => ['user_id']
			]
		];
	}
}
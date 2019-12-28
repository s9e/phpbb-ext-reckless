<?php

/**
* @package   s9e\Reckless
* @copyright Copyright (c) 2018-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\reckless\migrations;

use phpbb\db\migration\migration;

class indexes_01 extends migration
{
	public function revert_schema()
	{
		return [
			'drop_keys' => [
				$this->table_prefix . 'notifications' => ['item_parent_id', 'most_recent'],
				$this->table_prefix . 'topics'        => ['listing_order']
			]
		];
	}

	public function update_schema()
	{
		return [
			'add_index' => [
				$this->table_prefix . 'notifications' => [
					'item_parent_id' => [
						'item_parent_id'
					],
					'most_recent' => [
						'user_id',
						'notification_time'
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
				]
			]
		];
	}
}
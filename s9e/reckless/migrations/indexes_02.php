<?php

/**
* @package   s9e\Reckless
* @copyright Copyright (c) 2018-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\reckless\migrations;

use phpbb\db\migration\migration;

class indexes_02 extends migration
{
	public function revert_schema()
	{
		return [
			'drop_keys' => [
				$this->table_prefix . 'posts' => ['reading_order'],
			]
		];
	}

	public function update_schema()
	{
		return [
			'add_index' => [
				$this->table_prefix . 'posts' => [
					'reading_order' => [
						'topic_id',
						'post_visibility',
						'post_time',
						'post_id'
					]
				]
			]
		];
	}
}
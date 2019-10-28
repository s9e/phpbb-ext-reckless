<?php

/**
* @package   s9e\Reckless
* @copyright Copyright (c) 2018-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\reckless\migrations;

use phpbb\db\migration\migration;

class config_00 extends migration
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
}
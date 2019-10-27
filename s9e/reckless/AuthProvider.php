<?php

/**
* @package   s9e\Reckless
* @copyright Copyright (c) 2018-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\reckless;

use phpbb\auth\provider\db;

class AuthProvider extends db
{
	/**
	* {@inheritdoc}
	*/
	public function validate_session($user)
	{
		// Skip ban check if the banlist hasn't been updated since last check
		if ($user['session_time'] > $this->config['banlist_last_update'] && !defined('SKIP_CHECK_BAN'))
		{
			define('SKIP_CHECK_BAN', true);
		}

		return parent::validate_session($user);
	}
}
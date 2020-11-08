<?php

/**
* @package   s9e\Reckless
* @copyright Copyright (c) 2018-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\reckless;

use phpbb\user;
use phpbb\user_loader;

class UserLoader extends user_loader
{
	/**
	* @var user
	*/
	protected $user;

	/**
	* {@inheritdoc}
	*/
	public function load_users(array $user_ids, array $ignore_types = [])
	{
		if (in_array($this->user->data['user_id'], $user_ids))
		{
			$this->users[$this->user->data['user_id']] = $this->user->data;
		}

		return parent::load_users($user_ids, $ignore_types);
	}

	protected function setUser(user $user)
	{
		$this->user = $user;
	}
}
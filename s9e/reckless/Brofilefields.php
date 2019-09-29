<?php

/**
* @package   s9e\Reckless
* @copyright Copyright (c) 2018-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\reckless;

use phpbb\cache\driver\driver_interface;
use phpbb\profilefields\manager;

class Brofilefields extends manager
{
	/**
	* @var driver_interface
	*/
	protected $cache;

	public function setCache(driver_interface $cache)
	{
		$this->cache = $cache;
	}

	/**
	* {@inheritdoc}
	*/
	protected function build_cache()
	{
		$key = '_brofilefields_' . $this->user->lang_name . '_' . (!$this->auth->acl_gets('a_', 'm_') && !$this->auth->acl_getf_global('m_'));

		$this->profile_cache = $this->cache->get($key);
		if ($this->profile_cache === false)
		{
			parent::build_cache();
			$this->cache->put($key, $this->profile_cache, 600);
		}
	}
}
<?php

/**
* @package   s9e\reckless
* @copyright Copyright (c) 2018 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\reckless;

use phpbb\db\driver\mysqli;
use phpbb\extension\base;

class ext extends base
{
	public function is_enableable()
	{
		return $this->container->get('dbal.conn')->get_sql_layer() === 'mysqli';
	}
}
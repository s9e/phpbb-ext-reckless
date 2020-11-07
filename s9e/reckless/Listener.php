<?php

/**
* @package   s9e\Reckless
* @copyright Copyright (c) 2018-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\reckless;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use phpbb\config\config;
use phpbb\db\driver\driver_interface as db;

class Listener implements EventSubscriberInterface
{
	/**
	* @var config
	*/
	protected $config;

	/**
	* @var array
	*/
	protected $forumCache = [];

	/**
	* @var string
	*/
	protected $topicsTable;

	public function __construct(config $config, db $db, string $topicsTable)
	{
		$this->config      = $config;
		$this->db          = $db;
		$this->topicsTable = $topicsTable;
	}

	public static function getSubscribedEvents()
	{
		return [
			'core.acp_ban_after'                             => 'onBan',
			'core.phpbb_content_visibility_get_visibility_sql_before' => 'onVisibilitySql',
			'core.search_modify_param_after'                 => 'onSearch',
			'core.viewforum_get_announcement_topic_ids_data' => 'onViewforumAnnouncementQuery',
			'core.viewforum_get_topic_ids_data'              => 'onViewforumTopicsQuery',
			'core.viewforum_modify_page_title'               => 'onViewforum',
			'core.viewforum_modify_sort_data_sql'            => 'onViewforumCutoffQuery',
			'core.viewtopic_modify_forum_id'                 => 'onViewtopic',
//			'core.viewtopic_modify_post_data'                => 'onViewtopicQuery'
		];
	}

	public function onBan($event)
	{
		// Round up current timestamp to reduce the risks of race conditions
		$this->config->set('banlist_last_update', 1 + time());
	}

	public function onSearch($event)
	{
		if ($event['search_id'] === 'unanswered' && $event['show_results'] === 'topics')
		{
			$event['sql'] = $this->pruneSearchUnanswered($event['sql']);
		}
	}

	public function onViewforum($event)
	{
		$this->forumCache[$event['forum_id']] = $event['forum_data'];
	}

	/**
	* Force the query to use the forum_id_type index
	*/
	public function onViewforumAnnouncementQuery($event)
	{
		$this->forceIndex($event, $this->topicsTable, 'forum_id_type');
	}

	/**
	* Rewrite the query to count normal topics and announcements separately using different indexes
	*/
	public function onViewforumCutoffQuery($event)
	{
		$regexp = '(\\s*OR t.topic_type = ' . POST_ANNOUNCE . '\\s*OR t.topic_type = ' . POST_GLOBAL . ')';

		$sql = $event['sql_array'];
		if ($sql['SELECT'] !== 'COUNT(t.topic_id) AS num_topics' || !preg_match($regexp, $sql['WHERE']))
		{
			// If the current query doesn't match the default query, we don't attempt to rewrite
			// it and instead we force the appropriate index
			$this->forceIndex($event, $this->topicsTable, 'listing_order');

			return;
		}

		// Adjust the SELECT column and remove the topic_type predicate from WHERE
		$sql['SELECT'] = 'COUNT(*)';
		$sql['WHERE']  = preg_replace($regexp, '', $sql['WHERE']);

		$sqlNormal  = $sql;
		$sqlSpecial = $sql;

		$sqlNormal['FROM']  = [$this->topicsTable => 't FORCE INDEX (listing_order)'];
		$sqlSpecial['FROM'] = [$this->topicsTable => 't FORCE INDEX (forum_id_type)'];

		$sqlNormal['WHERE']  .= ' AND t.topic_type IN (' . POST_NORMAL . ', ' . POST_STICKY . ')';
		$sqlSpecial['WHERE'] .= ' AND t.topic_type IN (' . POST_ANNOUNCE . ', ' . POST_GLOBAL . ')';

		$queryNormal  = $this->db->sql_build_query('SELECT', $sqlNormal);
		$querySpecial = $this->db->sql_build_query('SELECT', $sqlSpecial);

		$event['sql_array'] = [
			'SELECT' => 'num_topics',
			'FROM'   => [
				// The query builder requires a table so we create a derived table
				'(SELECT (' . $queryNormal . ') + (' . $querySpecial . ') AS num_topics)' => 't'
			]
		];
	}

	/**
	* Rewrite the query to better utilize indexes if more than one forum_id is selected
	*/
	public function onViewforumTopicsQuery($event)
	{
		$sql    = $event['sql_ary'];
		$regexp = '(t\\.forum_id\\s*IN\\s*\\(([^\\)]++)\\))';
		if (!preg_match($regexp, $sql['WHERE'], $m) || $event['sql_start'] > 0)
		{
			// Bail if there's only one forum_id or this is not the first page of results
			return;
		}
		preg_match_all('(\\d+)', $m[0], $m);
		$forumIds = $m[0];

		$forumSql            = $sql;
		$forumSql['SELECT'] .= ', ' . preg_replace('( (?:A|DE)SC)', '', $sql['ORDER_BY']);

		$subqueries = [];
		foreach ($forumIds as $forumId)
		{
			$forumSql['WHERE'] = preg_replace($regexp, 't.forum_id = ' . $forumId, $sql['WHERE']);

			$subqueries[] = $this->db->sql_build_query('SELECT', $forumSql) . ' LIMIT ' . $event['sql_limit'];
		}

		$event['sql_ary'] = [
			'SELECT'   => preg_replace('(\\w+\\.)', 't.', $sql['SELECT']),
			'FROM'     => ["(\n(" . implode(")\nUNION ALL\n(", $subqueries) . ")\n)" => 't'],
			'ORDER_BY' => preg_replace('(\\w+\\.)', 't.', $sql['ORDER_BY'])
		];
	}

	public function onViewtopic($event)
	{
		$this->forumCache[$event['topic_data']['forum_id']] = $event['topic_data'];
	}

	public function onViewtopicQuery($event)
	{
		$userCache = $event['user_cache'];
		$userIds   = [];
		foreach (['post_delete_user', 'post_edit_user'] as $key)
		{
			foreach ($event['rowset'] as $row)
			{
				$userId = $row[$key];
				if ($userId && !isset($userCache[$userId]))
				{
					$userIds[] = $userId;
				}
			}
		}
		if (empty($userIds))
		{
			return;
		}

		$sql = 'SELECT * FROM ' . USERS_TABLE . ' WHERE ' . $this->db->sql_in_set('user_id', $userIds);
//		die($sql);
	}

	public function onVisibilitySql($event)
	{
		$forum_id   = $event['forum_id'];
		$prefix     = 'forum_' . $event['mode'] . 's_';
		$deleted    = $this->forumCache[$forum_id][$prefix . 'softdeleted'] ?? 1;
		$unapproved = $this->forumCache[$forum_id][$prefix . 'unapproved']  ?? 1;

		// If there are no deleted or unapproved items then we can limit the query to approved items
		// and make full use of the covering indexes
		if ($deleted + $unapproved === 0)
		{
			$event['get_visibility_sql_overwrite'] = $event['table_alias'] . $event['mode'] . '_visibility = ' . ITEM_APPROVED;
		}
	}

	protected function forceIndex($event, $table, $index)
	{
		$k   = isset($event['sql_ary']) ? 'sql_ary' : 'sql_array';
		$sql = $event[$k];
		if (isset($sql['FROM'][$table]) && strpos($sql['FROM'][$table], ' ') === false)
		{
			$sql['FROM'][$table] .= ' FORCE INDEX (' . $index . ')';
		}
		$event[$k] = $sql;
	}

	/**
	* Remove the posts table from the "unanswered" search if it's not actually being used
	*/
	protected function pruneSearchUnanswered(string $sql): string
	{
		$old = $sql;
		$sql = str_replace(', p.topic_id', ', t.topic_id', $sql);
		$sql = str_replace('AND p.topic_id = t.topic_id', '', $sql);
		$sql = preg_replace('(AND p\\.forum_id NOT IN \\([0-9, ]++\\))', '', $sql);

		if (strpos($sql, 'p.') !== false)
		{
			return $old;
		}

		$sql = str_replace('SELECT DISTINCT', 'SELECT', $sql);
		$sql = preg_replace('(FROM \\K.*?posts p, )', '', $sql);

		return $sql;
	}
}
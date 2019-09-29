<?php

/**
* @package   s9e\Reckless
* @copyright Copyright (c) 2018-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\reckless;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use phpbb\db\driver\driver_interface as db;

class Listener implements EventSubscriberInterface
{
	/**
	* @string
	*/
	protected $topicsTable;

	public function __construct(db $db, string $topicsTable)
	{
		$this->db          = $db;
		$this->topicsTable = $topicsTable;
	}

	public static function getSubscribedEvents()
	{
		return [
			'core.viewforum_get_announcement_topic_ids_data' => 'onViewforumAnnouncementQuery',
			'core.viewforum_get_topic_ids_data'              => 'onViewforumTopicsQuery',
			'core.viewforum_modify_sort_data_sql'            => 'onViewforumCutoffQuery'
		];
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
}
The queries shown here are based on actual queries executed by phpBB, reformatted for readability. Only the relevant parts of MySQL's EXPLAIN's output are shown.


## core.viewforum_get_announcement_topic_ids_data

When planning this query, MySQL considers the `forum_id_type` index but opts out in favor of a full table scan if the range of rows covered by the `forum_id` predicates is too great. An index hint forces it to always use `forum_id_type`.

Default query:

```sql
   SELECT t.*, f.forum_name
     FROM phpbb_topics t
LEFT JOIN phpbb_forums f ON (f.forum_id = t.forum_id)
    WHERE (t.forum_id = ? AND t.topic_type = 2)
       OR (t.forum_id IN (?, ?, ?, ?, ?, ?, ?, ?, ?) AND t.topic_type = 3)
 ORDER BY t.topic_time DESC
```
```
        table: t
         type: ALL
possible_keys: forum_id,forum_id_type,fid_time_moved,forum_vis_last,latest_topics,s9e_listing_order
          key: NULL
      key_len: NULL
          ref: NULL
         rows: 142033
        Extra: Using where; Using filesort
```

Hinted query:

```sql
   SELECT t.*, f.forum_name
     FROM phpbb_topics t FORCE INDEX (forum_id_type)
LEFT JOIN phpbb_forums f ON (f.forum_id = t.forum_id)
    WHERE (t.forum_id = ? AND t.topic_type = 2)
       OR (t.forum_id IN (?, ?, ?, ?, ?, ?, ?, ?, ?) AND t.topic_type = 3)
 ORDER BY t.topic_time DESC
```
```
        table: t
         type: range
possible_keys: forum_id_type
          key: forum_id_type
      key_len: 4
          ref: NULL
         rows: 34716
        Extra: Using index condition; Using filesort
```


## core.viewforum_get_topic_ids_data

Default query:

```sql
  SELECT t.topic_id FROM (phpbb_topics t) WHERE t.forum_id IN (?, ?, ?)
     AND t.topic_type IN (0, 1)
     AND t.topic_visibility = 1
ORDER BY t.topic_type DESC, t.topic_last_post_time DESC, t.topic_last_post_id DESC
   LIMIT 25
```
```
  select_type: SIMPLE
        table: t
         type: ALL
possible_keys: forum_id,forum_id_type,fid_time_moved,topic_visibility,forum_vis_last,latest_topics,listing_order
          key: NULL
      key_len: NULL
          ref: NULL
         rows: 142033
        Extra: Using where; Using filesort
```

Rewritten query:

```sql
SELECT t.topic_id FROM ((
     (SELECT t.topic_id, t.topic_type, t.topic_last_post_time, t.topic_last_post_id
        FROM (phpbb_topics t) WHERE t.forum_id = ?
         AND t.topic_type IN (0, 1)
         AND t.topic_visibility = 1
    ORDER BY t.topic_type DESC, t.topic_last_post_time DESC, t.topic_last_post_id DESC
       LIMIT 25)
UNION ALL
     (SELECT t.topic_id, t.topic_type, t.topic_last_post_time, t.topic_last_post_id
        FROM (phpbb_topics t) WHERE t.forum_id = ?
         AND t.topic_type IN (0, 1)
         AND t.topic_visibility = 1
    ORDER BY t.topic_type DESC, t.topic_last_post_time DESC, t.topic_last_post_id DESC
       LIMIT 25)
UNION ALL
     (SELECT t.topic_id, t.topic_type, t.topic_last_post_time, t.topic_last_post_id
        FROM (phpbb_topics t) WHERE t.forum_id = ?
         AND t.topic_type IN (0, 1)
         AND t.topic_visibility = 1
    ORDER BY t.topic_type DESC, t.topic_last_post_time DESC, t.topic_last_post_id DESC
       LIMIT 25)
) t)
ORDER BY t.topic_type DESC, t.topic_last_post_time DESC, t.topic_last_post_id DESC
LIMIT 25
```
```
  select_type: DERIVED
        table: t
         type: ref
possible_keys: forum_id,forum_id_type,fid_time_moved,topic_visibility,forum_vis_last,latest_topics,listing_order
          key: listing_order
      key_len: 4
          ref: const,const
         rows: 23393
        Extra: Using where

  select_type: UNION
        table: t
         type: ref
possible_keys: forum_id,forum_id_type,fid_time_moved,topic_visibility,forum_vis_last,latest_topics,listing_order
          key: listing_order
      key_len: 4
          ref: const,const
         rows: 81004
        Extra: Using where

  select_type: UNION
        table: t
         type: ref
possible_keys: forum_id,forum_id_type,fid_time_moved,topic_visibility,forum_vis_last,latest_topics,listing_order
          key: listing_order
      key_len: 4
          ref: const,const
         rows: 944
        Extra: Using where
```


## core.viewforum_modify_sort_data_sql

Default query:

```sql
SELECT COUNT(t.topic_id) AS num_topics
  FROM phpbb_topics t
 WHERE t.forum_id = ?
   AND (t.topic_last_post_time >= ?
        OR t.topic_type = 2
        OR t.topic_type = 3)
   AND t.topic_visibility = 1
```
```
        table: t
         type: ref
possible_keys: forum_id,forum_id_type,last_post_time,fid_time_moved,topic_visibility,forum_vis_last,latest_topics,listing_order
          key: latest_topics
      key_len: 3
          ref: const
         rows: 81004
        Extra: Using where
```

Rewritten query:

```sql
SELECT num_topics FROM ((SELECT (
    SELECT COUNT(*)
      FROM phpbb_topics t FORCE INDEX (listing_order)
     WHERE t.forum_id = ?
       AND t.topic_last_post_time >= ?
       AND t.topic_visibility = 1
       AND t.topic_type IN (0, 1)
) + (
    SELECT COUNT(*)
      FROM phpbb_topics t FORCE INDEX (forum_id_type)
     WHERE t.forum_id = ?
       AND t.topic_last_post_time >= ?
       AND t.topic_visibility = 1
       AND t.topic_type IN (2, 3)
) AS num_topics) t)
```
```
           id: 4
  select_type: SUBQUERY
        table: t
         type: range
possible_keys: forum_id_type
          key: forum_id_type
      key_len: 4
          ref: NULL
         rows: 2
        Extra: Using index condition; Using where

           id: 3
  select_type: SUBQUERY
        table: t
         type: range
possible_keys: listing_order
          key: listing_order
      key_len: 9
          ref: NULL
         rows: 1004
        Extra: Using where; Using index
```

Hinted query: (only used as a fallback if the query cannot be rewritten)

```sql
SELECT COUNT(t.topic_id) AS num_topics
  FROM phpbb_topics t FORCE INDEX (listing_order)
 WHERE t.forum_id = ?
   AND (t.topic_last_post_time >= ?
        OR t.topic_type = 2
        OR t.topic_type = 3)
   AND t.topic_visibility = 1
```
```
        table: t
         type: ref
possible_keys: listing_order
          key: listing_order
      key_len: 4
          ref: const,const
         rows: 100694
        Extra: Using index condition
```

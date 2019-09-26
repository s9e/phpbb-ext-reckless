Query times are for illustration purposes only. Actual performance vary depending on hardware, MySQL version, server configuration and database content.

The queries shown here are based on actual queries executed by phpBB, reformatted for readability and with index hints used to force a particular query plan. IDs and other dynamic values are replaced with `?` as a placeholder.


## phpbb_forums_watch.user_id

`user_id` `forum_id`

This index replaces the default index on `(user_id)` to cover the whole predicate from `watch_topic_forum()`.


## phpbb_notifications.most_recent

`user_id` `notification_time`

This index eliminates the filesort used to retrieve the most recent notifications for a given user. This type of query is used on nearly every page.

```sql
  SELECT n.*, nt.notification_type_name
    FROM phpbb_notifications n, phpbb_notification_types nt
   WHERE n.user_id = ?
     AND nt.notification_type_id = n.notification_type_id
     AND nt.notification_type_enabled = 1
ORDER BY n.notification_time DESC
   LIMIT 5
```
```
        table: n
         type: ref
possible_keys: item_ident,user,most_recent
          key: most_recent
      key_len: 4
          ref: const
         rows: 144
        Extra: Using where
```
```sql
  SELECT n.*, nt.notification_type_name
    FROM phpbb_notifications n IGNORE INDEX (most_recent), phpbb_notification_types nt
   WHERE n.user_id = ?
     AND nt.notification_type_id = n.notification_type_id
     AND nt.notification_type_enabled = 1
ORDER BY n.notification_time DESC
   LIMIT 5
```
```
        table: n
         type: ref
possible_keys: item_ident,user
          key: user
      key_len: 4
          ref: const
         rows: 146
        Extra: Using where; Using filesort
```


## phpbb_posts.reading_order

`topic_id` `post_visibility` `post_time` `post_id`

This index can be used to select a topic's visible posts in chronological order--for instance in viewtopic--with no filesort. It can reduce the query times by up to 98% (50x speedup) on topics with more than 5000 posts. It is also used to select the first unread post of a topic and when filtering posts to those created in the last X days.

Below is the type of query used in viewtopic.

```sql
  SELECT p.post_id
    FROM phpbb_posts p USE INDEX (reading_order)
   WHERE p.topic_id = ?
     AND p.post_visibility = 1
ORDER BY p.post_time ASC, p.post_id ASC
   LIMIT 15
```
```
        table: p
         type: ref
possible_keys: reading_order
          key: reading_order
      key_len: 5
          ref: const,const
         rows: 13836
        Extra: Using where; Using index
```
```sql
  SELECT p.post_id
    FROM phpbb_posts p IGNORE INDEX (reading_order)
   WHERE p.topic_id = ?
     AND p.post_visibility = 1
ORDER BY p.post_time ASC, p.post_id ASC
   LIMIT 15
```
```
        table: p
         type: ref
possible_keys: topic_id,tid_post_time,post_visibility
          key: topic_id
      key_len: 4
          ref: const
         rows: 11593
        Extra: Using where; Using filesort
```

Below is the query used in viewtopic to find the first unread post.

```sql
  SELECT post_id, topic_id, forum_id
    FROM phpbb_posts FORCE INDEX (reading_order)
   WHERE topic_id = ?
     AND post_visibility = 1
     AND post_time > ?
     AND forum_id = ?
ORDER BY post_time ASC, post_id ASC
   LIMIT 1
```
```
        table: phpbb_posts
         type: range
possible_keys: reading_order
          key: reading_order
      key_len: 9
          ref: NULL
         rows: 103
        Extra: Using index condition; Using where
```
```sql
  SELECT post_id, topic_id, forum_id
    FROM phpbb_posts IGNORE INDEX (reading_order)
   WHERE topic_id = ?
     AND post_visibility = 1
     AND post_time > ?
     AND forum_id = ?
ORDER BY post_time ASC, post_id ASC
   LIMIT 1
```
```
        table: phpbb_posts
         type: range
possible_keys: forum_id,topic_id,tid_post_time,post_visibility
          key: tid_post_time
      key_len: 8
          ref: NULL
         rows: 95
        Extra: Using index condition; Using where; Using filesort
```

Below is a query used in viewtopic when filtering posts to only display those posted in the last X days.

```sql
SELECT COUNT(post_id) AS num_posts
  FROM phpbb_posts USE INDEX (reading_order)
 WHERE topic_id = ?
   AND post_time >= ?
   AND post_visibility = 1
```
```
        table: phpbb_posts
         type: range
possible_keys: reading_order
          key: reading_order
      key_len: 9
          ref: NULL
         rows: 17
        Extra: Using where; Using index
```

```sql
SELECT COUNT(post_id) AS num_posts
  FROM phpbb_posts IGNORE INDEX (reading_order)
 WHERE topic_id = ?
   AND post_time >= ?
   AND post_visibility = 1
```
```
        table: phpbb_posts
         type: range
possible_keys: topic_id,tid_post_time,post_visibility
          key: tid_post_time
      key_len: 8
          ref: NULL
         rows: 16
        Extra: Using index condition; Using where
```


## phpbb_topics.listing_order

`forum_id` `topic_visibility` `topic_type` `topic_last_post_time` `topic_last_post_id`

Similar to `phpbb_posts.reading_order`, this index can be used to select a forum's visible topics in chronological order--for instance in viewforum--with no filesort.

```sql
  SELECT t.topic_id
    FROM phpbb_topics t FORCE INDEX (listing_order)
   WHERE t.forum_id = ?
     AND t.topic_type IN (0, 1)
     AND t.topic_visibility = 1
ORDER BY t.topic_type DESC, t.topic_last_post_time DESC, t.topic_last_post_id DESC
   LIMIT 25
```
```
        table: t
         type: range
possible_keys: listing_order
          key: listing_order
      key_len: 5
          ref: NULL
         rows: 100694
        Extra: Using index condition
```
```sql
  SELECT t.topic_id
    FROM phpbb_topics t FORCE INDEX (listing_order)
   WHERE t.forum_id = ?
     AND t.topic_type IN (0, 1)
     AND t.topic_visibility = 1
ORDER BY t.topic_type DESC, t.topic_last_post_time DESC, t.topic_last_post_id DESC
   LIMIT 25
```
```
        table: t
         type: ref
possible_keys: forum_id,forum_id_type,fid_time_moved,topic_visibility,forum_vis_last,latest_topics
          key: latest_topics
      key_len: 3
          ref: const
         rows: 81004
        Extra: Using where; Using filesort
```


## phpbb_sessions.session_fid

`session_forum_id` `session_user_id`

This index replace the default index on `(session_forum_id)`. It can be used to retrieve a list of users browsing a given forum.


## phpbb_topics_watch.user_id

`user_id` `topic_id`

This index replaces the default index on `(user_id)`. It can be used in viewtopic to cover the whole predicate from `watch_topic_forum()` and in the UCP pages that lists watched topics for current user.


## phpbb_bbcodes.display_on_post

`display_on_posting` `bbcode_tag`

This index replaces the default index on `(display_on_posting)` and eliminates the filesort in `display_custom_bbcodes()`.

```sql
  SELECT b.bbcode_id, b.bbcode_tag, b.bbcode_helpline
    FROM (phpbb_bbcodes b)
   WHERE b.display_on_posting = 1
ORDER BY b.bbcode_tag
```


## phpbb_drafts.user_drafts

`user_id` `forum_id` `topic_id` `save_time`

This index is used whenever the posting editor is loaded. A query is executed to determine whether current user has any drafts in order to display the "Load draft" button. When pressed, drafts are loaded in order of `save_time`.

```sql
SELECT draft_id
  FROM phpbb_drafts
 WHERE user_id = ? AND forum_id = ? AND topic_id = ?
 LIMIT 1
```


## phpbb_profile_fields.display

`field_active` `field_no_view` `field_hide` `field_order`

This index eliminates the temporary table when viewing a topic or a profile, and also eliminates the filesort if the current user is not an admin or a mod.

```sql
  SELECT l.*, f.*
    FROM phpbb_profile_lang l, phpbb_profile_fields f
   WHERE l.lang_id = ?
     AND f.field_active = 1
     AND f.field_hide = 0
     AND f.field_no_view = 0
     AND l.field_id = f.field_id
ORDER BY f.field_order
```
```
        table: f
         type: ref
possible_keys: PRIMARY,display
          key: display
      key_len: 3
          ref: const,const,const
         rows: 7
        Extra: Using where

        table: l
         type: eq_ref
possible_keys: PRIMARY
          key: PRIMARY
      key_len: 6
          ref: phpbb.f.field_id,const
         rows: 1
        Extra: 
```


## phpbb_user_group.user_id

`user_id` `user_pending`


SELECT yvs.`id`, yvs.`title` as series_title, yv.`title` as video_title, REGEXP_SUBSTR(yv.`title`, '\\d+') as episode, REGEXP_SUBSTR(yv.`title`, '\\d+', 1, 2) as part
FROM `youtube_video_series` yvs
INNER JOIN `user_yvideo` uyv ON uyv.`series_id`=yvs.`id` AND uyv.`user_id`=2
INNER JOIN `youtube_video` yv ON yv.`id`=uyv.`video_id`
WHERE yvs.`id`=1

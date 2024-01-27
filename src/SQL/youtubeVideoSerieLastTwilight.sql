SELECT yv.`id`, yvs.`title` as series_title, yv.`title` as video_title, CAST(REGEXP_SUBSTR(yv.`title`, '\\d+') AS UNSIGNED) as episode, REGEXP_SUBSTR(yv.`title`, '\\d+', 1, 2) as part
FROM `youtube_video_series` yvs
INNER JOIN `user_yvideo` uyv ON uyv.`series_id`=yvs.`id` AND uyv.`user_id`=2
INNER JOIN `youtube_video` yv ON yv.`id`=uyv.`video_id`
WHERE yv.`title` REGEXP '.+Last Twilight ภาพนายไม่เคยลืม \\| EP.?(\\d+)(?:\\s{1}\\[(\\d)\\/4\\]){1}.*'
ORDER BY episode ASC, part ASC

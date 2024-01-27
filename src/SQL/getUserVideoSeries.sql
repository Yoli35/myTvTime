SELECT yvs.id, yvs.title, yvs.format, yvs.regex, yvs.matches
FROM user_yvideo uyv 
INNER JOIN youtube_video_series yvs ON yvs.id = uyv.series_id
WHERE uyv.user_id=2 AND uyv.series_id IS NOT NULL
GROUP BY yvs.`id`

SELECT yv.`id`, yv.`title`
FROM `youtube_video` yv
INNER JOIN `user_youtube_video` uyv ON uyv.`user_id`=2 AND uyv.`youtube_video_id`=yv.`id`
WHERE yv.`title` REGEXP '.+Last Twilight ภาพนายไม่เคยลืม \\| EP.?(\\d+)(?:\\s{1}\\[(\\d)\\/4\\]){1}.*'

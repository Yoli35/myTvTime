UPDATE user_yvideo uyv, (SELECT id FROM `youtube_video`
						 WHERE id IN
						 		  (SELECT id FROM `youtube_video`
						 		   WHERE `title` LIKE '%Stern DuTube%'))
						 	 AS stern_videos
SET uyv.`series_id`=2
WHERE uyv.`video_id`=stern_videos.`id`


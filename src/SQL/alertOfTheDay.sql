SELECT a.`id` as id, s.`id` as series_id, a.`message` as message, a.`provider_id` as provider_id, a.`episode_number` as alert_episode_number, a.`season_number` as alert_season_number,
 sv.`number_of_episodes` as number_of_episodes, sv.`number_of_seasons` as number_of_seasons, sv.`viewed_episodes` as viewed_episodes,
 s.`name` as name, s.`original_name` as original_name,
 sln.`name` as localized_name,
 se.`poster_path` as season_poster_path, ep.`still_path` as episode_still_path
FROM `alert` a
INNER JOIN `serie_viewing` sv ON sv.id=a.`serie_viewing_id`
LEFT JOIN `serie` s ON s.id=sv.`serie_id`
LEFT JOIN `serie_localized_name` sln ON sln.`serie_id`=s.id
LEFT JOIN `season` se ON se.`series_id`=s.id AND se.`season_number`=a.`season_number`
LEFT JOIN `episode` ep ON ep.`season_id`=se.id AND ep.`episode_number` =a.`episode_number`
WHERE a.`user_id`=2 AND a.`activated`=1 AND ( (sv.`time_shifted`=0 AND DATE(a.`date`)=DATE(NOW()) ) OR( sv.`time_shifted`=1 AND DATE(a.`date`)=DATE_SUB(DATE(NOW()), INTERVAL 1 DAY)) )
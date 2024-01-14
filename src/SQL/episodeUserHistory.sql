SELECT s.`id`, s.`name`, sln.`name`, seav.`season_number`, epiv.`episode_number`, DATE(epiv.`viewed_at`),
		s.`poster_path` as serie_poster_path,
        ROW_NUMBER() OVER (ORDER BY epiv.`viewed_at` DESC) as offset
FROM `episode_viewing` epiv
LEFT JOIN `season_viewing` seav ON seav.`id`=epiv.`season_id`
LEFT JOIN `serie_viewing` serv ON serv.`id`=seav.`serie_viewing_id`
LEFT JOIN `serie` s ON s.`id`=serv.`serie_id`
LEFT JOIN `serie_localized_name` sln ON sln.`serie_id`=s.`id` AND sln.`locale`='fr'
/*INNER JOIN `season` season ON season.`series_id`=s.`id`
INNER JOIN `episode` episode ON episode.`season_id`=season.`id`*/
WHERE serv.`user_id`=2
ORDER BY epiv.`viewed_at` DESC
LIMIT 40

SELECT s.`id` as id, s.`serie_id` as tmdbId, sv.`id` as svId, 
		s.`name` as name, sln.`name` as localizedName,
		sev.`season_number` as seasonNumber, epv.`episode_number` as episodeNumber,
		s.`first_date_air` as firstDateAir, s.`created_at` as createdAt, s.`updated_at` as updatedAt,
		s.`status` as status, s.`overview` as overview, sao.`overview` as alternateOverview,
		s.`number_of_episodes` as numberOfEpisodes, s.`number_of_seasons` as numberOfSeasons,
		sv.`viewed_episodes` as viewedEpisodes, (sv.`viewed_episodes` / s.`number_of_episodes`) as progress,
		s.`original_name` as originalName, s.`origin_country` as originCountry, s.`episode_durations` as episodeDurations,
		s.`upcoming_date_year` as upcomingDateYear, s.`upcoming_date_month` as upcomingDateMonth,
		s.`direct_link` as directLink,
		s.`poster_path` as posterPath, s.`backdrop_path` as backdropPath
FROM `serie` s
INNER JOIN `serie_user` su ON su.`user_id`=2 AND su.`serie_id`=s.`id`
INNER JOIN `serie_viewing` sv ON sv.`user_id`=2 AND sv.`serie_id`=s.`id`
LEFT JOIN `episode_viewing` epv ON epv.`id`=sv.`next_episode_to_watch_id`/* OR sv.`next_episode_to_watch_id`=NULL*/
LEFT JOIN `season_viewing` sev ON sev.`id`=epv.`season_id`/* OR sv.`next_episode_to_watch_id`=NULL*/
LEFT JOIN `serie_localized_name` sln ON sln.`serie_id`=s.`id` AND sln.`locale`='fr'
LEFT JOIN `serie_alternate_overview` sao ON sao.`series_id`=s.`id` AND sao.`locale`='fr' 
ORDER BY s.`id` DESC
LIMIT 120 OFFSET 0
SELECT m.`title` as `title`, m.`id` as `id`, m.`movie_db_id` as tmdbId, m.`poster_path` as `poster_path`, m.`release_date` as `release_date`
FROM `rating` r
INNER JOIN `user` u ON u.`id`=r.`user_id`
INNER JOIN `movie` m ON m.`id`=r.`movie_id`
WHERE r.`value`=5 AND u.`id`=2
ORDER BY m.`release_date` DESC
LIMIT 20
OFFSET 60

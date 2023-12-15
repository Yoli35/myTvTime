SELECT s.`name`, s.`serie_id`, sv.`viewed_episodes` / sv.`number_of_episodes` as progress
FROM `serie_viewing` sv
INNER JOIN `serie` s ON s.id=sv.`serie_id`
WHERE sv.`user_id`=2
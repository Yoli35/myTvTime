SELECT COUNT(*),
	   SUM(ad.`distance`) as total_disstance, AVG(ad.`distance`) as average_distance,
	   SUM(ad.`steps`) as total_steps, AVG(ad.`steps`) as average_steps,
	   SUM(ad.`exercise_result`) as total_exercise, AVG(ad.`exercise_result`) as average_exercise,
		SUM(ad.`move_result`) as total_move, AVG(ad.`move_result`) as average_move,
		MIN(ad.`stand_up_result`) as min_stand_up, MAX(ad.`stand_up_result`) as max_stand_up, AVG(ad.`stand_up_result`) as average_stand_up
FROM `activity_day` ad
INNER JOIN `activity` a ON a.id=ad.`activity_id`
WHERE MONTH(ad.`day`)=12
	AND DATE(ad.`day`)<DATE(NOW())
	AND a.`user_id`=2
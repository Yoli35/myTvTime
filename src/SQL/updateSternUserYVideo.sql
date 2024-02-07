SELECT s.`id`,
	GROUP_CONCAT(sao.`logo_path` SEPARATOR "|||") sao_logo_paths,
	GROUP_CONCAT(sao.`overview`SEPARATOR "|||") as sao_overviews,
	GROUP_CONCAT(sao.`source` SEPARATOR "|||") sao_sources,
	GROUP_CONCAT(sao.`url` SEPARATOR "|||") sao_urls
	
FROM `serie` s
LEFT JOIN `serie_alternate_overview` sao ON sao.`series_id`=s.`id`
WHERE s.`id` > 830
GROUP BY s.`id`
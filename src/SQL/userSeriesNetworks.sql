SELECT s.`id` as id, n.`name` as networkName, n.`logo_path` as networkLogoPath, n.`origin_country` as networkOriginCountry
FROM `serie` s
INNER JOIN `serie_networks` sn ON sn.`serie_id`=s.`id`
INNER JOIN `networks` n ON n.`id`=sn.`networks_id`
WHERE s.`id` IN (830,829,828,827,826,825,824,823,822,821,820,819,818,817,816,815,814,813,812,811)

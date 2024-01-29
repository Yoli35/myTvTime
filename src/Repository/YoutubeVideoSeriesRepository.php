<?php

namespace App\Repository;

use App\Entity\YoutubeVideoSeries;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<YoutubeVideoSeries>
 *
 * @method YoutubeVideoSeries|null find($id, $lockMode = null, $lockVersion = null)
 * @method YoutubeVideoSeries|null findOneBy(array $criteria, array $orderBy = null)
 * @method YoutubeVideoSeries[]    findAll()
 * @method YoutubeVideoSeries[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class YoutubeVideoSeriesRepository extends ServiceEntityRepository
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($registry, YoutubeVideoSeries::class);
    }

    public function save(YoutubeVideoSeries $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function findVideosBySeries(int $userId, YoutubeVideoSeries $series): array
    {
        $format = $series->getFormat();
        $matches = $series->getMatches();

        dump([
            'series' => $series,
            'matches' => $matches,
        ]);

        $sql = "SELECT yv.`id` as id, yv.`link` as link, yv.`title` as title, "
            . "yv.thumbnail_high_path as thumbnailHighPath, yv.published_at as publishedAt, "
            . "yv.content_duration as contentDuration, "
            . "uyv.hidden as hidden, "
            . "yc.youtube_id as channelYoutubeId, yc.thumbnail_default_url as channelThumbnailDefaultUrl, "
            . "yc.title as channelTitle, yc.custom_url as channelCustomUrl";
        if ($series->isRegex()) {
            if (count($matches)) {
                foreach ($matches as $match) {
                    if ($match['type'] === 'UNSIGNED')
                        $sql .= ", CAST(REGEXP_SUBSTR(yv.`title`, '" . $match['expr'] . "', " . $match['position'] . ", " . $match['occurrence'] . ") AS " . $match['type'] . ") as " . $match['name'] . " ";
                    else
                        $sql .= ", REGEXP_SUBSTR(yv.`title`, '" . $match['expr'] . "', " . $match['position'] . ", " . $match['occurrence'] . ") as " . $match['name'] . " ";
                }
            }
            $sql .= " FROM `youtube_video_series` yvs "
                . "INNER JOIN `user_yvideo` uyv ON uyv.`series_id`=yvs.`id` AND uyv.`user_id`=" . $userId . " "
                . "INNER JOIN `youtube_video` yv ON yv.`id`=uyv.`video_id` "
                . "LEFT JOIN `youtube_channel` yc ON yc.`id`=yv.`channel_id` "
                . "WHERE yv.`title` REGEXP '" . $format . "' "
                . "ORDER BY ";
            foreach ($matches as $match) {
                $sql .= $match['name'] . " ASC, ";
            }
            $sql .= "yv.published_at ASC";
        } else {
            $sql .= " FROM `youtube_video` yv "
                . "LEFT JOIN 'youtube_channel' yc ON yc.`id`=yv.`channel_id` "
                . "WHERE yv.`title` LIKE '%" . $series->getFormat() . "%'"
                . "ORDER BY yv.published_at ASC";
        }

        dump([
            'sql' => $sql,
        ]);

        return $this->registry->getManager()
            ->getConnection()->prepare($sql)
            ->executeQuery()
            ->fetchAllAssociative();
    }

    public function findVideosByFormat(int $userId, string $format, bool $regex): array
    {
//        $matches = $series->getMatches();

        $sql = "SELECT yv.`id` as id, yv.`link` as link, yv.`title` as title, "
            . "yv.thumbnail_high_path as thumbnailHighPath, yv.published_at as publishedAt, "
            . "yv.content_duration as contentDuration, "
            . "uyv.hidden as hidden ";
        if ($regex === true) {
//            if (count($matches)) {
//                foreach ($matches as $match) {
//                    if ($match['type'] === 'UNSIGNED')
//                        $sql .= ", CAST(REGEXP_SUBSTR(yv.`title`, '" . $match['expr'] . "', " . $match['position'] . ", " . $match['occurrence'] . ") AS " . $match['type'] . ") as " . $match['name'] . " ";
//                    else
//                        $sql .= ", REGEXP_SUBSTR(yv.`title`, '" . $match['expr'] . "', " . $match['position'] . ", " . $match['occurrence'] . ") as " . $match['name'] . " ";
//                }
//            }
            $sql .= " FROM `youtube_video_series` yvs "
                . "INNER JOIN `user_yvideo` uyv ON uyv.`user_id`=" . $userId . " AND yv.`id`=uyv.`video_id` "
                . "WHERE yv.`title` REGEXP '" . $format . "' "
//                . "ORDER BY ";
//            foreach ($matches as $match) {
//                $sql .= $match['name'] . " ASC, ";
//            }
                . "ORDER BY yv.published_at ASC";
            $sql .= "yv.published_at ASC";
        } else {
            $sql .= " FROM `youtube_video` yv "
                . "INNER JOIN `user_yvideo` uyv ON uyv.`user_id`=" . $userId . " AND yv.`id`=uyv.`video_id` "
                . "WHERE yv.`title` LIKE '%" . $format . "%' "
                . "ORDER BY yv.published_at ASC";
        }

        dump([
            'sql' => $sql,
        ]);

        return $this->registry->getManager()
            ->getConnection()->prepare($sql)
            ->executeQuery()
            ->fetchAllAssociative();
    }
}

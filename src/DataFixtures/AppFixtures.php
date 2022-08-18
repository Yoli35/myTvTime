<?php

namespace App\DataFixtures;

use App\Entity\Genre;
use App\Entity\ImageConfig;
use App\Entity\User;
use App\Entity\YoutubeVideoThumbnailDimension;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;
    public const ADMIN_USER_REFERENCE = 'admin-user';

    public function __construct(UserPasswordHasherInterface $upHasher)
    {
        $this->hasher = $upHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $this->addConfig($manager);
        $this->addGenres($manager);

        $this->addYoutubeThumbnailsDimensions($manager);

        $this->addAdmin($manager);
    }

    private function addConfig(ObjectManager $manager)
    {
        $config = ["base_url" => "http://image.tmdb.org/t/p/", "secure_base_url" => "https://image.tmdb.org/t/p/", "backdrop_sizes" => ["w300", "w780", "w1280", "original"], "logo_sizes" => ["w45", "w92", "w154", "w185", "w300", "w500", "original"], "poster_sizes" => ["w92", "w154", "w185", "w342", "w500", "w780", "original"], "profile_sizes" => ["w45", "w185", "h632", "original"], "still_sizes" => ["w92", "w185", "w300", "original"]];

        $imageConfig = new ImageConfig();
        $imageConfig->setBaseUrl($config["base_url"]);
        $imageConfig->setSecureBaseUrl($config["secure_base_url"]);
        $imageConfig->setBackdropSizes($config["backdrop_sizes"]);
        $imageConfig->setLogoSizes($config["logo_sizes"]);
        $imageConfig->setPosterSizes($config["poster_sizes"]);
        $imageConfig->setProfileSizes($config["profile_sizes"]);
        $imageConfig->setStillSizes($config["still_sizes"]);

        $manager->persist($imageConfig);
        $manager->flush();
    }

    private function addGenres(ObjectManager $manager)
    {
        $genres = [[28, "Action"], [12, "Adventure"], [16, "Animation"], [35, "Comedy"], [80, "Crime"], [99, "Documentary"], [18, "Drama"], [10751, "Family"], [14, "Fantasy"], [36, "History"], [27, "Horror"], [10402, "Music"], [9648, "Mystery"], [10749, "Romance"], [878, "Science Fiction"], [10770, "TV Movie"], [53, "Thriller"], [10752, "War"], [37, "Western"],];

        $count = count($genres);

        for ($i = 0; $i < $count; $i++) {
            $genre = new Genre();
            $genre->setGenreId($genres[$i][0]);
            $genre->setName($genres[$i][1]);
            $manager->persist($genre);
        }

        $manager->flush();
    }

    private function addAdmin(ObjectManager $manager)
    {
        $user = new User();
        $user->setUsername('admin');
        $user->setEmail("ojm16@free.fr");
        $password = $this->hasher->hashPassword($user, 'a123-B456-c789');
        $user->setPassword($password);
        $user->setRoles(['ROLE_ADMIN']);
        $manager->persist($user);

        $manager->flush();

        $this->addReference(self::ADMIN_USER_REFERENCE, $user);
    }

    private function addYoutubeThumbnailsDimensions(ObjectManager $manager)
    {
        $ytThumbnailDims = [["default", 90, 120], ["medium", 180, 320], ["high", 360, 480], ["standard", 480, 640], ["maxres", 720, 1280]];

        foreach ($ytThumbnailDims as $ytThumbnailDim) {

            $dim = new YoutubeVideoThumbnailDimension();
            $dim->setName($ytThumbnailDim[0]);
            $dim->setHeight($ytThumbnailDim[1]);
            $dim->setWidth($ytThumbnailDim[2]);

            $manager->persist($dim);
        }
        $manager->flush();
    }
}

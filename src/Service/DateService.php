<?php

namespace App\Service;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use IntlDateFormatter;

class DateService
{
    private array $days = [
        "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi", "Dimanche",                                    /* Français - France */
        "Londin", "Mårdi", "Mèrkidi", "Djûdi", "Vinrdi", "Sèmedi", "Dimègne",                                       /* Picard - France */
        "Lundi", "Mardi", "Mercrédi", "Jéeudi", "Vendrédi", "Sammedi", "Dîmmaunche",                                /* Wallon - Belgique */
        "Léndi", "Mardi", "Mécrdi", "Jheùdi", "Vendrdi", "Sémedi", "Dimenche",                                      /* Lorrain - France */
        "Diluns", "Dimars", "Dimècres", "Dijòus", "Divendres", "Dissabte", "Dimenge",                               /* Occitan - France */
        "Dilun", "Dimars", "Dimèdre", "Dijòu", "Divèndre", "Dissate", "Dimenche",                                   /* Provençal - France */
        "Luni", "Marti", "Marcuri", "Ghjovi", "Venneri", "Sabbatu", "Dumenica",                                     /* Corsu - France */
        "Lunedì", "Martedì", "Mercoledì", "Giovedì", "Venerdì", "Sabato", "Domenica",                               /* Italiano - Italie */
        "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo",                                   /* Español - Espagne */
        "Dilluns", "Dimarts", "Dimecres", "Dijous", "Divendres", "Dissabte", "Diumenge",                            /* Català - Espagne */
        "Segunda-feira", "Terça-feira", "Quarta-feira", "Quinta-feira", "Sexta-feira", "Sábado", "Domingo",         /* Português - Portugal */
        "Luni", "Marţi", "Miercuri", "Joi", "Vineri", "Sâmbătă", "Duminică",                                        /* Română - Roumanie */
        "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday",                               /* English - Royaume-uni */
        "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag", "Sonntag",                            /* Deutsch - Allemagne */
        "Mandi", "Zischdi", "Mittwuch", "Dunnerschdi", "Fridi", "Sàmschdi", "Sunndi",                               /* Alemannisch - Allemagne */
        "Maandag", "Dinsdag", "Woensdag", "Donderdag", "Vrijdag", "Zaterdag", "Zondag",                             /* Nederlands - Pays-bas */
        "Mandag", "Tirsdag", "Onsdag", "Torsdag", "Fredag", "Lørdag", "Søndag",                                     /* Dansk - Danemark */
        "Måndag", "Tisdag", "Onsdag", "Torsdag", "Fredag", "Lördag", "Söndag",                                      /* Svenska - Suède */
        "Mánnudagur", "Þriðjudagur", "Miðvikudagur", "Fimmtudagur", "Föstudagur", "Laugardagur", "Sunnudagur",      /* Íslenska - Islande */
        "Llun", "Mawrth", "Mercher", "Iau", "Gwener", "Sadwrn", "Sul",                                              /* Cymraeg - Pays de Galles */
        "Lun", "Meurzh", "Merc'her", "Yaou", "Gwener", "Sadorn", "Sul",                                             /* Brezhoneg - Bretagne */
        "Diluain", "Dimàirt", "Diciadaoin", "Diardaoin", "Dihaoine", "Disathairne", "Didòmhnaich",                  /* Gàidhlig - Écosse */
        "Luan", "Máirt", "Céadaoin", "Déardaoin", "Aoine", "Satharn", "Domhnach",                                   /* Gaeilge - Irlande */
        "Δευτέρα", "Τρίτη", "Τετάρτη", "Πέμπτη", "Παρασκευή", "Σάββατο", "Κυριακή",                                 /* Ελληνικά - Grèce */
        "Astelehena", "Asteartea", "Asteazkena", "Osteguna", "Ostirala", "Larunbata", "Igandea",                    /* Euskara - Pays basque */
        "Maanantai", "Tiistai", "Keskiviikko", "Torstai", "Perjantai", "Lauantai", "Sunnuntai",                     /* Suomi - Finlande */
        "Esmaspäev", "Teisipäev", "Kolmapäev", "Neljapäev", "Reede", "Laupäev", "Pühapäev",                         /* Eesti - Estonie */
        "Poniedziałek", "Wtórek", "Środa", "Czwartek", "Piątek", "Sobota", "Niedziela",                             /* Polski - Pologne */
        "Понедельник", "Вторник", "Среда", "Четверг", "Пятница", "Суббота", "Воскресенье",                          /* Русский - Russie */
        "Pirmdiena", "Otrdiena", "Trešdiena", "Ceturtdiena", "Piektdiena", "Sestdiena", "Svētdiena",                /* Latviešu - Lettonie */
        "Jumatatu", "Jumanne", "Jumatano", "Alhamisi", "Ijumaa", "Jumamosi", "Jumapili",];                          /* Kiswahili - Tanzanie */

    public function newDate($dateString, $timeZone, $allDay = false): DateTime
    {
        try {
            $date = new DateTime($dateString, new DateTimeZone($timeZone));
        } catch (Exception) {
            $date = new DateTime();
        }
        if ($allDay) $date = $date->setTime(0, 0);

        return $date;
    }

    public function getNow($timezone, $allDay = false): DateTime
    {
        try {
            $date = new DateTime('now', new DateTimeZone($timezone));
        } catch (Exception) {
            $date = new DateTime();
        }
        if ($allDay) $date = $date->setTime(0, 0);

        return $date;
    }

    public function getNowImmutable($timezone, $allDay = false): DateTimeImmutable
    {
        try {
            $date = new DateTimeImmutable('now', new DateTimeZone($timezone));
        } catch (Exception) {
            $date = new DateTimeImmutable();
        }
        if ($allDay) $date = $date->setTime(0, 0);

        return $date;
    }

    public function newDateFromUTC($dateString, $timeZone, $allDay = false): DateTime
    {
        try {
            $date = new DateTime($dateString, new DateTimeZone('UTC'));
            $date->setTimezone(new DateTimeZone($timeZone));
        } catch (Exception) {
            $date = new DateTime();
        }
        if ($allDay) $date = $date->setTime(0, 0);

        return $date;
    }

    public function newDateImmutable($dateString, $timeZone, $allDay = false): DateTimeImmutable
    {
        try {
            $date = new DateTimeImmutable($dateString, new DateTimeZone($timeZone));
        } catch (Exception) {
            $date = new DateTimeImmutable();
        }
        if ($allDay) $date = $date->setTime(0, 0);

        return $date;
    }

    public function newDateFromTimestamp($timestamp, $timeZone, $allDay = false): DateTime
    {
        try {
            $date = new DateTime();
            $date->setTimestamp($timestamp);
            $date->setTimezone(new DateTimeZone($timeZone));
        } catch (Exception) {
            $date = new DateTime();
        }
        if ($allDay) $date = $date->setTime(0, 0);

        return $date;
    }

    public function newDateImmutableFromTimestamp($timestamp, $timeZone, $allDay = false): DateTimeImmutable
    {
        try {
            $date = new DateTimeImmutable();
            $date->setTimestamp($timestamp);
            $date->setTimezone(new DateTimeZone($timeZone));
        } catch (Exception) {
            $date = new DateTimeImmutable();
        }
        if ($allDay) $date = $date->setTime(0, 0);

        return $date;
    }

    public function formatDate($dateSting, $timeZone, $locale): string
    {
        $format = datefmt_create($locale, IntlDateFormatter::SHORT, IntlDateFormatter::NONE, $timeZone, IntlDateFormatter::GREGORIAN);
        return datefmt_format($format, $dateSting);
    }

    public function getDayNames($count): array
    {
        $days = [];
        $n = count($this->days);
        if ($count > $n / 2) $count = $n / 2;
        for ($i = 0; $i < $count; $i++) {
            do {
                $day = $this->days[rand(0, $n - 1)];
            } while (in_array($day, $days));
            $days[] = $day;
        }
        return $days;
    }

    public function interval($interval): DateInterval|false
    {
        try {
            return new DateInterval($interval);
        } catch (Exception $e) {
        }
        return false;
    }
}
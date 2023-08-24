<?php

namespace App\Service;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use IntlDateFormatter;

class DateService
{
    private $days = [
        "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi", "Dimanche",
        "Londin", "Mårdi", "Mèrkidi", "Djûdi", "Vinrdi", "Sèmedi", "Dimègne",
        "Lundi", "Mardi", "Mercrédi", "Jéeudi", "Vendrédi", "Sammedi", "Dîmmaunche",
        "Léndi", "Mardi", "Mécrdi", "Jheùdi", "Vendrdi", "Sémedi", "Dimenche",
        "Diluns", "Dimars", "Dimècres", "Dijòus", "Divendres", "Dissabte", "Dimenge",
        "Dilun", "Dimars", "Dimèdre", "Dijòu", "Divèndre", "Dissate", "Dimenche",
        "Luni", "Marti", "Marcuri", "Ghjovi", "Venneri", "Sabbatu", "Dumenica",
        "Lunedì", "Martedì", "Mercoledì", "Giovedì", "Venerdì", "Sabato", "Domenica",
        "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo",
        "Dilluns", "Dimarts", "Dimecres", "Dijous", "Divendres", "Dissabte", "Diumenge",
        "Segunda-feira", "Terça-feira", "Quarta-feira", "Quinta-feira", "Sexta-feira", "Sábado", "Domingo",
        "Luni", "Marţi", "Miercuri", "Joi", "Vineri", "Sâmbătă", "Duminică",
        "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday",
        "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag", "Sonntag",
        "Mandi", "Zischdi", "Mittwuch", "Dunnerschdi", "Fridi", "Sàmschdi", "Sunndi",
        "Maandag", "Dinsdag", "Woensdag", "Donderdag", "Vrijdag", "Zaterdag", "Zondag",
        "Mandag", "Tirsdag", "Onsdag", "Torsdag", "Fredag", "Lørdag", "Søndag",
        "Måndag", "Tisdag", "Onsdag", "Torsdag", "Fredag", "Lördag", "Söndag",
        "Mánnudagur", "Þriðjudagur", "Miðvikudagur", "Fimmtudagur", "Föstudagur", "Laugardagur", "Sunnudagur",
        "Llun", "Mawrth", "Mercher", "Iau", "Gwener", "Sadwrn", "Sul",
        "Lun", "Meurzh", "Merc'her", "Yaou", "Gwener", "Sadorn", "Sul",
        "Diluain", "Dimàirt", "Diciadaoin", "Diardaoin", "Dihaoine", "Disathairne", "Didòmhnaich",
        "Luan", "Máirt", "Céadaoin", "Déardaoin", "Aoine", "Satharn", "Domhnach",
        "Δευτέρα", "Τρίτη", "Τετάρτη", "Πέμπτη", "Παρασκευή", "Σάββατο", "Κυριακή",
        "Astelehena", "Asteartea", "Asteazkena", "Osteguna", "Ostirala", "Larunbata", "Igandea",
        "Maanantai", "Tiistai", "Keskiviikko", "Torstai", "Perjantai", "Lauantai", "Sunnuntai",
        "Esmaspäev", "Teisipäev", "Kolmapäev", "Neljapäev", "Reede", "Laupäev", "Pühapäev",
        "Poniedziałek", "Wtórek", "Środa", "Czwartek", "Piątek", "Sobota", "Niedziela",
        "Понедельник", "Вторник", "Среда", "Четверг", "Пятница", "Суббота", "Воскресенье",
        "Pirmdiena", "Otrdiena", "Trešdiena", "Ceturtdiena", "Piektdiena", "Sestdiena", "Svētdiena",
        "Jumatatu", "Jumanne", "Jumatano", "Alhamisi", "Ijumaa", "Jumamosi", "Jumapili",];

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
        if ($count > $n) $count = $n;
        for ($i = 0; $i < $count; $i++) {
            do {
                $day = $this->days[rand(0, $n - 1)];
            } while (in_array($day, $days));
            $days[] = $day;
        }
        return $days;
    }
}
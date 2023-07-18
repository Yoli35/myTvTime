<?php

namespace App\Controller;

use App\Service\DateService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class LogFilterController extends AbstractController
{
    private array $logs;

    public function __construct(
        private readonly DateService $dateService,
    )
    {
    }

    #[Route('/log/filter', name: 'app_log_filter')]
    public function index(Request $request): Response
    {
        $logPath = $this->getParameter('kernel.logs_dir') . '/dev.log';
        $this->logs = file($logPath);
        dump([
            'logPath' => $logPath,
            'fileSize' => filesize($logPath),
            'logs - line 1' => $this->logs[0],
            'logs - line 2' => $this->logs[1],
            'nombre de lignes' => count($this->logs),
        ]);
        $this->logs = array_reverse($this->logs);

        $count = $request->query->getInt('lines', 100);
        $logs = [];
        $channels = [];
        $levels = [];
        for ($i = 0; $i < $count; $i++) {
            $log = $this->logs[$i];
            if (strlen($log) > 0 && $log[0] === '[') {
                $dateString = substr($log, 1, 19);
                $date = $this->dateService->newDateFromUTC($dateString, 'Europe/Paris');
                $channel = substr($log, 35, strpos($log, '.', 35) - 35);
                $offset = 36 + strlen($channel);
                $level = substr($log, $offset, strpos($log, ':', $offset) - $offset);
                $offset = $offset + strlen($level) + 1;
                $message = substr($log, $offset);
                $channels[] = $channel;
                $logs[$i] = [
                    'date' => $date,
                    'channel' => $channel,
                    'level' => $level,
                    'message' => $message,
                ];
            }
        }
        $levels = ['DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'];
        $channels = array_unique($channels);
        dump([
            'logs' => $logs,
            'channels' => $channels,
            'levels' => $levels,
        ]);

        return $this->render('log_filter/index.html.twig', [
            'logs' => $logs,
            'channels' => $channels,
            'levels' => $levels,
            'count' => $count,
            'timezone' => 'Europe/Paris',
        ]);
    }
}

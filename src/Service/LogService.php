<?php

namespace App\Service;

use App\Entity\VisitorLog;
use App\Repository\VisitorLogRepository;
use Jenssegers\Agent\Agent;
use Symfony\Component\HttpFoundation\Request;

class LogService
{
    private Agent $agent;

    public function __construct(private readonly VisitorLogRepository $repository)
    {
        $this->agent = new Agent();
    }

    public function log(Request $request, $user): void
    {
        $agent = $this->agent;

        $ip = $request->getClientIp();
        $url = $request->getPathInfo();
        $browser = $agent->browser();
        $platform = $agent->platform();
        $languages = $agent->languages();
        $deviceName = $agent->isMobile() ? $agent->device() : null;

        $log = new VisitorLog($user ? $user->getUsername() : ($agent->isBot() ? $agent->robot() : 'Anonymous'), $url, $ip, $browser, $platform, $languages, $deviceName);
        $this->repository->save($log, true);
    }
}
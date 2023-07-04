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

//        dump($request->getClientIps(), $request->headers->get('user-agent'));
//  Safari : "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.2 Safari/605.1.15"
//  Brave :  "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36"
//  Chrome : "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36"
        $ip = $request->getClientIp();
        $url = $request->getPathInfo();

        if (str_contains($url, "_wdt") ||
            str_contains($url, "_profiler") ||
            str_contains($url, "get-posters")) {
            return;
        }
        $browser = $agent->browser();
        $platform = $agent->platform();
        $languages = $agent->languages();
        $deviceName = $agent->isMobile() ? $agent->device() : null;

        $log = new VisitorLog($user ? $user->getUsername() : ($agent->isBot() ? $agent->robot() : 'Anonymous'), $url, $ip, $browser, $platform, $languages, $deviceName);
        // flush in calling registerCurrentController
        $this->repository->save($log, false);
    }
}
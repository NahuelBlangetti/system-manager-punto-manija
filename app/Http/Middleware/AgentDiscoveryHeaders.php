<?php

namespace App\Http\Middleware;

use App\Support\MarketplaceAgent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AgentDiscoveryHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->is('admin', 'admin/*', 'livewire/*', 'up')) {
            return $response;
        }

        $response->headers->set('Content-Signal', MarketplaceAgent::CONTENT_SIGNAL);

        foreach (MarketplaceAgent::linkValues() as $link) {
            $response->headers->set('Link', $link, false);
        }

        return $response;
    }
}

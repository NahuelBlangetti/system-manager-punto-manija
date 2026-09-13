<?php

namespace App\Http\Controllers;

use App\Support\MarketplaceAgent;
use App\Support\MarketplaceSeo;
use Illuminate\Http\Response;

class MarketplaceDiscoveryController extends Controller
{
    public function robots(): Response
    {
        return MarketplaceAgent::textResponse(MarketplaceSeo::robotsTxt());
    }

    public function llms(): Response
    {
        return MarketplaceAgent::textResponse(MarketplaceSeo::llmsTxt());
    }

    public function sitemap(): Response
    {
        return response()
            ->view('sitemap', ['urls' => MarketplaceSeo::sitemapUrls()])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}

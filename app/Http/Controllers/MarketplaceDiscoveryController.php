<?php

namespace App\Http\Controllers;

use App\Support\MarketplaceSeo;
use Illuminate\Http\Response;

class MarketplaceDiscoveryController extends Controller
{
    public function robots(): Response
    {
        return response(MarketplaceSeo::robotsTxt(), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function sitemap(): Response
    {
        return response()
            ->view('sitemap', ['urls' => MarketplaceSeo::sitemapUrls()])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}

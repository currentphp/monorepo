<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Current\Pages\PageRegistry;
use Current\Pages\Pages;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use Symfony\Component\HttpFoundation\Response;

class SitemapController
{
    /**
     * Lists every page with a URL, read from Current's page registry.
     */
    public function __invoke(PageRegistry $registry, Pages $pages): Response
    {
        $sitemap = Sitemap::create();
        $context = $pages->context();

        foreach ($registry->all() as $page) {
            $url = $pages->url($page, $context);

            if ($url !== null) {
                $sitemap->add(Url::create($url));
            }
        }

        return $sitemap->toResponse(request());
    }
}

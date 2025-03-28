<?php

declare(strict_types=1);

namespace BVP\BoatraceScraper\Scrapers;

use BVP\Converter\Converter;
use BVP\Trimmer\Trimmer;
use BVP\BoatraceScraper\Traits\HttpBrowserInitializer;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @author shimomo
 */
abstract class BaseScraper
{
    use HttpBrowserInitializer;

    /**
     * @var string
     */
    protected string $baseUrl = 'https://www.boatrace.jp';

    /**
     * @var int
     */
    protected int $baseLevel = 0;

    /**
     * @var int
     */
    protected int $seconds = 1;

    /**
     * @param  \Symfony\Component\BrowserKit\HttpBrowser  $httpBrowser
     * @return void
     */
    public function __construct(protected readonly HttpBrowser $httpBrowser)
    {
        $this->initializeHttpBrowser($httpBrowser);
    }

    /**
     * @param  \Symfony\Component\DomCrawler\Crawler  $scraper
     * @param  string                                 $xpath
     * @return string|null
     */
    protected function filterXPath(Crawler $scraper, string $xpath): ?string
    {
        if (!$scraper->filterXPath($xpath)->count()) {
            return null;
        }

        $value = $scraper->filterXPath($xpath)->text();
        $value = Converter::string($value);
        $value = Trimmer::trim($value);
        return $value;
    }

    /**
     * @param  \Symfony\Component\DomCrawler\Crawler  $scraper
     * @param  string                                 $xpath
     * @return string
     */
    protected function filterXPathForWindDirectionId(Crawler $scraper, string $xpath): ?string
    {
        if (!$scraper->filterXPath($xpath)->count()) {
            return null;
        }

        $value = $scraper->filterXPath($xpath)->attr('class');
        $value = Converter::string($value);
        $value = Trimmer::trim($value);
        return $value;
    }

    /**
     * @param  \Symfony\Component\DomCrawler\Crawler  $scraper
     * @param  string                                 $xpath
     * @return float|null
     */
    protected function filterXPathForOdds(Crawler $scraper, string $xpath): ?float
    {
        if (!$scraper->filterXPath($xpath)->count()) {
            return null;
        }

        $value = $scraper->filterXPath($xpath)->text();
        $value = Converter::float($value);
        return $value;
    }

    /**
     * @param  \Symfony\Component\DomCrawler\Crawler  $scraper
     * @param  string                                 $xpath
     * @return array
     */
    protected function filterXPathForOddsWithLowerLimitAndUpperLimit(Crawler $scraper, string $xpath): array
    {
        if ($scraper->filterXPath($xpath)->count()) {
            if (count($oddses = explode('-', $scraper->filterXPath($xpath)->text())) === 2) {
                $lowerLimit = Converter::float(array_shift($oddses));
                $upperLimit = Converter::float(array_shift($oddses));
            }
        }

        $response = [];

        $response['lower_limit'] = $lowerLimit ?? null;
        $response['upper_limit'] = $upperLimit ?? null;

        return $response;
    }
}

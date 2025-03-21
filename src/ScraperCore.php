<?php

declare(strict_types=1);

namespace BVP\BoatraceScraper;

use BVP\Converter\Converter;
use BVP\BoatraceScraper\Scrapers\BaseScraperInterface;
use BVP\BoatraceScraper\Scrapers\OddsScraper;
use BVP\BoatraceScraper\Scrapers\PreviewScraper;
use BVP\BoatraceScraper\Scrapers\ProgramScraper;
use BVP\BoatraceScraper\Scrapers\ResultScraper;
use BVP\BoatraceScraper\Scrapers\StadiumScraper;
use Carbon\CarbonImmutable as Carbon;
use Carbon\CarbonInterface;
use InvalidArgumentException;
use Symfony\Component\BrowserKit\HttpBrowser;

/**
 * @author shimomo
 */
class ScraperCore implements ScraperCoreInterface
{
    /**
     * @var array
     */
    private array $instances = [];

    /**
     * @var array
     */
    private array $scraperClasses = [
        'scrapeOddses' => OddsScraper::class,
        'scrapePreviews' => PreviewScraper::class,
        'scrapePrograms' => ProgramScraper::class,
        'scrapeResults' => ResultScraper::class,
        'scrapeStadiumIds' => StadiumScraper::class,
        'scrapeStadiumNames' => StadiumScraper::class,
        'scrapeStadiums' => StadiumScraper::class,
    ];

    /**
     * @param  string  $name
     * @param  array   $arguments
     * @return array
     */
    public function __call(string $name, array $arguments): array
    {
        return $this->scraper($name, ...$arguments);
    }

    /**
     * @param  string                          $name
     * @param  \Carbon\CarbonInterface|string  $date
     * @param  string|int|null                 $raceStadiumCode
     * @param  string|int|null                 $raceCode
     * @return array
     */
    private function scraper(string $name, CarbonInterface|string $date, string|int|null $raceStadiumCode = null, string|int|null $raceCode = null): array
    {
        $scraper = $this->getScraperInstance($name);
        $carbonDate = Carbon::parse($date);

        if (str_starts_with($name, 'scrapeStadium')) {
            $methodName = match ($name) {
                'scrapeStadiumIds' => 'scrapeIds',
                'scrapeStadiumNames' => 'scrapeNames',
                default => 'scrape',
            };
            $response = $scraper->$methodName($carbonDate);
            return $response;
        }

        $raceStadiumCodes = $this->getRaceStadiumCodes($carbonDate, $raceStadiumCode);
        $raceCodes = $this->getRaceCodes($raceCode);

        $response = [];
        foreach ($raceStadiumCodes as $raceStadiumCode) {
            foreach ($raceCodes as $raceCode) {
                $response[$raceStadiumCode][$raceCode] = $scraper->scrape(
                    $carbonDate,
                    $raceStadiumCode,
                    $raceCode
                );
            }
        }

        return $response;
    }

    /**
     * @param  string  $name
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    private function resolveScraperClass(string $name): string
    {
        if (isset($this->scraperClasses[$name])) {
            return $this->scraperClasses[$name];
        }

        throw new InvalidArgumentException(
            __METHOD__ . "The scraper name for '{$name}' is invalid."
        );
    }

    /**
     * @param  string  $name
     * @return \BVP\BoatraceScraper\ScraperContractInterface
     */
    private function getScraperInstance(string $name): ScraperContractInterface
    {
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        $scraper = $this->resolveScraperClass($name);
        return $this->instances[$name] = new $scraper(
            new HttpBrowser()
        );
    }

    /**
     * @param  string  $name
     * @return \BVP\BoatraceScraper\ScraperContractInterface
     */
    private function createScraperInstance(string $name): ScraperContractInterface
    {
        $scraper = $this->resolveScraperClass($name);
        return $this->instances[$name] = new $scraper(
            new HttpBrowser()
        );
    }

    /**
     * @param  \Carbon\CarbonInterface  $carbonDate
     * @param  string|int|null          $raceStadiumCode
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    private function getRaceStadiumCodes(CarbonInterface $carbonDate, string|int|null $raceStadiumCode): array
    {
        if (is_null($raceStadiumCode)) {
            return $this->getScraperInstance('scrapeStadiums')->scrapeIds($carbonDate);
        }

        $formattedRaceStadiumCode = Converter::string($raceStadiumCode);
        if (preg_match('/\b(0?[1-9]|1[0-9]|2[0-4])\b/', $formattedRaceStadiumCode, $matches)) {
            return [(int) $matches[1]];
        }

        throw new InvalidArgumentException(
            __METHOD__ . "The race stadium code for '{$raceStadiumCode}' is invalid."
        );
    }

    /**
     * @param  string|int|null  $raceCode
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    private function getRaceCodes(string|int|null $raceCode): array
    {
        if (is_null($raceCode)) {
            return range(1, 12);
        }

        $formattedRaceCode = Converter::string($raceCode);
        if (preg_match('/\b(0?[1-9]|1[0-2])\b/', $formattedRaceCode, $matches)) {
            return [(int) $matches[1]];
        }

        throw new InvalidArgumentException(
            __METHOD__ . "() - The race code for '{$raceCode}' is invalid."
        );
    }
}

<?php

declare(strict_types=1);

namespace Crawlora\Imdb;

class CrawloraException extends \RuntimeException
{
    public function __construct(string $message, public readonly ?int $status = null, public readonly ?string $operationId = null, public readonly ?string $responseBody = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}

class ClientException extends CrawloraException {}
class ServerException extends CrawloraException {}
class NetworkException extends CrawloraException {}

final class Client
{
    private static array $operations;
    private bool $closed = false;
    private string $apiKey;
    private string $baseUrl;
    private float $timeout;
    private ?\Closure $transport;

    public const PLATFORM = 'imdb';
    public const VERSION = '0.1.0';
    public const OPERATION_COUNT = 30;
    public const OPERATION_IDS = ["imdb-charts", "imdb-image-types", "imdb-name", "imdb-name-awards", "imdb-name-credits", "imdb-name-images", "imdb-name-videos", "imdb-search", "imdb-search-title", "imdb-title", "imdb-title-awards", "imdb-title-box-office", "imdb-title-company-credits", "imdb-title-connections", "imdb-title-credits", "imdb-title-episodes", "imdb-title-filming-locations", "imdb-title-goofs", "imdb-title-images", "imdb-title-keywords", "imdb-title-parental-guide", "imdb-title-public-facts-analysis", "imdb-title-quotes", "imdb-title-ratings", "imdb-title-release-info", "imdb-title-reviews", "imdb-title-similar", "imdb-title-technical-specs", "imdb-title-trivia", "imdb-title-videos"];

    public function __construct(?string $apiKey = null, string $baseUrl = 'https://api.crawlora.net/api/v1', float $timeout = 30.0, ?callable $transport = null)
    {
        $this->apiKey = $apiKey ?? (getenv('CRAWLORA_API_KEY') ?: '');
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
        $this->transport = $transport === null ? null : \Closure::fromCallable($transport);
        self::$operations ??= json_decode(<<<'JSON'
{"imdb-charts": {"id": "imdb-charts", "method": "GET", "params": [{"default": "top_rated_movies", "description": "IMDb chart", "enum": ["top_rated_movies", "top_rated_tv_shows", "most_popular_movies", "most_popular_tv_shows", "top_rated_english_movies", "lowest_rated_movies"], "in": "query", "name": "chart", "type": "string"}, {"default": 25, "description": "Rows to return, default 25, max 250", "in": "query", "name": "limit", "type": "integer"}], "path": "/imdb/charts", "pathParams": [], "produces": ["application/json"], "queryParams": [{"enum": ["top_rated_movies", "top_rated_tv_shows", "most_popular_movies", "most_popular_tv_shows", "top_rated_english_movies", "lowest_rated_movies"], "in": "query", "name": "chart", "type": "string"}, {"in": "query", "name": "limit", "type": "integer"}], "security": ["ApiKeyAuth"]}, "imdb-image-types": {"id": "imdb-image-types", "method": "GET", "params": [], "path": "/imdb/image-types", "pathParams": [], "produces": ["application/json"], "queryParams": [], "security": ["ApiKeyAuth"]}, "imdb-name": {"id": "imdb-name", "method": "GET", "params": [{"description": "IMDb name id", "in": "query", "name": "id", "type": "string", "x-example": "nm0634240"}, {"description": "Absolute https://www.imdb.com/name/<id>/ URL", "in": "query", "name": "url", "type": "string", "x-example": "https://www.imdb.com/name/nm0634240/"}], "path": "/imdb/name", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "id", "type": "string"}, {"in": "query", "name": "url", "type": "string"}], "security": ["ApiKeyAuth"]}, "imdb-name-awards": {"id": "imdb-name-awards", "method": "GET", "params": [{"description": "IMDb name id", "in": "query", "name": "id", "type": "string", "x-example": "nm0634240"}, {"description": "Absolute https://www.imdb.com/name/<id>/ URL", "in": "query", "name": "url", "type": "string", "x-example": "https://www.imdb.com/name/nm0634240/"}], "path": "/imdb/name/awards", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "id", "type": "string"}, {"in": "query", "name": "url", "type": "string"}], "security": ["ApiKeyAuth"]}, "imdb-name-credits": {"id": "imdb-name-credits", "method": "GET", "params": [{"description": "IMDb name id", "in": "query", "name": "id", "type": "string", "x-example": "nm0634240"}, {"description": "Absolute https://www.imdb.com/name/<id>/ URL", "in": "query", "name": "url", "type": "string", "x-example": "https://www.imdb.com/name/nm0634240/"}], "path": "/imdb/name/credits", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "id", "type": "string"}, {"in": "query", "name": "url", "type": "string"}], "security": ["ApiKeyAuth"]}, "imdb-name-images": {"id": "imdb-name-images", "method": "GET", "params": [{"description": "IMDb name id", "in": "query", "name": "id", "type": "string", "x-example": "nm0000151"}, {"description": "Absolute https://www.imdb.com/name/<id>/ URL", "in": "query", "name": "url", "type": "string", "x-example": "https://www.imdb.com/name/nm0000151/"}, {"description": "Image type filter, single value or comma-separated list", "enum": ["behind_the_scenes", "event", "poster", "product", "production_art", "publicity", "still_frame", "unknown"], "in": "query", "name": "type", "type": "string", "x-example": "still_frame"}, {"description": "Rows to return, default 50, max 1000", "in": "query", "name": "limit", "type": "integer", "x-example": 50}], "path": "/imdb/name/images", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "id", "type": "string"}, {"in": "query", "name": "url", "type": "string"}, {"enum": ["behind_the_scenes", "event", "poster", "product", "production_art", "publicity", "still_frame", "unknown"], "in": "query", "name": "type", "type": "string"}, {"in": "query", "name": "limit", "type": "integer"}], "security": ["ApiKeyAuth"]}, "imdb-name-videos": {"id": "imdb-name-videos", "method": "GET", "params": [{"description": "IMDb name id", "in": "query", "name": "id", "type": "string", "x-example": "nm0000151"}, {"description": "Absolute https://www.imdb.com/name/<id>/ URL", "in": "query", "name": "url", "type": "string", "x-example": "https://www.imdb.com/name/nm0000151/"}, {"description": "Rows to return, default 50, max 100", "in": "query", "name": "limit", "type": "integer", "x-example": 50}], "path": "/imdb/name/videos", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "id", "type": "string"}, {"in": "query", "name": "url", "type": "string"}, {"in": "query", "name": "limit", "type": "integer"}], "security": ["ApiKeyAuth"]}, "imdb-search": {"id": "imdb-search", "method": "GET", "params": [{"description": "Search query", "in": "query", "name": "query", "required": true, "type": "string", "x-example": "inception"}, {"description": "Rows to return, default 10, max 20", "in": "query", "name": "limit", "type": "integer", "x-example": 10}], "path": "/imdb/search", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "query", "required": true, "type": "string"}, {"in": "query", "name": "limit", "type": "integer"}], "security": ["ApiKeyAuth"]}, "imdb-search-title": {"id": "imdb-search-title", "method": "GET", "params": [{"description": "Title-name substring match", "in": "query", "name": "title", "type": "string", "x-example": "matrix"}, {"description": "Comma-separated title types: `feature`, `tvSeries`, `short`, `tvEpisode`, `tvMiniSeries`, `tvMovie`, `tvSpecial`, `tvShort`, `videoGame`, `video`, `musicVideo`, `podcastSeries`, `podcastEpisode`", "in": "query", "name": "title_type", "type": "string", "x-example": "feature"}, {"description": "Comma-separated genres (include-only): `Action`, `Adventure`, `Animation`, `Biography`, `Comedy`, `Crime`, `Documentary`, `Drama`, `Family`, `Fantasy`, `Film-Noir`, `Game-Show`, `History`, `Horror`, `Music`, `Musical`, `Mystery`, `News`, `Reality-TV`, `Romance`, `Sci-Fi`, `Short`, `Sport`, `Talk-Show`, `Thriller`, `War`, `Western`", "in": "query", "name": "genres", "type": "string", "x-example": "Action,Adventure"}, {"description": "Release date lower bound: YYYY, YYYY-MM, or YYYY-MM-DD", "in": "query", "name": "release_date_from", "type": "string", "x-example": "2020-01-01"}, {"description": "Release date upper bound: YYYY, YYYY-MM, or YYYY-MM-DD", "in": "query", "name": "release_date_to", "type": "string", "x-example": "2021-12-31"}, {"description": "Minimum IMDb user rating, 0-10", "in": "query", "name": "min_user_rating", "type": "number", "x-example": 7}, {"description": "Maximum IMDb user rating, 0-10", "in": "query", "name": "max_user_rating", "type": "number", "x-example": 9.5}, {"description": "Minimum number of user rating votes", "in": "query", "name": "min_votes", "type": "integer", "x-example": 25000}, {"description": "Maximum number of user rating votes", "in": "query", "name": "max_votes", "type": "integer"}, {"description": "Minimum IMDb popularity rank (1 is most popular)", "in": "query", "name": "min_popularity", "type": "integer"}, {"description": "Maximum IMDb popularity rank", "in": "query", "name": "max_popularity", "type": "integer"}, {"description": "Minimum runtime in minutes", "in": "query", "name": "min_runtime", "type": "integer"}, {"description": "Maximum runtime in minutes", "in": "query", "name": "max_runtime", "type": "integer"}, {"description": "Comma-separated awards/curated-list groups: `oscar_winner`, `oscar_nominee`, `emmy_winner`, `emmy_nominee`, `golden_globe_winner`, `golden_globe_nominee`, `best_picture_winner`, `best_director_winner`, `razzie_winner`, `razzie_nominee`, `top_100`, `top_250`, `top_1000`, `bottom_100`, `bottom_250`, `bottom_1000`", "in": "query", "name": "groups", "type": "string"}, {"description": "Comma-separated plot keywords", "in": "query", "name": "keywords", "type": "string", "x-example": "superhero"}, {"description": "Comma-separated IMDb company ids, format `co########`", "in": "query", "name": "companies", "type": "string", "x-example": "co0000756"}, {"description": "Comma-separated `COUNTRY:RATING` certificate pairs, e.g. `US:PG-13`", "in": "query", "name": "certificates", "type": "string", "x-example": "US:PG-13"}, {"description": "Comma-separated color info: `color`, `black_and_white`, `colorized`, `aces`", "in": "query", "name": "colors", "type": "string"}, {"description": "Comma-separated ISO country codes", "in": "query", "name": "countries", "type": "string", "x-example": "US"}, {"description": "Comma-separated ISO language codes", "in": "query", "name": "languages", "type": "string", "x-example": "en"}, {"description": "Comma-separated sound mix names: `12-Track Digital Sound`, `3 Channel Stereo`, `4-Track Stereo`, `6-Track Stereo`, `70 mm 6-Track`, `AGA Sound System`, `Auro 11.1`, `CDS`, `Chronophone`, `Cinematophone`, `Cinephone`, `Cinerama 7-Track`, `Cinesound`, `D-Cinema 48kHz 5.1`, `Datasat`, `De Forest Phonofilm`, `Digitrac Digital Audio System`, `Dolby`, `Dolby Atmos`, `Dolby Digital`, `Dolby Digital EX`, `Dolby SR`, `Dolby Stereo`, `Dolby Surround 7.1`, `DTS`, `DTS 70 mm`, `DTS Stereo`, `DTS-ES`, `IMAX 6-Track`, `Kinoplasticon`, `LC-Concept Digital Sound`, `Matrix Surround`, `Mono`, `Perspecta Stereo`, `Phono-Kinema`, `SDDS`, `Sensurround`, `Silent`, `Sonics-DDP`, `Sonix`, `Stereo`, `Ultra Stereo`, `Vitaphone`", "in": "query", "name": "sound_mixes", "type": "string"}, {"description": "Comma-separated cast/crew IMDb name ids, format `nm########`", "in": "query", "name": "role", "type": "string", "x-example": "nm0634240"}, {"description": "Comma-separated character names", "in": "query", "name": "characters", "type": "string", "x-example": "Neo"}, {"description": "Plot text search term", "in": "query", "name": "plot", "type": "string", "x-example": "hacker"}, {"default": false, "description": "Include adult titles. Defaults to excluded", "in": "query", "name": "include_adult", "type": "boolean"}, {"description": "One of `moviemeter`, `alpha`, `user_rating`, `num_votes`, `boxoffice_gross_us`, `runtime`, `year`, `release_date`", "in": "query", "name": "sort", "type": "string", "x-example": "user_rating"}, {"description": "`asc` or `desc`. Defaults to `asc` when sort is set", "in": "query", "name": "sort_order", "type": "string", "x-example": "desc"}, {"description": "Rows to return, default 25, max 50", "in": "query", "name": "limit", "type": "integer", "x-example": 25}], "path": "/imdb/search/title", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "title", "type": "string"}, {"in": "query", "name": "title_type", "type": "string"}, {"in": "query", "name": "genres", "type": "string"}, {"in": "query", "name": "release_date_from", "type": "string"}, {"in": "query", "name": "release_date_to", "type": "string"}, {"in": "query", "name": "min_user_rating", "type": "number"}, {"in": "query", "name": "max_user_rating", "type": "number"}, {"in": "query", "name": "min_votes", "type": "integer"}, {"in": "query", "name": "max_votes", "type": "integer"}, {"in": "query", "name": "min_popularity", "type": "integer"}, {"in": "query", "name": "max_popularity", "type": "integer"}, {"in": "query", "name": "min_runtime", "type": "integer"}, {"in": "query", "name": "max_runtime", "type": "integer"}, {"in": "query", "name": "groups", "type": "string"}, {"in": "query", "name": "keywords", "type": "string"}, {"in": "query", "name": "companies", "type": "string"}, {"in": "query", "name": "certificates", "type": "string"}, {"in": "query", "name": "colors", "type": "string"}, {"in": "query", "name": "countries", "type": "string"}, {"in": "query", "name": "languages", "type": "string"}, {"in": "query", "name": "sound_mixes", "type": "string"}, {"in": "query", "name": "role", "type": "string"}, {"in": "query", "name": "characters", "type": "string"}, {"in": "query", "name": "plot", "type": "string"}, {"in": "query", "name": "include_adult", "type": "boolean"}, {"in": "query", "name": "sort", "type": "string"}, {"in": "query", "name": "sort_order", "type": "string"}, {"in": "query", "name": "limit", "type": "integer"}], "security": ["ApiKeyAuth"]}, "imdb-title": {"id": "imdb-title", "method": "GET", "params": [{"description": "IMDb title id", "in": "query", "name": "id", "type": "string", "x-example": "tt1375666"}, {"description": "Absolute https://www.imdb.com/title/<id>/ URL", "in": "query", "name": "url", "type": "string", "x-example": "https://www.imdb.com/title/tt1375666/"}], "path": "/imdb/title", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "id", "type": "string"}, {"in": "query", "name": "url", "type": "string"}], "security": ["ApiKeyAuth"]}, "imdb-title-awards": {"id": "imdb-title-awards", "method": "GET", "params": [{"description": "IMDb title id", "in": "query", "name": "id", "type": "string", "x-example": "tt1375666"}, {"description": "Absolute https://www.imdb.com/title/<id>/ URL", "in": "query", "name": "url", "type": "string", "x-example": "https://www.imdb.com/title/tt1375666/"}], "path": "/imdb/title/awards", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "id", "type": "string"}, {"in": "query", "name": "url", "type": "string"}], "security": ["ApiKeyAuth"]}, "imdb-title-box-office": {"id": "imdb-title-box-office", "method": "GET", "params": [{"description": "IMDb title id", "in": "query", "name": "id", "type": "string", "x-example": "tt0111161"}, {"description": "Absolute https://www.imdb.com/title/<id>/ URL", "in": "query", "name": "url", "type": "string", "x-example": "https://www.imdb.com/title/tt0111161/"}], "path": "/imdb/title/box-office", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "id", "type": "string"}, {"in": "query", "name": "url", "type": "string"}], "security": ["ApiKeyAuth"]}, "imdb-title-company-credits": {"id": "imdb-title-company-credits", "method": "GET", "params": [{"description": "IMDb title id", "in": "query", "name": "id", "type": "string", "x-example": "tt1375666"}, {"description": "Absolute https://www.imdb.com/title/<id>/ URL", "in": "query", "name": "url", "type": "string", "x-example": "https://www.imdb.com/title/tt1375666/"}], "path": "/imdb/title/company-credits", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "id", "type": "string"}, {"in": "query", "name": "url", "type": "string"}], "security": ["ApiKeyAuth"]}, "imdb-title-connections": {"id": "imdb-title-connections", "method": "GET", "params": [{"description": "IMDb title id", "in": "query", "name": "id", "type": "string", "x-example": "tt0111161"}, {"description": "Absolute https://www.imdb.com/title/<id>/ URL", "in": "query", "name": "url", "type": "string", "x-example": "https://www.imdb.com/title/tt0111161/"}, {"description": "Rows to return, default 50, max 250", "in": "query", "name": "limit", "type": "integer", "x-example": 50}], "path": "/imdb/title/connections", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "id", "type": "string"}, {"in": "query", "name": "url", "type": "string"}, {"in": "query", "name": "limit", "type": "integer"}], "security": ["ApiKeyAuth"]}, "imdb-title-credits": {"id": "imdb-title-credits", "method": "GET", "params": [{"description": "IMDb title id", "in": "query", "name": "id", "type": "string", "x-example": "tt1375666"}, {"description": "Absolute https://www.imdb.com/title/<id>/ URL", "in": "query", "name": "url", "type": "string", "x-example": "https://www.imdb.com/title/tt1375666/"}], "path": "/imdb/title/credits", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "id", "type": "string"}, {"in": "query", "name": "url", "type": "string"}], "security": ["ApiKeyAuth"]}, "imdb-title-episodes": {"id": "imdb-title-episodes", "method": "GET", "params": [{"description": "IMDb title id", "in": "query", "name": "id", "type": "string", "x-example": "tt0944947"}, {"description": "Absolute https://www.imdb.com/title/<id>/ URL", "in": "query", "name": "url", "type": "string", "x-example": "https://www.imdb.com/title/tt0944947/"}, {"description": "Season number to request", "in": "query", "name": "season", "type": "integer", "x-example": 1}, {"description": "Rows to return, default 10, max 20", "in": "query", "name": "limit", "type": "integer", "x-example": 10}], "path": "/imdb/title/episodes", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "id", "type": "string"}, {"in": "query", "name": "url", "type": "string"}, {"in": "query", "name": "season", "type": "integer"}, {"in": "query", "name": "limit", "type": "integer"}], "security": ["ApiKeyAuth"]}, "imdb-title-filming-locations": {"id": "imdb-title-filming-locations", "method": "GET", "params": [{"description": "IMDb title id", "in": "query", "name": "id", "type": "string", "x-example": "tt1375666"}, {"description": "Absolute https://www.imdb.com/title/<id>/ URL", "in": "query", "name": "url", "type": "string", "x-example": "https://www.imdb.com/title/tt1375666/"}], "path": "/imdb/title/filming-locations", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "id", "type": "string"}, {"in": "query", "name": "url", "type": "string"}], "security": ["ApiKeyAuth"]}, "imdb-title-goofs": {"id": "imdb-title-goofs", "method": "GET", "params": [{"description": "IMDb title id", "in": "query", "name": "id", "type": "string", "x-example": "tt1375666"}, {"description": "Absolute https://www.imdb.com/title/<id>/ URL", "in": "query", "name": "url", "type": "string", "x-example": "https://www.imdb.com/title/tt1375666/"}], "path": "/imdb/title/goofs", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "id", "type": "string"}, {"in": "query", "name": "url", "type": "string"}], "security": ["ApiKeyAuth"]}, "imdb-title-images": {"id": "imdb-title-images", "method": "GET", "params": [{"description": "IMDb title id", "in": "query", "name": "id", "type": "string", "x-example": "tt0089753"}, {"description": "Absolute https://www.imdb.com/title/<id>/ URL", "in": "query", "name": "url", "type": "string", "x-example": "https://www.imdb.com/title/tt0089753/"}, {"description": "Image type filter, single value or comma-separated list", "enum": ["behind_the_scenes", "event", "poster", "product", "production_art", "publicity", "still_frame", "unknown"], "in": "query", "name": "type", "type": "string", "x-example": "still_frame"}, {"description": "Rows to return, default 50, max 1000", "in": "query", "name": "limit", "type": "integer", "x-example": 50}], "path": "/imdb/title/images", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "id", "type": "string"}, {"in": "query", "name": "url", "type": "string"}, {"enum": ["behind_the_scenes", "event", "poster", "product", "production_art", "publicity", "still_frame", "unknown"], "in": "query", "name": "type", "type": "string"}, {"in": "query", "name": "limit", "type": "integer"}], "security": ["ApiKeyAuth"]}, "imdb-title-keywords": {"id": "imdb-title-keywords", "method": "GET", "params": [{"description": "IMDb title id", "in": "query", "name": "id", "type": "string", "x-example": "tt1375666"}, {"description": "Absolute https://www.imdb.com/title/<id>/ URL", "in": "query", "name": "url", "type": "string", "x-example": "https://www.imdb.com/title/tt1375666/"}], "path": "/imdb/title/keywords", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "id", "type": "string"}, {"in": "query", "name": "url", "type": "string"}], "security": ["ApiKeyAuth"]}, "imdb-title-parental-guide": {"id": "imdb-title-parental-guide", "method": "GET", "params": [{"description": "IMDb title id", "in": "query", "name": "id", "type": "string", "x-example": "tt1375666"}, {"description": "Absolute https://www.imdb.com/title/<id>/ URL", "in": "query", "name": "url", "type": "string", "x-example": "https://www.imdb.com/title/tt1375666/"}], "path": "/imdb/title/parental-guide", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "id", "type": "string"}, {"in": "query", "name": "url", "type": "string"}], "security": ["ApiKeyAuth"]}, "imdb-title-public-facts-analysis": {"id": "imdb-title-public-facts-analysis", "method": "GET", "params": [{"description": "IMDb title id", "in": "query", "name": "id", "type": "string", "x-example": "tt1375666"}, {"description": "Absolute https://www.imdb.com/title/<id>/ URL", "in": "query", "name": "url", "type": "string", "x-example": "https://www.imdb.com/title/tt1375666/"}], "path": "/imdb/title/public-facts-analysis", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "id", "type": "string"}, {"in": "query", "name": "url", "type": "string"}], "security": ["ApiKeyAuth"]}, "imdb-title-quotes": {"id": "imdb-title-quotes", "method": "GET", "params": [{"description": "IMDb title id", "in": "query", "name": "id", "type": "string", "x-example": "tt1375666"}, {"description": "Absolute https://www.imdb.com/title/<id>/ URL", "in": "query", "name": "url", "type": "string", "x-example": "https://www.imdb.com/title/tt1375666/"}], "path": "/imdb/title/quotes", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "id", "type": "string"}, {"in": "query", "name": "url", "type": "string"}], "security": ["ApiKeyAuth"]}, "imdb-title-ratings": {"id": "imdb-title-ratings", "method": "GET", "params": [{"description": "IMDb title id", "in": "query", "name": "id", "type": "string", "x-example": "tt0111161"}, {"description": "Absolute IMDb title URL", "in": "query", "name": "url", "type": "string", "x-example": "https://www.imdb.com/title/tt0111161/"}], "path": "/imdb/title/ratings", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "id", "type": "string"}, {"in": "query", "name": "url", "type": "string"}], "security": ["ApiKeyAuth"]}, "imdb-title-release-info": {"id": "imdb-title-release-info", "method": "GET", "params": [{"description": "IMDb title id", "in": "query", "name": "id", "type": "string", "x-example": "tt1375666"}, {"description": "Absolute https://www.imdb.com/title/<id>/ URL", "in": "query", "name": "url", "type": "string", "x-example": "https://www.imdb.com/title/tt1375666/"}], "path": "/imdb/title/release-info", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "id", "type": "string"}, {"in": "query", "name": "url", "type": "string"}], "security": ["ApiKeyAuth"]}, "imdb-title-reviews": {"id": "imdb-title-reviews", "method": "GET", "params": [{"description": "IMDb title id", "in": "query", "name": "id", "type": "string", "x-example": "tt1375666"}, {"description": "Absolute https://www.imdb.com/title/<id>/ URL", "in": "query", "name": "url", "type": "string", "x-example": "https://www.imdb.com/title/tt1375666/"}, {"description": "Rows to return, default 10, max 20", "in": "query", "name": "limit", "type": "integer", "x-example": 10}], "path": "/imdb/title/reviews", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "id", "type": "string"}, {"in": "query", "name": "url", "type": "string"}, {"in": "query", "name": "limit", "type": "integer"}], "security": ["ApiKeyAuth"]}, "imdb-title-similar": {"id": "imdb-title-similar", "method": "GET", "params": [{"description": "IMDb title id", "in": "query", "name": "id", "type": "string", "x-example": "tt0111161"}, {"description": "Absolute IMDb title URL", "in": "query", "name": "url", "type": "string", "x-example": "https://www.imdb.com/title/tt0111161/"}], "path": "/imdb/title/similar", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "id", "type": "string"}, {"in": "query", "name": "url", "type": "string"}], "security": ["ApiKeyAuth"]}, "imdb-title-technical-specs": {"id": "imdb-title-technical-specs", "method": "GET", "params": [{"description": "IMDb title id", "in": "query", "name": "id", "type": "string", "x-example": "tt1375666"}, {"description": "Absolute https://www.imdb.com/title/<id>/ URL", "in": "query", "name": "url", "type": "string", "x-example": "https://www.imdb.com/title/tt1375666/"}], "path": "/imdb/title/technical-specs", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "id", "type": "string"}, {"in": "query", "name": "url", "type": "string"}], "security": ["ApiKeyAuth"]}, "imdb-title-trivia": {"id": "imdb-title-trivia", "method": "GET", "params": [{"description": "IMDb title id", "in": "query", "name": "id", "type": "string", "x-example": "tt1375666"}, {"description": "Absolute https://www.imdb.com/title/<id>/ URL", "in": "query", "name": "url", "type": "string", "x-example": "https://www.imdb.com/title/tt1375666/"}], "path": "/imdb/title/trivia", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "id", "type": "string"}, {"in": "query", "name": "url", "type": "string"}], "security": ["ApiKeyAuth"]}, "imdb-title-videos": {"id": "imdb-title-videos", "method": "GET", "params": [{"description": "IMDb title id", "in": "query", "name": "id", "type": "string", "x-example": "tt1375666"}, {"description": "Absolute https://www.imdb.com/title/<id>/ URL", "in": "query", "name": "url", "type": "string", "x-example": "https://www.imdb.com/title/tt1375666/"}, {"description": "Rows to return, default 50, max 100", "in": "query", "name": "limit", "type": "integer", "x-example": 50}], "path": "/imdb/title/videos", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "id", "type": "string"}, {"in": "query", "name": "url", "type": "string"}, {"in": "query", "name": "limit", "type": "integer"}], "security": ["ApiKeyAuth"]}}
JSON, true, 512, JSON_THROW_ON_ERROR);
    }

    public function request(string $operationId, array $params = [], string $responseType = 'auto'): mixed
    {
        if ($this->closed) {
            throw new ClientException('Client is closed', null, $operationId);
        }
        $operation = self::$operations[$operationId] ?? null;
        if ($operation === null) {
            throw new ClientException('Unknown operation: ' . $operationId, null, $operationId);
        }
        if ($this->apiKey === '') {
            throw new ClientException('Crawlora API key is required', null, $operationId);
        }
        $url = $this->buildUrl($operation, $params);
        $headers = [
            'x-api-key: ' . $this->apiKey,
            'User-Agent: crawlora-imdb-php/0.1.0',
            'Accept: ' . (in_array('text/plain', $operation['produces'], true) ? 'application/json, text/plain' : 'application/json'),
        ];
        try {
            [$status, $contentType, $body] = $this->send($url, $headers, $operationId);
        } catch (CrawloraException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new NetworkException('Crawlora request failed: ' . $exception->getMessage(), null, $operationId, null, $exception);
        }
        if ($status < 200 || $status >= 300) {
            $class = $status >= 500 ? ServerException::class : ClientException::class;
            throw new $class('Crawlora returned HTTP ' . $status, $status, $operationId, $body);
        }
        return $this->parseResponse($body, $contentType, $operation, $params, $responseType);
    }

    public function close(): void
    {
        $this->closed = true;
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }

    public function operationCount(): int
    {
        return self::OPERATION_COUNT;
    }

    public function operationIds(): array
    {
        return self::OPERATION_IDS;
    }

    public function operations(): array
    {
        return self::$operations;
    }

    public function charts(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("imdb-charts", $params, $responseType);
    }
    public function image_types(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("imdb-image-types", $params, $responseType);
    }
    public function name(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("imdb-name", $params, $responseType);
    }
    public function name_awards(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("imdb-name-awards", $params, $responseType);
    }
    public function name_credits(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("imdb-name-credits", $params, $responseType);
    }
    public function name_images(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("imdb-name-images", $params, $responseType);
    }
    public function name_videos(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("imdb-name-videos", $params, $responseType);
    }
    public function search(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("imdb-search", $params, $responseType);
    }
    public function search_title(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("imdb-search-title", $params, $responseType);
    }
    public function title(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("imdb-title", $params, $responseType);
    }
    public function title_awards(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("imdb-title-awards", $params, $responseType);
    }
    public function title_box_office(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("imdb-title-box-office", $params, $responseType);
    }
    public function title_company_credits(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("imdb-title-company-credits", $params, $responseType);
    }
    public function title_connections(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("imdb-title-connections", $params, $responseType);
    }
    public function title_credits(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("imdb-title-credits", $params, $responseType);
    }
    public function title_episodes(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("imdb-title-episodes", $params, $responseType);
    }
    public function title_filming_locations(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("imdb-title-filming-locations", $params, $responseType);
    }
    public function title_goofs(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("imdb-title-goofs", $params, $responseType);
    }
    public function title_images(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("imdb-title-images", $params, $responseType);
    }
    public function title_keywords(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("imdb-title-keywords", $params, $responseType);
    }
    public function title_parental_guide(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("imdb-title-parental-guide", $params, $responseType);
    }
    public function title_public_facts_analysis(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("imdb-title-public-facts-analysis", $params, $responseType);
    }
    public function title_quotes(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("imdb-title-quotes", $params, $responseType);
    }
    public function title_ratings(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("imdb-title-ratings", $params, $responseType);
    }
    public function title_release_info(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("imdb-title-release-info", $params, $responseType);
    }
    public function title_reviews(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("imdb-title-reviews", $params, $responseType);
    }
    public function title_similar(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("imdb-title-similar", $params, $responseType);
    }
    public function title_technical_specs(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("imdb-title-technical-specs", $params, $responseType);
    }
    public function title_trivia(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("imdb-title-trivia", $params, $responseType);
    }
    public function title_videos(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("imdb-title-videos", $params, $responseType);
    }

    private function buildUrl(array $operation, array $params): string
    {
        $known = array_column($operation['params'], 'name');
        $unknown = array_diff(array_keys($params), $known, ['response_type', '_response_type']);
        if ($unknown !== []) {
            throw new ClientException('Unknown parameters: ' . implode(', ', $unknown), null, $operation['id']);
        }
        $path = $operation['path'];
        foreach ($operation['params'] as $param) {
            if ($param['in'] !== 'path') {
                continue;
            }
            $name = $param['name'];
            if (!array_key_exists($name, $params) || $params[$name] === null) {
                throw new ClientException('Missing path parameter: ' . $name, null, $operation['id']);
            }
            $path = str_replace('{' . $name . '}', rawurlencode((string) $params[$name]), $path);
        }
        $pairs = [];
        foreach ($operation['queryParams'] as $param) {
            $name = $param['name'];
            $value = $params[$name] ?? ($param['default'] ?? null);
            if ($value === null) {
                if ($param['required'] ?? false) {
                    throw new ClientException('Missing query parameter: ' . $name, null, $operation['id']);
                }
                continue;
            }
            $enumValues = $param['enum'] ?? ($param['items']['enum'] ?? null);
            $values = is_array($value) ? $value : [$value];
            $invalidEnum = false;
            foreach ($values as $item) {
                if ($enumValues !== null && !in_array((string) $item, array_map('strval', $enumValues), true)) {
                    $invalidEnum = true;
                    break;
                }
            }
            if ($invalidEnum) {
                throw new ClientException('Invalid value for ' . $name, null, $operation['id']);
            }
            if (is_array($value)) {
                $format = $param['collectionFormat'] ?? 'csv';
                if ($format === 'multi') {
                    foreach ($value as $item) {
                        $pairs[] = [rawurlencode($name), rawurlencode($this->stringify($item))];
                    }
                } else {
                    $separator = ['csv' => ',', 'ssv' => ' ', 'tsv' => "\t", 'pipes' => '|'][$format] ?? ',';
                    $pairs[] = [rawurlencode($name), rawurlencode(implode($separator, array_map([$this, 'stringify'], $value)))];
                }
            } else {
                $pairs[] = [rawurlencode($name), rawurlencode($this->stringify($value))];
            }
        }
        $query = implode('&', array_map(static fn(array $pair): string => $pair[0] . '=' . $pair[1], $pairs));
        return $this->baseUrl . $path . ($query === '' ? '' : '?' . $query);
    }

    private function stringify(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_array($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR);
        }
        return (string) $value;
    }

    private function send(string $url, array $headers, string $operationId): array
    {
        if ($this->transport !== null) {
            $result = ($this->transport)($url, $headers, $this->timeout);
            return [(int) $result['status'], (string) ($result['content_type'] ?? ''), (string) ($result['body'] ?? '')];
        }
        $handle = curl_init($url);
        if ($handle === false) {
            throw new NetworkException('Could not initialize cURL', null, $operationId);
        }
        curl_setopt_array($handle, [
            CURLOPT_HTTPGET => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT_MS => (int) ($this->timeout * 1000),
            CURLOPT_CONNECTTIMEOUT_MS => (int) ($this->timeout * 1000),
        ]);
        $body = curl_exec($handle);
        if ($body === false) {
            $message = curl_error($handle);
            curl_close($handle);
            throw new NetworkException('Crawlora request failed: ' . $message, null, $operationId);
        }
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $contentType = (string) curl_getinfo($handle, CURLINFO_CONTENT_TYPE);
        curl_close($handle);
        return [$status, $contentType, (string) $body];
    }

    private function parseResponse(string $body, string $contentType, array $operation, array $params, string $responseType): mixed
    {
        if (!in_array($responseType, ['auto', 'json', 'text'], true)) {
            throw new ClientException('responseType must be auto, json, or text', null, $operation['id']);
        }
        $format = null;
        foreach ($operation['params'] as $param) {
            if ($param['name'] === 'format') {
                $format = $param;
                break;
            }
        }
        $textFormats = array_values(array_filter($format['enum'] ?? [], static fn($value): bool => !in_array(strtolower((string) $value), ['json', 'application/json'], true)));
        $rawFormat = isset($params['format']) && in_array((string) $params['format'], array_map('strval', $textFormats), true);
        $jsonFormat = isset($params['format']) && in_array(strtolower((string) $params['format']), ['json', 'application/json'], true);
        $isJson = $jsonFormat || stripos($contentType, 'json') !== false || $operation['produces'] === ['application/json'];
        if ($responseType === 'text' || $rawFormat || ($responseType === 'auto' && !$isJson)) {
            return $body;
        }
        try {
            return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new CrawloraException('Invalid JSON response from Crawlora: ' . $exception->getMessage(), null, $operation['id'], $body, $exception);
        }
    }
}

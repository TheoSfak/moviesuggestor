<?php

namespace MovieSuggestor;

/**
 * TMDB (The Movie Database) API Service
 * Provides integration with TMDB API v3
 * No external dependencies - uses native PHP curl/file_get_contents
 */
class TMDBService
{
    private const API_BASE_URL = 'https://api.themoviedb.org/3';
    private const IMAGE_BASE_URL = 'https://image.tmdb.org/t/p';
    private const DEFAULT_LANGUAGE = 'el-GR'; // Greek language support
    
    private string $apiKey;
    private string $language;
    
    // TMDB Genre ID to Greek Category mapping
    private const GENRE_MAP = [
        18 => 'Δράμα',           // Drama
        35 => 'Κωμωδία',         // Comedy
        28 => 'Δράση',           // Action
        12 => 'Περιπέτεια',      // Adventure
        10749 => 'Ρομαντική',    // Romance
        53 => 'Θρίλερ',          // Thriller
        27 => 'Τρόμου',          // Horror
        80 => 'Αστυνομική',      // Crime
        14 => 'Φαντασίας',       // Fantasy
        878 => 'Επιστημονική Φαντασία', // Science Fiction
        16 => 'Κινουμένων Σχεδίων', // Animation
        10752 => 'Πολεμική',     // War
        37 => 'Δυτικά',          // Western
        99 => 'Ντοκιμαντέρ',     // Documentary
        9648 => 'Μυστηρίου',     // Mystery
        10751 => 'Οικογενειακή', // Family
        36 => 'Ιστορική',        // History
        10402 => 'Μουσική',      // Music
    ];

    public function __construct(?string $apiKey = null, ?string $language = null)
    {
        $this->apiKey = $apiKey ?? getenv('TMDB_API_KEY') ?: '';
        $this->language = $language ?? self::DEFAULT_LANGUAGE;
        
        if (empty($this->apiKey)) {
            error_log('TMDB API Key not configured. Set TMDB_API_KEY environment variable.');
        }
    }

    /**
     * Search movies by query string
     * 
     * @param string $query Search query
     * @param int $page Page number (default: 1)
     * @return array Response with standardized results or error
     */
    public function searchMovies(string $query, int $page = 1): array
    {
        if (empty($this->apiKey)) {
            return $this->errorResponse('TMDB API key not configured');
        }

        if (empty(trim($query))) {
            return $this->errorResponse('Search query is required');
        }

        $params = [
            'api_key' => $this->apiKey,
            'language' => 'en-US', // Use English for search to get better results
            'query' => $query,
            'page' => max(1, $page),
            'include_adult' => 'false'
        ];

        $url = self::API_BASE_URL . '/search/movie?' . http_build_query($params);
        
        $response = $this->makeRequest($url);
        
        // Standardize response format
        if ($response['success']) {
            return $this->formatMovieResults($response['data']);
        }
        
        return $response;
    }

    /**
     * Get detailed information about a specific movie
     * 
     * @param int $tmdbId TMDB movie ID
     * @return array Movie details or error
     */
    public function getMovieDetails(int $tmdbId): array
    {
        if (empty($this->apiKey)) {
            return $this->errorResponse('TMDB API key not configured');
        }

        $params = [
            'api_key' => $this->apiKey,
            'language' => $this->language,
            'append_to_response' => 'credits,videos,external_ids'
        ];

        $url = self::API_BASE_URL . "/movie/{$tmdbId}?" . http_build_query($params);
        
        return $this->makeRequest($url);
    }

    /**
     * Discover movies with advanced filters
     * 
     * Supports all filtering options including:
     * - genre_ids: Array of TMDB genre IDs or comma-separated string
     * - categories: Array of Greek category names (auto-converted to genre IDs)
     * - vote_average_gte: Minimum rating (0-10)
     * - vote_average_lte: Maximum rating (0-10)
     * - primary_release_date_gte: Start date (YYYY or YYYY-MM-DD)
     * - primary_release_date_lte: End date (YYYY or YYYY-MM-DD)
     * - year_from: Year from (converted to release date)
     * - year_to: Year to (converted to release date)
     * - with_original_language: Language code (e.g., 'el' for Greek)
     * - sort_by: Sort order (popularity.desc, vote_average.desc, etc.)
     * - page: Page number
     * - query: Search query (uses search instead of discover)
     * 
     * @param array $filters Filter options
     * @return array Discovered movies with standardized format
     */
    public function discoverMovies(array $filters = []): array
    {
        if (empty($this->apiKey)) {
            return $this->errorResponse('TMDB API key not configured');
        }

        // If search query provided, use search instead
        if (!empty($filters['query'])) {
            return $this->searchMovies($filters['query'], $filters['page'] ?? 1);
        }

        $params = [
            'api_key' => $this->apiKey,
            'language' => $this->language,
            'sort_by' => $filters['sort_by'] ?? 'popularity.desc',
            'include_adult' => 'false',
            'page' => $filters['page'] ?? 1,
            'vote_count.gte' => 10 // Ensure movies have enough votes for reliable ratings
        ];

        // Handle genre filters - support both genre_ids and Greek categories
        $genreIds = [];
        
        // Direct genre IDs
        if (!empty($filters['genre_ids'])) {
            if (is_array($filters['genre_ids'])) {
                $genreIds = $filters['genre_ids'];
            } else {
                $genreIds = explode(',', $filters['genre_ids']);
            }
        }
        
        // Convert Greek categories to genre IDs
        if (!empty($filters['categories'])) {
            $categories = is_array($filters['categories']) ? $filters['categories'] : [$filters['categories']];
            foreach ($categories as $category) {
                $categoryGenreId = $this->categoryToGenreId($category);
                if ($categoryGenreId) {
                    $genreIds[] = $categoryGenreId;
                }
            }
        }
        
        // Legacy single genre support
        if (!empty($filters['genre'])) {
            $genreIds[] = $filters['genre'];
        }
        
        if (!empty($genreIds)) {
            $params['with_genres'] = implode(',', array_unique($genreIds));
        }

        // Minimum rating filter
        if (isset($filters['vote_average_gte']) && $filters['vote_average_gte'] > 0) {
            $params['vote_average.gte'] = (float)$filters['vote_average_gte'];
        } elseif (isset($filters['min_score']) && $filters['min_score'] > 0) {
            $params['vote_average.gte'] = (float)$filters['min_score'];
        }

        // Maximum rating filter
        if (isset($filters['vote_average_lte']) && $filters['vote_average_lte'] > 0) {
            $params['vote_average.lte'] = (float)$filters['vote_average_lte'];
        }

        // Year range filters
        if (!empty($filters['year_from'])) {
            $params['primary_release_date.gte'] = $filters['year_from'] . '-01-01';
        } elseif (!empty($filters['primary_release_date_gte'])) {
            $params['primary_release_date.gte'] = $filters['primary_release_date_gte'];
        }
        
        if (!empty($filters['year_to'])) {
            $params['primary_release_date.lte'] = $filters['year_to'] . '-12-31';
        } elseif (!empty($filters['primary_release_date_lte'])) {
            $params['primary_release_date.lte'] = $filters['primary_release_date_lte'];
        }

        // Single year filter (legacy)
        if (!empty($filters['year']) && empty($params['primary_release_date.gte'])) {
            $params['primary_release_year'] = $filters['year'];
        }

        // Language filter (for Greek movies)
        if (!empty($filters['with_original_language'])) {
            $params['with_original_language'] = $filters['with_original_language'];
        }

        $url = self::API_BASE_URL . '/discover/movie?' . http_build_query($params);
        
        $response = $this->makeRequest($url);
        
        // Standardize response format
        if ($response['success']) {
            return $this->formatMovieResults($response['data']);
        }
        
        return $response;
    }

    /**
     * Convert Greek category name to TMDB genre ID
     * 
     * @param string $category Greek category name
     * @return int|null Genre ID or null if not found
     */
    public function categoryToGenreId(string $category): ?int
    {
        $reverseMap = array_flip(self::GENRE_MAP);
        return $reverseMap[$category] ?? null;
    }

    /**
     * Format movie results to standardized structure
     * 
     * @param array $tmdbData Raw TMDB API response
     * @return array Formatted response
     */
    private function formatMovieResults(array $tmdbData): array
    {
        $results = [];
        
        if (isset($tmdbData['results'])) {
            foreach ($tmdbData['results'] as $movie) {
                $results[] = $this->formatMovie($movie);
            }
        }
        
        return [
            'success' => true,
            'results' => $results,
            'page' => $tmdbData['page'] ?? 1,
            'total_pages' => $tmdbData['total_pages'] ?? 1,
            'total_results' => $tmdbData['total_results'] ?? count($results)
        ];
    }

    /**
     * Format single movie to standardized structure
     * 
     * @param array $movie Raw TMDB movie data
     * @return array Formatted movie
     */
    public function formatMovie(array $movie): array
    {
        $year = null;
        if (!empty($movie['release_date'])) {
            $year = (int)substr($movie['release_date'], 0, 4);
        }
        
        // Extract IMDB rating if available
        $imdbRating = null;
        if (!empty($movie['vote_average']) && $movie['vote_average'] >= 6) {
            // TMDB ratings correlate well with IMDB
            // For display purposes, we'll show the TMDB rating as a proxy
            $imdbRating = round($movie['vote_average'], 1);
        }
        
        return [
            'id' => $movie['id'], // tmdb_id
            'tmdb_id' => $movie['id'],
            'title' => $movie['title'] ?? 'Unknown Title',
            'original_title' => $movie['original_title'] ?? '',
            'description' => $movie['overview'] ?? '',
            'overview' => $movie['overview'] ?? '', // Keep for backward compatibility
            'category' => $this->mapGenresToCategory($movie['genre_ids'] ?? []),
            'genre_ids' => $movie['genre_ids'] ?? [],
            'score' => round($movie['vote_average'] ?? 0, 1),
            'vote_average' => round($movie['vote_average'] ?? 0, 1),
            'vote_count' => $movie['vote_count'] ?? 0,
            'imdb_rating' => $imdbRating,
            'imdb_id' => $movie['imdb_id'] ?? null,
            'year' => $year,
            'release_year' => $year,
            'release_date' => $movie['release_date'] ?? null,
            'poster_path' => $movie['poster_path'] ?? null,
            'poster_url' => $this->getPosterUrl($movie['poster_path'] ?? null),
            'backdrop_path' => $movie['backdrop_path'] ?? null,
            'backdrop_url' => $this->getBackdropUrl($movie['backdrop_path'] ?? null),
            'popularity' => $movie['popularity'] ?? 0,
            'original_language' => $movie['original_language'] ?? '',
        ];
    }

    /**
     * Get popular movies
     * 
     * @param int $page Page number
     * @return array Popular movies with standardized format or error
     */
    public function getPopularMovies(int $page = 1): array
    {
        if (empty($this->apiKey)) {
            return $this->errorResponse('TMDB API key not configured');
        }

        $params = [
            'api_key' => $this->apiKey,
            'language' => $this->language,
            'page' => max(1, $page)
        ];

        $url = self::API_BASE_URL . '/movie/popular?' . http_build_query($params);
        
        $response = $this->makeRequest($url);
        
        // Standardize response format
        if ($response['success']) {
            return $this->formatMovieResults($response['data']);
        }
        
        return $response;
    }

    /**
     * Convert TMDB genre IDs to Greek category name
     * 
     * @param array $genreIds Array of TMDB genre IDs
     * @return string Greek category name
     */
    public function mapGenresToCategory(array $genreIds): string
    {
        if (empty($genreIds)) {
            return 'Άλλο'; // Other
        }

        // Return the first mapped genre
        foreach ($genreIds as $genreId) {
            if (isset(self::GENRE_MAP[$genreId])) {
                return self::GENRE_MAP[$genreId];
            }
        }

        return 'Άλλο'; // Default to "Other" if no match
    }

    /**
     * Get full poster URL
     * 
     * @param string|null $posterPath Poster path from TMDB
     * @param string $size Image size (w92, w154, w185, w342, w500, w780, original)
     * @return string|null Full poster URL or null
     */
    public function getPosterUrl(?string $posterPath, string $size = 'w500'): ?string
    {
        if (empty($posterPath)) {
            return null;
        }

        return self::IMAGE_BASE_URL . "/{$size}{$posterPath}";
    }

    /**
     * Get full backdrop URL
     * 
     * @param string|null $backdropPath Backdrop path from TMDB
     * @param string $size Image size (w300, w780, w1280, original)
     * @return string|null Full backdrop URL or null
     */
    public function getBackdropUrl(?string $backdropPath, string $size = 'w1280'): ?string
    {
        if (empty($backdropPath)) {
            return null;
        }

        return self::IMAGE_BASE_URL . "/{$size}{$backdropPath}";
    }

    /**
     * Extract director from movie credits
     * 
     * @param array $movieDetails Movie details with credits
     * @return string|null Director name or null
     */
    public function extractDirector(array $movieDetails): ?string
    {
        if (!isset($movieDetails['credits']['crew'])) {
            return null;
        }

        foreach ($movieDetails['credits']['crew'] as $crew) {
            if ($crew['job'] === 'Director') {
                return $crew['name'];
            }
        }

        return null;
    }

    /**
     * Extract top actors from movie credits
     * 
     * @param array $movieDetails Movie details with credits
     * @param int $limit Maximum number of actors
     * @return string Comma-separated actor names
     */
    public function extractActors(array $movieDetails, int $limit = 5): string
    {
        if (!isset($movieDetails['credits']['cast'])) {
            return '';
        }

        $actors = array_slice($movieDetails['credits']['cast'], 0, $limit);
        $actorNames = array_map(fn($actor) => $actor['name'], $actors);
        
        return implode(', ', $actorNames);
    }

    /**
     * Get movie trailer URL
     * 
     * @param int $tmdbId TMDB movie ID
     * @return string|null YouTube trailer URL or null
     */
    public function getMovieTrailer(int $tmdbId): ?string
    {
        if (empty($this->apiKey)) {
            return null;
        }

        $params = [
            'api_key' => $this->apiKey,
            'language' => $this->language
        ];

        $url = self::API_BASE_URL . "/movie/{$tmdbId}/videos?" . http_build_query($params);
        
        $response = $this->makeRequest($url);
        
        if ($response['success'] && isset($response['data']['results'])) {
            foreach ($response['data']['results'] as $video) {
                if ($video['type'] === 'Trailer' && $video['site'] === 'YouTube') {
                    return 'https://www.youtube.com/watch?v=' . $video['key'];
                }
            }
        }
        
        return null;
    }

    /**
     * Extract YouTube trailer URL
     * 
     * @param array $movieDetails Movie details with videos
     * @return string|null YouTube URL or null
     */
    public function extractTrailerUrl(array $movieDetails): ?string
    {
        if (!isset($movieDetails['videos']['results'])) {
            return null;
        }

        foreach ($movieDetails['videos']['results'] as $video) {
            if ($video['type'] === 'Trailer' && $video['site'] === 'YouTube') {
                return 'https://www.youtube.com/watch?v=' . $video['key'];
            }
        }

        return null;
    }

    /**
     * Make HTTP request to TMDB API
     * 
     * @param string $url Request URL
     * @return array Decoded JSON response or error
     */
    private function makeRequest(string $url): array
    {
        // Try cURL first (preferred method)
        if (function_exists('curl_init')) {
            return $this->makeRequestCurl($url);
        }

        // Fallback to file_get_contents
        if (ini_get('allow_url_fopen')) {
            return $this->makeRequestFileGetContents($url);
        }

        return $this->errorResponse('No HTTP client available (curl or allow_url_fopen required)');
    }

    /**
     * Make request using cURL
     * 
     * @param string $url Request URL
     * @return array Response or error
     */
    private function makeRequestCurl(string $url): array
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'MovieSuggestor/1.0',
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            error_log("TMDB API cURL error: {$error}");
            return $this->errorResponse("Network error: {$error}");
        }

        return $this->handleResponse($response, $httpCode);
    }

    /**
     * Make request using file_get_contents
     * 
     * @param string $url Request URL
     * @return array Response or error
     */
    private function makeRequestFileGetContents(string $url): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Accept: application/json\r\n" .
                           "User-Agent: MovieSuggestor/1.0\r\n",
                'timeout' => 10,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ]
        ]);

        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            error_log("TMDB API file_get_contents error for URL: {$url}");
            return $this->errorResponse('Network error occurred');
        }

        // Extract HTTP response code
        $httpCode = 200;
        if (isset($http_response_header[0])) {
            preg_match('/\d{3}/', $http_response_header[0], $matches);
            $httpCode = (int)($matches[0] ?? 200);
        }

        return $this->handleResponse($response, $httpCode);
    }

    /**
     * Handle API response
     * 
     * @param string $response Raw response
     * @param int $httpCode HTTP status code
     * @return array Parsed response or error
     */
    private function handleResponse(string $response, int $httpCode): array
    {
        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("TMDB API JSON decode error: " . json_last_error_msg());
            return $this->errorResponse('Invalid API response format');
        }

        // Handle HTTP errors
        if ($httpCode === 401) {
            return $this->errorResponse('Invalid API key');
        }

        if ($httpCode === 404) {
            return $this->errorResponse('Resource not found');
        }

        if ($httpCode === 429) {
            return $this->errorResponse('Rate limit exceeded. Please try again later.');
        }

        if ($httpCode >= 400) {
            $message = $data['status_message'] ?? 'API error occurred';
            return $this->errorResponse($message);
        }

        return [
            'success' => true,
            'data' => $data
        ];
    }

    /**
     * Create error response
     * 
     * @param string $message Error message
     * @return array Error response
     */
    private function errorResponse(string $message): array
    {
        return [
            'success' => false,
            'error' => $message
        ];
    }

    /**
     * Check if API key is configured
     * 
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Get all genre mappings
     * 
     * @return array Genre map
     */
    public static function getGenreMap(): array
    {
        return self::GENRE_MAP;
    }
}

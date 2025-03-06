<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class TmdbService
{
    private string $baseUrl;
    private string $apiKey;
    private string $accessToken;

    public function __construct()
    {
        $this->baseUrl = config('services.tmdb.base_url') ?? 'https://api.themoviedb.org/3';
        
        $apiKey = config('services.tmdb.api_key');
        $accessToken = config('services.tmdb.access_token');

        if (!$apiKey || !$accessToken) {
            throw new RuntimeException('TMDB API credentials not configured properly');
        }

        $this->apiKey = $apiKey;
        $this->accessToken = $accessToken;
    }

    protected function get(string $endpoint, array $params = [])
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->accessToken,
            'accept' => 'application/json',
        ])->get("{$this->baseUrl}{$endpoint}", $params)->json();
    }

    protected function getWithApiKey(string $endpoint, array $params = [])
    {
        return Http::get("{$this->baseUrl}{$endpoint}", [
            'api_key' => $this->apiKey,
            ...$params
        ])->json();
    }

    public function searchMovies(string $query)
    {
        return $this->get('/search/movie', ['query' => $query]);
    }

    public function getMovie(int $id)
    {
        return $this->get("/movie/{$id}");
    }

    // TV Show specific methods
    public function getTVShow($id)
    {
        return $this->get("/tv/{$id}");
    }

    public function getTVSeason($showId, $seasonNumber)
    {
        return $this->get("/tv/{$showId}/season/{$seasonNumber}");
    }

    public function getTVEpisode($showId, $seasonNumber, $episodeNumber)
    {
        return $this->get("/tv/{$showId}/season/{$seasonNumber}/episode/{$episodeNumber}");
    }

    public function searchTVShows($query)
    {
        return $this->get('/search/tv', ['query' => $query]);
    }

    public function getPopularTVShows()
    {
        return $this->get('/tv/popular');
    }

    public function getAiringToday()
    {
        return $this->get('/tv/airing_today');
    }

    public function getOnTheAir()
    {
        return $this->get('/tv/on_the_air');
    }

    public function testConnection()
    {
        try {
            return $this->get('/configuration');
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
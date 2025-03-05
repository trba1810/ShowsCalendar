<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TmdbService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $accessToken;

    public function __construct()
    {
        $this->baseUrl = config('services.tmdb.base_url');
        $this->apiKey = config('services.tmdb.api_key');
        $this->accessToken = config('services.tmdb.access_token');
    }

    protected function get(string $endpoint, array $params = [])
    {
        // Using Bearer token authentication (preferred method)
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->accessToken,
            'accept' => 'application/json',
        ])->get("{$this->baseUrl}{$endpoint}", $params)->json();
    }

    protected function getWithApiKey(string $endpoint, array $params = [])
    {
        // Alternative method using api_key parameter
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
}
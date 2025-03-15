<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TvMazeService
{
    protected $baseUrl = 'https://api.tvmaze.com';

    public function getShowById($id)
    {
        try {
            $response = Http::get("{$this->baseUrl}/shows/{$id}");
            
            Log::info('TVMaze API call', [
                'url' => "{$this->baseUrl}/shows/{$id}",
                'status' => $response->status()
            ]);

            if (!$response->successful()) {
                Log::error('TVMaze API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \Exception('Failed to fetch show data');
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('TVMaze service error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function searchShows($query)
    {
        return Http::get("{$this->baseUrl}/search/shows", [
            'q' => $query
        ])->json();
    }

    public function airingToday()
    {
        try {
            $shows = $this->tvmaze->getAiringToday();
            return response()->json($shows);
        } catch (\Exception $e) {
            Log::error('Error fetching airing shows: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch airing shows'], 500);
        }
    }

    public function getEpisode($showId, $seasonNumber, $episodeNumber)
    {
        return Http::get("{$this->baseUrl}/shows/{$showId}/episodebynumber", [
            'season' => $seasonNumber,
            'number' => $episodeNumber
        ])->json();
    }

    public function getTvShowDetails($showId)
    {
        $show = Http::get("{$this->baseUrl}/shows/{$showId}")->json();
        $episodes = Http::get("{$this->baseUrl}/shows/{$showId}/episodes")->json();
        
        return [
            'show' => $show,
            'episodes' => $episodes
        ];
    }

    public function getMonthlyEpisodes($showId)
    {
        $startDate = now()->startOfMonth()->format('Y-m-d');
        $endDate = now()->endOfMonth()->format('Y-m-d');
        
        return Http::get("{$this->baseUrl}/shows/{$showId}/episodes", [
            'start_date' => $startDate,
            'end_date' => $endDate
        ])->json();
    }

    public function getEpisodes($showId)
    {
        try {
            $response = Http::get("{$this->baseUrl}/shows/{$showId}/episodes");

            Log::debug('TVMaze API Response - Episodes', [
                'url' => "{$this->baseUrl}/shows/{$showId}/episodes",
                'status' => $response->status(),
                'show_id' => $showId
            ]);

            if (!$response->successful()) {
                Log::error('TVMaze API error fetching episodes', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \Exception('Failed to fetch episodes');
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('TVMaze service error fetching episodes', [
                'show_id' => $showId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
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
            $response = Http::get('https://api.tvmaze.com/schedule', [
                'country' => 'US',
                'date' => now()->format('Y-m-d')
            ]);
    
            if (!$response->successful()) {
                Log::error('TVMaze API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return response()->json(['error' => 'API request failed'], $response->status());
            }
    
            $shows = collect($response->json())->map(function ($schedule) {
                return [
                    'id' => $schedule['show']['id'],
                    'name' => $schedule['show']['name'],
                    'airtime' => $schedule['airtime'],
                    'airstamp' => $schedule['airstamp'],
                    'runtime' => $schedule['show']['runtime'],
                    'image' => $schedule['show']['image']['medium'] ?? null,
                    'summary' => $schedule['show']['summary'],
                    'season' => $schedule['season'],
                    'episode' => $schedule['number'],
                    'episode_name' => $schedule['name']
                ];
            })->values()->toArray();
    
            return response()->json($shows);
    
        } catch (\Exception $e) {
            Log::error('Error fetching airing shows', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
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

    public function getAiringToday()
    {
        try {
            $today = Carbon::now()->format('Y-m-d');
            $response = Http::get("{$this->baseUrl}/schedule", [
                'country' => 'US',
                'date' => $today
            ]);

            if (!$response->successful()) {
                Log::error('TVMaze API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \Exception('Failed to fetch airing shows');
            }

            $shows = collect($response->json())->map(function ($schedule) {
                return [
                    'id' => $schedule['show']['id'] ?? null,
                    'name' => $schedule['show']['name'] ?? null,
                    'airtime' => $schedule['airtime'] ?? null,
                    'airstamp' => $schedule['airstamp'] ?? null,
                    'runtime' => $schedule['show']['runtime'] ?? null,
                    'image' => $schedule['show']['image']['medium'] ?? null,
                    'summary' => $schedule['show']['summary'] ?? null,
                    'season' => $schedule['season'] ?? null,
                    'episode' => $schedule['number'] ?? null,
                    'episode_name' => $schedule['name'] ?? null
                ];
            })->values()->toArray();

            Log::info('TVMaze API Response - Airing Today', [
                'count' => count($shows),
                'date' => $today
            ]);

            return $shows;
        } catch (\Exception $e) {
            Log::error('TVMaze service error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function getLatestEpisode($showId)
    {
        try {
            // Get all episodes
            $response = Http::get("{$this->baseUrl}/shows/{$showId}/episodes");

            if (!$response->successful()) {
                Log::error('Failed to fetch episodes', [
                    'show_id' => $showId,
                    'status' => $response->status()
                ]);
                throw new \Exception('Failed to fetch episodes');
            }

            $episodes = collect($response->json());

            // Get the latest aired episode
            $latestEpisode = $episodes
                ->filter(function ($episode) {
                    return Carbon::parse($episode['airstamp'])->isPast();
                })
                ->sortByDesc('airstamp')
                ->first();

            if (!$latestEpisode) {
                Log::info('No aired episodes found', ['show_id' => $showId]);
                return null;
            }

            return [
                'id' => $latestEpisode['id'],
                'name' => $latestEpisode['name'],
                'season' => $latestEpisode['season'],
                'number' => $latestEpisode['number'],
                'airdate' => $latestEpisode['airdate'],
                'airstamp' => $latestEpisode['airstamp'],
                'runtime' => $latestEpisode['runtime'],
                'image' => $latestEpisode['image']['medium'] ?? null,
                'summary' => $latestEpisode['summary']
            ];

        } catch (\Exception $e) {
            Log::error('Error fetching latest episode', [
                'show_id' => $showId,
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
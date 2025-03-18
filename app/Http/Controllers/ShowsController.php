<?php

namespace App\Http\Controllers;

use App\Services\TvMazeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShowsController extends Controller
{
    protected $tvmaze;

    public function __construct(TvMazeService $tvmaze)
    {
        $this->tvmaze = $tvmaze;
    }

    public function following()
    {
        $followedShows = Auth::user()->shows()->pluck('tmdb_show_id');
        $shows = [];
        
        foreach($followedShows as $showId) {
            $shows[] = $this->tmdb->get("/tv/{$showId}");
        }
        
        return response()->json($shows);
    }


    public function toggleFollow($id)
    {
        $user = Auth::user();
        if($user->shows()->where('tmdb_show_id', $id)->exists()) {
            $user->shows()->where('tmdb_show_id', $id)->delete();
            $following = false;
        } else {
            $user->shows()->create(['tmdb_show_id' => $id]);
            $following = true;
        }
        
        return response()->json(['following' => $following]);
    }

    public function getMonthlyEpisodes()
    {
        try {
            $episodes = collect();
            $userShows = auth()->user()->shows()->get();

            foreach ($userShows as $show) {
                $monthlyEpisodes = $this->tvmaze->getMonthlyEpisodes($show->tvmaze_id);
                
                foreach ($monthlyEpisodes as $episode) {
                    $episodes->push([
                        'id' => $episode['id'],
                        'title' => $show->name . ' - ' . $episode['name'],
                        'start' => $episode['airstamp'],
                        'description' => $episode['summary'],
                        'show_id' => $show->tvmaze_id,
                        'episode_number' => $episode['number'],
                        'season_number' => $episode['season']
                    ]);
                }
            }

            return response()->json($episodes);
        } catch (\Exception $e) {
            \Log::error('Error fetching monthly episodes: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch monthly episodes'], 500);
        }
    }

    public function show($id)
    {
        try {
            Log::info('Show endpoint called', ['id' => $id]);
            
            $show = $this->tvmaze->getShowById($id);
            Log::info('TVMaze response', ['show' => $show]);
            
            return response()->json($show);
        } catch (\Exception $e) {
            Log::error('Show fetch error', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getShowById($id)
    {
        try {
            $show = $this->tvmaze->getShowById($id);
            return response()->json($show);
        } catch (\Exception $e) {
            Log::error('Error fetching show details: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch show details'], 500);
        }
    }

    public function search(Request $request)
    {
        try {
            $query = $request->get('query');
            $results = $this->tvmaze->searchShows($query);
            return response()->json($results);
        } catch (\Exception $e) {
            Log::error('Error searching shows: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to search shows'], 500);
        }
    }

    public function getEpisodeDetails($showId, $seasonNumber, $episodeNumber)
    {
        try {
            Log::info('Fetching episode details', [
                'show' => $showId,
                'season' => $seasonNumber,
                'episode' => $episodeNumber
            ]);

            $episode = $this->tvmaze->getEpisode($showId, $seasonNumber, $episodeNumber);
            return response()->json($episode);
        } catch (\Exception $e) {
            Log::error('Error fetching episode: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch episode details'], 500);
        }
    }

    public function addShow(Request $request)
    {
        try {
            $showId = $request->input('show_id');
            $show = $this->tvmaze->getShowById($showId);

            $user = auth()->user();
            $user->shows()->create([
                'tvmaze_id' => $show['id'],
                'name' => $show['name'],
                'poster_path' => $show['image']['medium'] ?? null,
            ]);

            return response()->json(['message' => 'Show added successfully']);
        } catch (\Exception $e) {
            Log::error('Error adding show: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to add show'], 500);
        }
    }

    public function removeShow($id)
    {
        try {
            auth()->user()->shows()->where('tvmaze_id', $id)->delete();
            return response()->json(['message' => 'Show removed successfully']);
        } catch (\Exception $e) {
            Log::error('Error removing show: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to remove show'], 500);
        }
    }

    public function getTvShowDetails($id)
    {
        try {
            Log::info('Fetching TV show details', ['show_id' => $id]);

            $show = $this->tvmaze->getShowById($id);
            
            // Get episodes grouped by season
            $episodes = $this->tvmaze->getEpisodes($id);
            $seasons = collect($episodes)->groupBy('season');

            $response = [
                'id' => $show['id'],
                'name' => $show['name'],
                'summary' => $show['summary'],
                'status' => $show['status'],
                'premiered' => $show['premiered'],
                'ended' => $show['ended'],
                'runtime' => $show['runtime'],
                'image' => $show['image']['original'] ?? null,
                'network' => $show['network']['name'] ?? null,
                'genres' => $show['genres'],
                'rating' => $show['rating']['average'] ?? null,
                'seasons' => $seasons->map(function ($episodes, $seasonNumber) {
                    return [
                        'season_number' => $seasonNumber,
                        'episodes' => collect($episodes)->map(function ($episode) {
                            return [
                                'id' => $episode['id'],
                                'name' => $episode['name'],
                                'number' => $episode['number'],
                                'airdate' => $episode['airdate'],
                                'summary' => $episode['summary'],
                                'image' => $episode['image']['medium'] ?? null,
                            ];
                        })->values()->toArray()
                    ];
                })->values()->toArray()
            ];

            Log::info('Successfully fetched TV show details', ['show_id' => $id]);
            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Error fetching TV show details', [
                'show_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Failed to fetch TV show details',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function airingToday()
    {
        try {
            Log::info('Starting airingToday request');
            
            $shows = $this->tvmaze->airingToday();
            
            if (empty($shows)) {
                Log::warning('No shows found for today');
                return response()->json([]);
            }
            
            Log::info('Successfully fetched shows', ['count' => count($shows)]);
            return response()->json($shows);
            
        } catch (\Exception $e) {
            Log::error('Error in airingToday', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Failed to fetch airing shows',
                'debug_message' => $e->getMessage()
            ], 500);
        }
    }

    public function getLatestEpisode($showId)
{
    try {
        $episode = $this->tvmaze->getLatestEpisode($showId);
        
        if (!$episode) {
            return response()->json(['message' => 'No aired episodes found'], 404);
        }

        return response()->json($episode);
    } catch (\Exception $e) {
        Log::error('Error getting latest episode', [
            'show_id' => $showId,
            'error' => $e->getMessage()
        ]);
        return response()->json(['error' => 'Failed to fetch latest episode'], 500);
    }
}
}

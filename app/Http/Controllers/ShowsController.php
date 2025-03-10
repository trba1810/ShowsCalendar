<?php

namespace App\Http\Controllers;

use App\Services\TmdbService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class ShowsController extends Controller
{
    protected TmdbService $tmdb;

    public function __construct(TmdbService $tmdb)
    {
        $this->tmdb = $tmdb;
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

    public function GetShowById(int $id)
    {
        $show = $this->tmdb->getTVShow($id);
        
        return response()->json($show);
    }

    public function getEpisodes()
    {
        $followedShows = Auth::user()->shows()->pluck('tmdb_show_id');
        $events = [];
        
        foreach($followedShows as $showId) {
            $show = $this->tmdb->get("/tv/{$showId}");
            $lastSeason = $show['last_episode_to_air']['season_number'];
            
            $season = $this->tmdb->get("/tv/{$showId}/season/{$lastSeason}");
            
            foreach($season['episodes'] as $episode) {
                if(!empty($episode['air_date'])) {
                    $events[] = [
                        'title' => $show['name'] . " - S{$lastSeason}E{$episode['episode_number']}",
                        'start' => $episode['air_date'],
                        'url' => "/shows/{$showId}/season/{$lastSeason}/episode/{$episode['episode_number']}",
                        'description' => $episode['overview']
                    ];
                }
            }
        }
        
        return response()->json($events);
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
        $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $episodes = collect();

        // Debug logging
        \Log::info("Fetching episodes between $startDate and $endDate");

        $userShows = auth()->user()->shows()->get();
        \Log::info("Found " . $userShows->count() . " user shows");

        foreach ($userShows as $show) {
            try {
                // Get show details
                $showData = $this->tmdb->getTvShowDetails($show->tmdb_id);
                \Log::info("Show data for {$show->tmdb_id}:", ['data' => $showData]);

                // Get all seasons
                $seasons = $showData['seasons'] ?? [];
                \Log::info("Found " . count($seasons) . " seasons for show {$show->tmdb_id}");

                foreach ($seasons as $season) {
                    $seasonNumber = $season['season_number'];
                    
                    // Skip season 0 (usually specials)
                    if ($seasonNumber === 0) continue;

                    // Get season details
                    $seasonData = $this->tmdb->getTvShowSeason($show->tmdb_id, $seasonNumber);
                    \Log::info("Season {$seasonNumber} data:", ['episodes' => count($seasonData['episodes'] ?? [])]);

                    foreach ($seasonData['episodes'] ?? [] as $episode) {
                        if (isset($episode['air_date']) && 
                            $episode['air_date'] >= $startDate && 
                            $episode['air_date'] <= $endDate) {
                            
                            $episodes->push([
                                'id' => $episode['id'],
                                'title' => "{$showData['name']} - S{$seasonNumber}E{$episode['episode_number']}",
                                'start' => $episode['air_date'],
                                'description' => $episode['overview'] ?? '',
                                'show_id' => $show->tmdb_id,
                                'episode_number' => $episode['episode_number'],
                                'season_number' => $seasonNumber
                            ]);

                            \Log::info("Added episode:", [
                                'show' => $showData['name'],
                                'episode' => "S{$seasonNumber}E{$episode['episode_number']}",
                                'air_date' => $episode['air_date']
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Error fetching episodes for show ' . $show->tmdb_id . ': ' . $e->getMessage());
                continue;
            }
        }

        \Log::info("Total episodes found: " . $episodes->count());
        return response()->json($episodes);
    }
}

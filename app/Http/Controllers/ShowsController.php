<?php

namespace App\Http\Controllers;

use App\Services\TmdbService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    public function testConnection()
    {
        return response()->json([
            'test_connection' => $this->tmdb->testConnection(),
            'popular_shows' => $this->tmdb->getPopularTVShows(),
            'airing_today' => $this->tmdb->getAiringToday()
        ]);
    }
}

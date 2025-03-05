<?php

namespace App\Http\Controllers;

use App\Services\TmdbService;
use Illuminate\Http\Request;

class MovieController extends Controller
{
    protected TmdbService $tmdb;

    public function __construct(TmdbService $tmdb)
    {
        $this->tmdb = $tmdb;
    }

    public function search(Request $request)
    {
        $query = $request->input('query');
        $results = $this->tmdb->searchMovies($query);
        
        return response()->json($results);
    }

    public function show(int $id)
    {
        $movie = $this->tmdb->getMovie($id);
        
        return response()->json($movie);
    }
}
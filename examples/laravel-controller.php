<?php

declare(strict_types=1);

use App\Models\Airport;
use Illuminate\Http\Request;

final class AirportController
{
    public function store(Request $request)
    {
        $airport = map($request->all())->to(Airport::class);
        $airport->save();

        return response()->json($airport);
    }
}

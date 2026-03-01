<?php

declare(strict_types=1);

use App\Http\Requests\StoreAirportRequest;
use App\Models\Airport;

final class AirportController
{
    public function store(StoreAirportRequest $request)
    {
        $airport = map($request)->to(Airport::class);
        $airport->save();

        return response()->json($airport);
    }
}

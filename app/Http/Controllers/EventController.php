<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::all();

        return response()->json(['data' => $events], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Event::create($request->all());

        return response()->json(['msg' => 'Event data created successfully.'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $event = Event::findOrFail($id);

        return response()->json(['data' => $event], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);
        $event->slug = null;
        $event->update($request->all());

        return response()->json(['msg' => 'Event data updated successfully.'], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $event = Event::findOrFail($id);
        $event->delete();

        return response()->json(['msg' => 'Event data deleted successfully.'], 200);
    }
}

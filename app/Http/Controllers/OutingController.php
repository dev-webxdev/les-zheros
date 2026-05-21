<?php

namespace App\Http\Controllers;

use App\Models\Outing;
use App\Models\OutingVote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OutingController extends Controller
{
    public function index(): View
    {
        return view('pages.sorties', [
            'outings' => Outing::query()
                ->with('votes.user')
                ->where('is_published', true)
                ->latest('close_at')
                ->latest()
                ->get(),
        ]);
    }

    public function vote(Request $request, Outing $outing): RedirectResponse
    {
        abort_if($outing->isClosed(), 422);
        abort_if($outing->confirmed_slot_id !== null, 422);

        $validated = $request->validate([
            'slot_id' => ['required', 'string', 'max:255'],
        ]);

        abort_unless($outing->hasSlot($validated['slot_id']), 404);

        OutingVote::updateOrCreate(
            ['outing_id' => $outing->id, 'user_id' => $request->user()->id],
            ['slot_id' => $validated['slot_id']]
        );

        return redirect()->route('sorties.index')->with('toast', [
            'title' => 'Vote enregistré',
            'text' => 'Ton inscription à la sortie est bien prise en compte.',
            'type' => 'success',
        ]);
    }

    public function cancel(Request $request, Outing $outing): RedirectResponse
    {
        abort_if($outing->confirmed_slot_id !== null, 422);

        OutingVote::query()
            ->where('outing_id', $outing->id)
            ->where('user_id', $request->user()->id)
            ->delete();

        return redirect()->route('sorties.index')->with('toast', [
            'title' => 'Vote annulé',
            'text' => 'Ton créneau a été libéré.',
            'type' => 'warning',
        ]);
    }
}

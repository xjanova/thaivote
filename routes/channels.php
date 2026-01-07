<?php

use App\Models\Election;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Public channels for election results - no authentication required
Broadcast::channel('election.{electionId}', function () {
    // Public channel - anyone can subscribe
    return true;
});

// Province-specific results channel
Broadcast::channel('election.{electionId}.province.{provinceId}', function () {
    return true;
});

// Admin channel for privileged updates
Broadcast::channel('election.{electionId}.admin', function ($user, $electionId) {
    return $user && $user->is_admin;
});

// User-specific channel for authenticated features
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

<?php

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

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Shared "something changed" pulse channel — any logged-in user may listen.
// No business data rides on it; pages still reload through their normal,
// permission-checked routes/controllers when they receive a pulse.
Broadcast::channel('erp.live', function ($user) {
    return (bool) $user;
});

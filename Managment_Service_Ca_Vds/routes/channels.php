<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('admin.{acc_id}', function ($user, $acc_id) {
    // Optionally validate access
    return true; // or add logic to restrict who can listen
});

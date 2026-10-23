<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('speedshop.queue.{warehouseId}', function ($user, $warehouseId) {
    $activeWarehouseId = $user->activeWarehouseId();
    if (! $activeWarehouseId) {
        return false;
    }

    return $user->isSpeedshop() && (int) $activeWarehouseId === (int) $warehouseId;
});

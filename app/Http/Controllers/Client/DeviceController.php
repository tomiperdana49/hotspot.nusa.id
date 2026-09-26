<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\HotspotUser;
use App\Models\Router;
use App\Services\OnlineDevices;
use Illuminate\Support\Facades\Auth;

class DeviceController extends Controller
{
    public function __construct(private readonly OnlineDevices $onlineDevices) {}

    public function index()
    {
        $clientId = Auth::guard('client')->user()->client_id;

        $users = HotspotUser::with('profile')
            ->where('client_id', $clientId)
            ->get()
            ->keyBy('username');

        $devices = $this->onlineDevices->forUsers($users, Router::where('client_id', $clientId)->get());

        return view('client.devices.index', compact('devices'));
    }
}

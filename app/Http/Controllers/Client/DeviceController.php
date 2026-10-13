<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\HotspotUser;
use App\Models\Router;
use App\Services\OnlineDevices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeviceController extends Controller
{
    public function __construct(private readonly OnlineDevices $onlineDevices) {}

    public function index(Request $request)
    {
        $clientId = Auth::guard('client')->user()->client_id;
        $routers = Router::where('client_id', $clientId)->orderBy('name')->get();
        $selectedRouter = $routers->firstWhere('id', (int) $request->input('router'));

        $users = HotspotUser::with('profile')
            ->where('client_id', $clientId)
            ->get()
            ->keyBy('username');

        $devices = $this->onlineDevices->forUsers($users, $selectedRouter ? collect([$selectedRouter]) : $routers);

        return view('client.devices.index', compact('devices', 'routers', 'selectedRouter'));
    }
}

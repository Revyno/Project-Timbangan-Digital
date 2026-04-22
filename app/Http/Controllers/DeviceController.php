<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DeviceController extends Controller
{
    public function index()
    {
        $devices = Device::withCount('penimbangans')->orderBy('device_name')->get();
        return view('devices.index', compact('devices'));
    }

    public function create()
    {
        return view('devices.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'device_code' => 'required|string|max:50|unique:devices',
            'device_name' => 'required|string|max:100',
        ]);

        $device = Device::create([
            'device_code'  => $validated['device_code'],
            'device_name'  => $validated['device_name'],
            'device_token' => Str::random(64),
            'is_active'    => true,
        ]);

        return redirect()->route('devices.index')
            ->with('success', "Device ditambahkan. Token: {$device->device_token}");
    }

    public function edit(Device $device)
    {
        return view('devices.edit', compact('device'));
    }

    public function update(Request $request, Device $device)
    {
        $validated = $request->validate([
            'device_name' => 'required|string|max:100',
            'is_active'   => 'boolean',
        ]);

        $device->update([
            'device_name' => $validated['device_name'],
            'is_active'   => $request->boolean('is_active'),
        ]);

        return redirect()->route('devices.index')
            ->with('success', 'Device berhasil diperbarui.');
    }

    public function destroy(Device $device)
    {
        $device->delete();
        return redirect()->route('devices.index')
            ->with('success', 'Device berhasil dihapus.');
    }

    public function regenerateToken(Device $device)
    {
        $token = Str::random(64);
        $device->update(['device_token' => $token]);
        return back()->with('success', "Token baru: {$token}");
    }
}

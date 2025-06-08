<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Guardian;
use Illuminate\Http\Request;

class GuardianController extends Controller
{
    public function index(Request $request) {
        return $request->user()->guardians;
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|regex:/^09[0-9]{9}$/',
        ]);
        $guardian = $request->user()->guardians()->create($validated);
        return response()->json($guardian, 201);
    }

    public function setDefault(Request $request, Guardian $guardian) {
        if ($guardian->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $request->user()->guardians()->update(['is_default' => false]);
        $guardian->update(['is_default' => true]);
        return response()->json(['message' => 'نگهبان پیش‌فرض با موفقیت تنظیم شد.']);
    }

    public function destroy(Request $request, Guardian $guardian) {
        if ($guardian->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $guardian->delete();
        return response()->json(null, 204);
    }
}
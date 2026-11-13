<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TechnicianGroup;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TechnicianGroupController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            'admin',
        ];
    }

    /**
     * Display a listing of technician groups.
     */
    public function index(Request $request): View
    {
        $query = TechnicianGroup::withCount(['users', 'tickets']);

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('telegram_chat_id', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $groups = $query->latest()->paginate(10)->withQueryString();

        // Calculate summary cards
        $totalGroups = TechnicianGroup::count();
        $activeGroups = TechnicianGroup::where('is_active', true)->count();
        $connectedTelegram = TechnicianGroup::whereNotNull('telegram_chat_id')->where('telegram_chat_id', '!=', '')->count();
        $totalTechnicians = User::where('role', 'technician')->whereNotNull('technician_group_id')->count();

        // All active technicians for assignment modal
        $unassignedTechnicians = User::where('role', 'technician')
            ->where('is_active', true)
            ->whereNull('technician_group_id')
            ->orderBy('name')
            ->get();

        return view('technician-groups.index', compact(
            'groups',
            'totalGroups',
            'activeGroups',
            'connectedTelegram',
            'totalTechnicians',
            'unassignedTechnicians'
        ));
    }

    /**
     * Store a newly created technician group.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'code'              => ['required', 'string', 'max:100', 'unique:technician_groups,code'],
            'telegram_chat_id'  => ['nullable', 'string', 'max:100'],
            'telegram_topic_id' => ['nullable', 'string', 'max:100'],
            'description'       => ['nullable', 'string', 'max:1000'],
            'is_active'         => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['code'] = strtoupper(trim($validated['code']));

        TechnicianGroup::create($validated);

        return back()->with('success', "Grup Teknisi '{$validated['name']}' berhasil ditambahkan.");
    }

    /**
     * Update the specified technician group.
     */
    public function update(Request $request, int|string $id): RedirectResponse
    {
        $group = TechnicianGroup::findOrFail($id);

        $validated = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'code'              => ['required', 'string', 'max:100', Rule::unique('technician_groups', 'code')->ignore($group->id)],
            'telegram_chat_id'  => ['nullable', 'string', 'max:100'],
            'telegram_topic_id' => ['nullable', 'string', 'max:100'],
            'description'       => ['nullable', 'string', 'max:1000'],
            'is_active'         => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', false);
        $validated['code'] = strtoupper(trim($validated['code']));

        $group->update($validated);

        return back()->with('success', "Grup Teknisi '{$group->name}' berhasil diperbarui.");
    }

    /**
     * Remove the specified technician group.
     */
    public function destroy(int|string $id): RedirectResponse
    {
        $group = TechnicianGroup::withCount(['users', 'tickets'])->findOrFail($id);

        if ($group->tickets_count > 0) {
            return back()->with('error', "Grup '{$group->name}' tidak dapat dihapus karena sudah memiliki {$group->tickets_count} riwayat tiket.");
        }

        // Unbind any users before deleting
        User::where('technician_group_id', $group->id)->update(['technician_group_id' => null]);

        $groupName = $group->name;
        $group->delete();

        return back()->with('success', "Grup Teknisi '{$groupName}' berhasil dihapus.");
    }

    /**
     * Test sending a sample Telegram notification to this group.
     */
    public function testTelegram(int|string $id, TelegramService $telegramService): JsonResponse
    {
        $group = TechnicianGroup::findOrFail($id);

        if (empty($group->telegram_chat_id)) {
            return response()->json([
                'success' => false,
                'message' => "Telegram Chat ID untuk grup '{$group->name}' masih kosong.",
            ], 422);
        }

        $result = $telegramService->sendTestMessage(
            (string) $group->telegram_chat_id,
            $group->telegram_topic_id ? (string) $group->telegram_topic_id : null,
            "{$group->name} ({$group->code})"
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }
}


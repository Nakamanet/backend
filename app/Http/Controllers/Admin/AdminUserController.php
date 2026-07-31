<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    private function boolLiteral($value): string
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
    }

    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->when($request->search, fn($q) => $q->where('username', 'ilike', "%{$request->search}%")
                ->orWhere('email', 'ilike', "%{$request->search}%"))
            ->when($request->role, fn($q) => $q->where('role', $request->role))
            ->orderByDesc('id')
            ->paginate(20);

        // Admin-only route (see the `admin` middleware): the moderation table needs
        // the fields User hides from every public payload.
        $users->getCollection()->makeVisible(User::PRIVATE_FIELDS);

        return response()->json($users);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(User::findOrFail($id)->withPrivateFields());
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'username' => ['sometimes', 'string', 'max:50', Rule::unique('Users', 'username')->ignore($user->id)],
            'email' => ['sometimes', 'email', 'max:100', Rule::unique('Users', 'email')->ignore($user->id)],
            'role' => ['sometimes', Rule::in(['user', 'moderator', 'admin'])],
            'is_admin' => ['sometimes', 'boolean'],
            'is_moderator' => ['sometimes', 'boolean'],
        ]);

        foreach (['is_admin', 'is_moderator'] as $boolField) {
            if (array_key_exists($boolField, $validated)) {
                $validated[$boolField] = $this->boolLiteral($validated[$boolField]);
            }
        }

        $user->update($validated);

        return response()->json($user->withPrivateFields());
    }

    public function destroy(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update(['is_deleted' => $this->boolLiteral(true)]);

        return response()->json(['message' => 'User soft-deleted']);
    }

    public function restore(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update(['is_deleted' => $this->boolLiteral(false)]);

        return response()->json(['message' => 'User restored']);
    }
}

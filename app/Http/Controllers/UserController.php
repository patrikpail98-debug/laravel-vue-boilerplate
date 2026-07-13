<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Role;
use App\Models\User;
use App\Services\CacheKeyService;
use App\Services\ReservationPdfService;
use App\Traits\CacheTrait;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    use JsonResponseTrait, CacheTrait;

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getAuthUser(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles.permissions');

        return $this->successResponse([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'street' => $user->street,
            'city' => $user->city,
            'postcode' => $user->postcode,
            'ico' => $user->ico,
            'email_verified_at' => $user->email_verified_at,
            'two_factor_enabled' => $user->two_factor_enabled,
            'two_factor_method' => $user->two_factor_method,
            'roles' => $user->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'display_name' => $role->display_name
                ];
            }),
            'permissions' => $user->getAllPermissions()
        ]);
    }

    /**
     * Reservations belonging to the authenticated user (shown on their /user profile).
     */
    public function myReservations(Request $request): JsonResponse
    {
        $reservations = Reservation::query()
            ->where('user_id', $request->user()->id)
            ->with('playground.area')
            ->orderByDesc('created_at')
            ->get();

        return $this->successResponse($reservations);
    }

    /**
     * Downloadable payment summary PDF for the authenticated user's own,
     * already-paid (approved) reservation - mirrors the admin equivalent in
     * Admin\ReservationController but scoped to the requesting user.
     */
    public function paymentSummaryPdf(Request $request, Reservation $reservation): JsonResponse|Response
    {
        if ($reservation->user_id !== $request->user()->id) {
            return $this->errorResponse(['message' => 'Nemáte oprávnenie k tejto rezervácii.'], 403);
        }

        if ($reservation->status !== Reservation::STATUS_APPROVED) {
            return $this->errorResponse(['message' => 'Súhrn platby je dostupný len pre schválené (zaplatené) rezervácie.'], 422);
        }

        $pdf = ReservationPdfService::instance()->generatePaymentSummary($reservation);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="suhrn-platby-' . $reservation->variable_symbol . '.pdf"',
        ]);
    }

    public function index(): JsonResponse
    {
        $users = User::with('roles')->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles,
                'is_blocked' => $user->is_blocked,
                'updated_at' => $user->updated_at,
                'permissions' => $user->getAllPermissions()
            ];
        });

        $roles = Role::all();

        return response()->json([
            'users' => $users,
            'roles' => $roles
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id'
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password'])
        ]);

        $user->syncRoles($validated['roles']);

        return response()->json($user, 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        if ($user->id === auth()->id()) {
            return $this->errorResponse(['message' => 'Cannot update your own account.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_blocked' => 'required|boolean',
            'email' => [
                'required',
                'string',
                'email',
                Rule::unique('users')->ignore($user->id)
            ],
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id'
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_blocked' => $validated['is_blocked']
        ]);

        if ($validated['is_blocked'] === false) {
            $cacheKey = CacheKeyService::instance()->getFailedAttemptsKey($validated['email']);
            $this->cacheForget($cacheKey);
        }

        $user->syncRoles($validated['roles']);

        return response()->json($user);
    }

    public function destroy(User $user): JsonResponse
    {
        // Prevent admin from deleting themselves
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Cannot delete your own account'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed'
        ]);

        $user->update([
            'password' => Hash::make($validated['password'])
        ]);

        return response()->json(['message' => 'Password updated successfully']);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function updatePassword(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => [
                'required',
                'string',
                // Custom rule to check if the current password is correct
                function ($attribute, $value, $fail) use ($user) {
                    if (!Hash::check($value, $user->password)) {
                        $fail('Current password is incorrect.');
                    }
                },
            ],
            'password' => [
                'required',
                'string',
                'confirmed', // This requires a 'password_confirmation' field
                Password::min(8)->mixedCase()->numbers()->symbols(), // Enforce strong password
            ],
        ]);

        // Update the user's password
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return $this->successResponse(['message' => 'Your password has been updated.']);
    }

    /**
     * Updates the authenticated user's own contact/billing details. An email
     * change resets verification (same mechanism as registration) since the
     * new address hasn't been proven to belong to the user yet.
     */
    public function updateContactDetails(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:30',
            'street' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:120',
            'postcode' => 'nullable|string|max:20',
            'ico' => 'nullable|string|max:20',
        ]);

        $emailChanged = $validated['email'] !== $user->email;

        $user->fill($validated);

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        return $this->successResponse($user->fresh());
    }

    /**
     * Self-service account deletion: anonymizes the user's own data and
     * revokes access, but never touches their existing reservations - those
     * keep their own customer_name/email/phone/etc. snapshots taken at
     * booking time, independent of this User row.
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validate([
            'current_password' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($user) {
                    if (!Hash::check($value, $user->password)) {
                        $fail('Zadané heslo nie je správne.');
                    }
                },
            ],
        ]);

        $user->roles()->detach();
        $user->tokens()->delete();

        $user->forceFill([
            'name' => 'Vymazaný účet',
            'email' => 'deleted-' . $user->id . '-' . Str::random(8) . '@deleted.invalid',
            'phone' => null,
            'street' => null,
            'city' => null,
            'postcode' => null,
            'ico' => null,
            'password' => Hash::make(Str::random(40)),
            'two_factor_enabled' => false,
            'two_factor_method' => null,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_email_code' => null,
            'two_factor_email_expires_at' => null,
            'is_deleted' => true,
            'deleted_at' => now(),
        ])->save();

        return $this->successResponse(['message' => 'Váš účet bol vymazaný.']);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use App\Services\SettingsService;
use App\Traits\JsonResponseTrait;
use Carbon\Carbon;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FA\Google2FA;
use Random\RandomException;

class AuthController extends Controller
{
    use JsonResponseTrait;

    /**
     * Minutes a two-factor challenge (issued after a correct password) stays
     * redeemable for.
     */
    private const TWO_FACTOR_CHALLENGE_MINUTES = 10;

    public function register(Request $request): JsonResponse
    {
        if (SettingsService::instance()->isRegistrationEnabled() === false) {
            return $this->errorResponse(['message' => 'Registration is disabled.'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users',
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        $user = User::query()
            ->create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

        // Assign default 'user' role
        $userRole = Role::query()
            ->where('name', 'user')
            ->first();
        $user->roles()->attach($userRole);

        $user->sendEmailVerificationNotification();

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * @throws RandomException
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::query()
            ->where('email', $request->email)
            ->first();

        // A deleted account's password was randomized to something unguessable
        // at deletion time (see UserController::deleteAccount), so Auth::attempt
        // could never succeed against it anyway - short-circuit before paying
        // the bcrypt cost, but keep the response identical to a normal wrong-
        // password failure so this can't be used to detect a deleted account.
        if ($user?->is_deleted) {
            return $this->errorResponse(['message' => 'Incorrect login details.'], 401);
        }

        // Throttled per email+IP pair rather than a global/permanent block, so
        // one person spamming wrong passwords against someone else's known
        // email can no longer lock that account indefinitely - it only holds
        // up their own IP for a few minutes.
        $throttleKey = 'login:' . Str::lower($request->email) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return $this->errorResponse([
                'message' => 'Priveľa neúspešných pokusov o prihlásenie. Skúste to prosím znova o pár minút.'
            ], 429);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            RateLimiter::hit($throttleKey, 900); // 15 minute lockout window, self-clearing
            return $this->errorResponse(['message' => 'Incorrect login details.'], 401);
        }

        RateLimiter::clear($throttleKey);

        if ($user->isBlocked()) {
            return $this->errorResponse([
                'message' => 'Account is blocked. Please contact support.'
            ], 403);
        }

        if (!$user->hasVerifiedEmail()) {
            return $this->errorResponse(['message' => 'not-verified'], 403);
        }

        if ($user->two_factor_enabled) {
            // If 2FA is enabled, generate and send code if method is email
            if ($user->two_factor_method === 'email') {
                $user->forceFill([
                    'two_factor_email_code' => random_int(100000, 999999),
                    'two_factor_email_expires_at' => now()->addMinutes(10),
                ])->save();
                $user->notify(new TwoFactorCodeNotification());
            }

            $method = $user->two_factor_method;

            // Log out the user for now and issue a one-time challenge token in
            // place of the password check - loginWithTwoFactor() trusts this
            // token (not a client-supplied user_id) as proof a correct
            // password was already provided.
            Auth::logout();

            $challengeToken = Str::random(64);
            Cache::put('two_factor_challenge:' . $challengeToken, $user->id, now()->addMinutes(self::TWO_FACTOR_CHALLENGE_MINUTES));

            return $this->successResponse([
                'two_factor_required' => true,
                'challenge_token' => $challengeToken,
                'method' => $method
            ]);
        }

        //update updated_at timestamp to log last login
        $user->touch();

        $tokenResult = $user->createToken('auth_token', expiresAt: Carbon::now()->addDays(7));

        return $this->successResponse([
            'user' => $user,
            'access_token' => $tokenResult->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::now()->addDays(7)
        ]);
    }

    /**
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws SecretKeyTooShortException
     * @throws InvalidCharactersException
     */
    public function loginWithTwoFactor(Request $request): JsonResponse
    {
        $request->validate([
            'challenge_token' => 'required|string',
            'code' => 'required|string',
        ]);

        // Bounded per challenge token (itself only obtainable after a correct
        // password) rather than per user_id, so this can no longer be brute
        // forced by anyone who merely guesses/enumerates an account id.
        $attemptsKey = 'two-factor-attempt:' . $request->challenge_token;

        if (RateLimiter::tooManyAttempts($attemptsKey, 5)) {
            return $this->errorResponse(['message' => 'Priveľa neúspešných pokusov. Prihláste sa prosím znova.'], 429);
        }

        $userId = Cache::get('two_factor_challenge:' . $request->challenge_token);

        if (!$userId) {
            return $this->errorResponse(['message' => 'Prihlasovací pokus vypršal. Prihláste sa prosím znova.'], 422);
        }

        $user = User::query()->find($userId);

        if (!$user) {
            return $this->errorResponse(['message' => 'Invalid or expired code.'], 422);
        }

        $codeIsValid = false;
        if ($user->two_factor_method === 'app') {
            $google2fa = new Google2FA();
            $codeIsValid = $google2fa->verifyKey($user->two_factor_secret, $request->code);
        } elseif ($user->two_factor_method === 'email') {
            $codeIsValid = $request->code == $user->two_factor_email_code && now()->lessThan($user->two_factor_email_expires_at);
        }

        // A recovery code is a valid alternative to the normal TOTP/email code
        // regardless of method, for a user who has lost access to their
        // authenticator device.
        $usedRecoveryCode = false;
        if (!$codeIsValid && $user->two_factor_recovery_codes?->contains($request->code)) {
            $codeIsValid = true;
            $usedRecoveryCode = true;
        }

        if (!$codeIsValid) {
            RateLimiter::hit($attemptsKey, 300);
            return $this->errorResponse(['message' => 'Invalid or expired code.'], 422);
        }

        RateLimiter::clear($attemptsKey);
        Cache::forget('two_factor_challenge:' . $request->challenge_token);

        $updates = [
            'two_factor_email_code' => null,
            'two_factor_email_expires_at' => null,
        ];

        if ($usedRecoveryCode) {
            // Recovery codes are single-use - remove the redeemed one.
            $updates['two_factor_recovery_codes'] = $user->two_factor_recovery_codes
                ->reject(fn($recoveryCode) => $recoveryCode === $request->code)
                ->values();
        }

        $user->forceFill($updates)->save();

        // Log the user in and create token
        Auth::login($user);
        $tokenResult = $user->createToken('auth_token', expiresAt: Carbon::now()->addDays(7));

        $user->touch();

        return $this->successResponse([
            'user' => $user,
            'access_token' => $tokenResult->plainTextToken,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();
        return $this->successResponse(['message' => 'Logged out']);
    }

    public function user(Request $request): JsonResponse
    {
        return $this->successResponse($request->user()->load('roles'));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function verify(Request $request): JsonResponse
    {
        $user = User::query()->find($request->route('id'));

        // The 'signed' middleware already proved the link is authentic and
        // unexpired; guard against a user deleted between issuance and click
        // (find() would return null and the sha1() below would fatal).
        if (!$user) {
            return $this->errorResponse(['message' => 'Invalid token'], 403);
        }

        if (!hash_equals((string)$request->route('hash'), sha1($user->getEmailForVerification()))) {
            return $this->errorResponse(['message' => 'Invalid token'], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->successResponse(['message' => 'Email is already verified.']);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return $this->successResponse(['message' => 'Email successfully verified!']);
    }

    /**
     * Deliberately reveals nothing about whether the email exists or is
     * already verified - same generic message either way (mirrors
     * ForgotPasswordController::sendResetLinkEmail's anti-enumeration stance).
     */
    public function resend(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::query()->where('email', $request->email)->first();

        if ($user && !$user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return $this->successResponse(['message' => 'Ak s touto e-mailovou adresou existuje neoverený účet, poslali sme naň overovací odkaz.']);
    }
}

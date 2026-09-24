<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class TwoFactorController extends Controller
{
    /**
     * Show 2FA setup page.
     */
    public function setup()
    {
        $user = Auth::user();

        return view('settings.two-factor', compact('user'));
    }

    /**
     * Enable 2FA — generate secret and show QR.
     */
    public function enable(Request $request)
    {
        $user = Auth::user();

        // Generate a random base32 secret (16 chars)
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }

        // Store temporarily in session until verified
        session(['2fa_temp_secret' => $secret]);

        // Build otpauth URI for QR code
        $issuer = urlencode(config('app.name', 'OpenBooks SG'));
        $label = urlencode($user->email);
        $otpauthUrl = "otpauth://totp/{$issuer}:{$label}?secret={$secret}&issuer={$issuer}&digits=6&period=30";

        return response()->json([
            'secret' => $secret,
            'otpauth_url' => $otpauthUrl,
        ]);
    }

    /**
     * Verify the TOTP code and activate 2FA.
     */
    public function verify(Request $request)
    {
        $request->validate(['code' => 'required|string|size:6']);

        $secret = session('2fa_temp_secret');
        if (! $secret) {
            return back()->with('error', '2FA setup session expired. Please try again.');
        }

        $code = $request->input('code');

        // Verify TOTP code
        if (! $this->verifyTotp($secret, $code)) {
            return back()->with('error', 'Invalid verification code. Please try again.');
        }

        // Generate recovery codes
        $recoveryCodes = [];
        for ($i = 0; $i < 8; $i++) {
            $recoveryCodes[] = strtoupper(bin2hex(random_bytes(4))).'-'.strtoupper(bin2hex(random_bytes(4)));
        }

        $user = Auth::user();
        $user->two_factor_secret = encrypt($secret);
        $user->two_factor_enabled = true;
        $user->two_factor_recovery_codes = encrypt(json_encode($recoveryCodes));
        $user->save();

        session()->forget('2fa_temp_secret');

        return back()->with('success', '2FA enabled successfully.')->with('recovery_codes', $recoveryCodes);
    }

    /**
     * Disable 2FA.
     */
    public function disable(Request $request)
    {
        $request->validate(['password' => 'required|string']);

        $user = Auth::user();

        if (! Hash::check($request->password, $user->password)) {
            return back()->with('error', 'Incorrect password.');
        }

        $user->two_factor_secret = null;
        $user->two_factor_enabled = false;
        $user->two_factor_recovery_codes = null;
        $user->save();

        return back()->with('success', '2FA has been disabled.');
    }

    /**
     * Show 2FA challenge page during login.
     */
    public function challenge()
    {
        if (! session('2fa_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    /**
     * Verify 2FA code during login.
     */
    public function challengeVerify(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $userId = session('2fa_user_id');
        if (! $userId) {
            return redirect()->route('login')->with('error', 'Session expired. Please login again.');
        }

        $user = User::find($userId);
        if (! $user) {
            return redirect()->route('login');
        }

        $code = $request->input('code');
        $secret = decrypt($user->two_factor_secret);

        // Try TOTP verification
        if ($this->verifyTotp($secret, $code)) {
            session()->forget('2fa_user_id');
            Auth::login($user);
            session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        // Try recovery code
        $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true) ?? [];
        if (in_array($code, $recoveryCodes)) {
            // Remove used recovery code
            $recoveryCodes = array_values(array_diff($recoveryCodes, [$code]));
            $user->two_factor_recovery_codes = encrypt(json_encode($recoveryCodes));
            $user->save();

            session()->forget('2fa_user_id');
            Auth::login($user);
            session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()->with('error', 'Invalid code. Please try again.');
    }

    /**
     * TOTP verification (RFC 6238) — pure PHP, no external package.
     */
    private function verifyTotp(string $secret, string $code, int $window = 1): bool
    {
        $time = floor(time() / 30);

        for ($i = -$window; $i <= $window; $i++) {
            $timeSlice = $time + $i;
            $expected = $this->generateTotp($secret, $timeSlice);
            if (hash_equals($expected, $code)) {
                return true;
            }
        }

        return false;
    }

    private function generateTotp(string $secret, int $timeSlice): string
    {
        // Decode base32 secret
        $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binaryString = '';
        $secret = strtoupper($secret);

        for ($i = 0; $i < strlen($secret); $i++) {
            $pos = strpos($base32Chars, $secret[$i]);
            if ($pos === false) {
                continue;
            }
            $binaryString .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $binaryKey = '';
        for ($i = 0; $i + 7 < strlen($binaryString); $i += 8) {
            $binaryKey .= chr(bindec(substr($binaryString, $i, 8)));
        }

        // Pack time into 8 bytes
        $timePacked = pack('N*', 0).pack('N*', $timeSlice);

        // HMAC-SHA1
        $hash = hash_hmac('sha1', $timePacked, $binaryKey, true);

        // Dynamic truncation
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $otp = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % 1000000;

        return str_pad((string) $otp, 6, '0', STR_PAD_LEFT);
    }
}

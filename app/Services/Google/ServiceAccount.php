<?php

namespace App\Services\Google;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * The shop's own Google credentials, and an access token made from them.
 *
 * A service account rather than a sign-in flow, because nobody is sitting at
 * the admin when the numbers are fetched, and a refresh token that expires
 * silently is a dashboard that goes blank in three months with no explanation.
 *
 * No SDK. google/apiclient is some ten megabytes and brings half of Guzzle's
 * ecosystem with it, which on a shared host with an eight-megabyte upload
 * limit and a composer that times out is a real cost. All that is needed is a
 * JWT signed with the key Google already gave the shop, which PHP's own
 * openssl does in four lines.
 */
class ServiceAccount
{
    public const ANALYTICS = 'https://www.googleapis.com/auth/analytics.readonly';

    public const SEARCH_CONSOLE = 'https://www.googleapis.com/auth/webmasters.readonly';

    /** @param array{client_email?: string, private_key?: string} $key */
    public function __construct(private array $key)
    {
    }

    /** From whatever the shop pasted into Settings, or null if it pasted nothing. */
    public static function fromSettings(): ?self
    {
        $json = trim((string) \App\Models\Setting::get('google_service_account'));

        if ($json === '') {
            return null;
        }

        $key = json_decode($json, true);

        if (! is_array($key) || blank($key['client_email'] ?? null) || blank($key['private_key'] ?? null)) {
            return null;
        }

        return new self($key);
    }

    public function email(): string
    {
        return (string) $this->key['client_email'];
    }

    /**
     * An access token for these scopes, good for an hour.
     *
     * Cached for fifty minutes rather than sixty: a token that expires between
     * being read from the cache and being used is a request that fails for no
     * reason anybody can see.
     */
    public function token(string ...$scopes): ?string
    {
        $scope = implode(' ', $scopes);

        return Cache::remember(
            'google:token:'.md5($this->email().'|'.$scope),
            now()->addMinutes(50),
            function () use ($scope): ?string {
                $assertion = $this->assertion($scope);

                if ($assertion === null) {
                    return null;
                }

                $response = Http::asForm()
                    ->timeout(15)
                    ->post('https://oauth2.googleapis.com/token', [
                        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                        'assertion'  => $assertion,
                    ]);

                return $response->successful() ? ($response->json('access_token') ?: null) : null;
            },
        );
    }

    /** The signed claim that says who we are and what we are asking for. */
    private function assertion(string $scope): ?string
    {
        $now = time();

        $header = $this->segment(['alg' => 'RS256', 'typ' => 'JWT']);
        $claims = $this->segment([
            'iss'   => $this->email(),
            'scope' => $scope,
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
        ]);

        $signature = '';

        if (! @openssl_sign("{$header}.{$claims}", $signature, $this->key['private_key'], OPENSSL_ALGO_SHA256)) {
            return null;
        }

        return "{$header}.{$claims}.".$this->base64($signature);
    }

    private function segment(array $data): string
    {
        return $this->base64(json_encode($data, JSON_UNESCAPED_SLASHES));
    }

    /** Base64, in the spelling a URL allows. */
    private function base64(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}

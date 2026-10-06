<?php

namespace App\Filament\Support;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Initials, drawn here.
 *
 * Filament's default avatar asks ui-avatars.com for a picture on every admin
 * page, which means the shop's staff names leave the server to a third party
 * and the admin stops looking right the day that service is slow. This draws
 * the same thing as an inline SVG: no request, no dependency, and the shop's
 * own gold behind the letters.
 */
class InitialsAvatarProvider implements AvatarProvider
{
    public function get(Model $record): string
    {
        // An uploaded photograph wins; this is only the fallback.
        $avatar = $record->getAttribute('avatar');

        if (is_string($avatar) && $avatar !== '') {
            return Str::startsWith($avatar, ['http://', 'https://', 'data:'])
                ? $avatar
                : Storage::disk('public')->url($avatar);
        }

        return $this->initialsSvg($this->initials((string) ($record->getAttribute('name') ?? '')));
    }

    private function initials(string $name): string
    {
        $words = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($words === []) {
            return '?';
        }

        $first = mb_strtoupper(mb_substr($words[0], 0, 1));

        if (count($words) === 1) {
            return $first;
        }

        return $first . mb_strtoupper(mb_substr((string) end($words), 0, 1));
    }

    private function initialsSvg(string $initials): string
    {
        $text = htmlspecialchars($initials, ENT_QUOTES | ENT_XML1, 'UTF-8');

        $svg = <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 96 96" width="96" height="96">
          <rect width="96" height="96" rx="48" fill="#a8781f"/>
          <text x="48" y="49" fill="#fffdf7" font-family="Georgia, 'Times New Roman', serif"
                font-size="38" font-weight="500" letter-spacing="1"
                text-anchor="middle" dominant-baseline="central">{$text}</text>
        </svg>
        SVG;

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}

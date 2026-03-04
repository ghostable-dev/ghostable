<?php

declare(strict_types=1);

namespace App\Core\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class RedirectDesktopDownload
{
    public function __invoke(Request $request): RedirectResponse
    {
        $downloadUrl = trim((string) config('desktop-updates.channels.stable.download_url'));

        if ($downloadUrl === '') {
            abort(404, 'Desktop download URL is not configured.');
        }

        return redirect()->away($downloadUrl);
    }
}

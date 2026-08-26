<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\RichPreview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class RichPreviewController extends Controller
{
    public function show(RichPreview $richPreview): Response
    {
        abort_unless($richPreview->is_active, 404);

        return response()
            ->view('rich-previews.show', [
                'richPreview' => $richPreview,
            ]);
    }

    public function go(RichPreview $richPreview, Request $request): RedirectResponse
    {
        abort_unless($richPreview->is_active, 404);

        $richPreview->forceFill([
            'click_count' => $richPreview->click_count + 1,
            'last_clicked_at' => now(),
        ])->save();

        return redirect()->away($richPreview->destination_url, 302);
    }
}

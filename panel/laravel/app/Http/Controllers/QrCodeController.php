<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ShortLink;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class QrCodeController extends Controller
{
    public function __invoke(Request $request, ShortLink $link): Response
    {
        abort_unless((int) $link->user_id === (int) $request->user()->id, 404);
        abort_unless((string) $link->status === 'active' && (string) $link->shlink_short_url !== '', 404);

        $result = (new Builder(
            writer: new SvgWriter,
            data: (string) $link->shlink_short_url,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 800,
            margin: 24,
        ))->build();

        return response($result->getString(), 200, [
            'Content-Type' => $result->getMimeType(),
            'Cache-Control' => 'private, max-age=300',
            'Content-Disposition' => 'inline; filename="melink-'.($link->shlink_short_code ?: $link->id).'.svg"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

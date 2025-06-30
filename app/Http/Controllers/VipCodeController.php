<?php

namespace App\Http\Controllers;

use App\Models\VipCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class VipCodeController extends Controller
{
    /**
     * Display a printable version of a VIP code QR code
     */
    public function printQRCode(Request $request): View
    {
        $vipCode = VipCode::query()->where('code', $request->input('code'))->firstOrFail();
        $templateUrl = asset('images/printer_template.png');

        // The SVG path will be passed directly to the view
        $svgPath = $request->input('svg_path');

        abort_if(! $svgPath || ! Storage::disk('public')->exists($svgPath), 404, 'QR code image not found');

        $qrUrl = asset('storage/'.$svgPath);

        return view('vip-code.print', [
            'code' => $vipCode->code,
            'qrUrl' => $qrUrl,
            'templateUrl' => $templateUrl,
        ]);
    }
}

<?php

namespace App\Actions\QrCode;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use DOMDocument;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Storage;

class GenerateQrCodeWithLogo
{
    use AsAction;

    /**
     * Generate a QR code with PRIMA logo for a given URL
     *
     * @param  string  $url  The URL to encode in the QR code
     * @param  string  $displayText  The text to display under the PRIMA logo (QR code ID or URL key)
     * @return array Array containing image data and file paths
     */
    public function handle(string $url, string $displayText): array
    {
        // Generate QR code for display (PNG)
        $qrDisplayOptions = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_H,
            'scale' => 5,
            'outputBase64' => true,
        ]);
        $qrCodeImage = (new QRCode($qrDisplayOptions))->render($url);

        // Generate branded SVG for download and printing
        $logoText = 'PRIMA';

        $qrCustomSvgOptions = new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel' => QRCode::ECC_H,
            'scale' => 20,
            'addLogoSpace' => true,
            'logoSpaceWidth' => 17,
            'logoSpaceHeight' => 11,
            'outputBase64' => false,
            'svgAddXmlHeader' => true,
        ]);

        $rawSvgQrCode = (new QRCode($qrCustomSvgOptions))->render($url);
        $brandedSvg = $this->addBranding($rawSvgQrCode, $logoText, $displayText, 'Inter, Arial, sans-serif', $qrCustomSvgOptions);

        // Generate a unique filename
        $svgFileName = 'bulk-qr-'.Str::slug($displayText).'-'.Str::random(8).'.svg';
        $svgPath = 'qrcodes/'.$svgFileName;
        $svgUrl = asset('storage/'.$svgPath);

        // Store the SVG in storage for printing
        Storage::disk('public')->put($svgPath, $brandedSvg);

        return [
            'image' => $qrCodeImage, // Base64 PNG for display
            'downloadURL' => 'data:image/svg+xml;base64,'.base64_encode($brandedSvg),
            'storedSvgUrl' => $svgUrl,
            'svgPath' => $svgPath,
        ];
    }

    /**
     * Add Prima branding to the SVG QR code
     */
    private function addBranding(
        string $rawSvgQrCode,
        string $logoText,
        string $displayText,
        string $fontFamily,
        QROptions $qrCustomSvgOptions
    ): string {
        $dom = new DOMDocument;
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        if (! @$dom->loadXML($rawSvgQrCode)) {
            return $rawSvgQrCode; // Return original SVG if loading fails
        }

        $svgRoot = $dom->documentElement;

        $viewBoxValues = explode(' ', $svgRoot->getAttribute('viewBox'));
        $vbX = (float) ($viewBoxValues[0] ?? 0);
        $vbY = (float) ($viewBoxValues[1] ?? 0);
        $vbWidth = (float) ($viewBoxValues[2] ?? 200);
        $vbHeight = (float) ($viewBoxValues[3] ?? 200);

        // Add PRIMA text in the center
        $textPrima = $dom->createElement('text', $logoText);
        $textPrima->setAttribute('x', (string) ($vbWidth / 2));
        $textPrima->setAttribute('y', (string) ($vbHeight / 2));
        $textPrima->setAttribute('font-family', $fontFamily);
        $textPrima->setAttribute('font-weight', 'bold');
        $primaFontSize = $vbHeight * ($qrCustomSvgOptions->logoSpaceHeight / 100) * 0.5;
        $textPrima->setAttribute('font-size', (string) max(5, $primaFontSize));
        $textPrima->setAttribute('text-anchor', 'middle');
        $textPrima->setAttribute('dominant-baseline', 'central');
        $textPrima->setAttribute('fill', 'black');
        $svgRoot->appendChild($textPrima);

        // Add ID text underneath PRIMA text
        $keyFontSize = $primaFontSize * 0.2; // Smaller font for the key text (reduced from 0.4)
        $keyYPosition = ($vbHeight / 2) + ($primaFontSize * 1.4); // Position below PRIMA text (increased from 0.8)

        $textKey = $dom->createElement('text', $displayText);
        $textKey->setAttribute('x', (string) ($vbWidth / 2));
        $textKey->setAttribute('y', (string) $keyYPosition);
        $textKey->setAttribute('font-family', $fontFamily);
        $textKey->setAttribute('font-size', (string) max(3, $keyFontSize));
        $textKey->setAttribute('text-anchor', 'middle');
        $textKey->setAttribute('dominant-baseline', 'central');
        $textKey->setAttribute('fill', 'black');
        $svgRoot->appendChild($textKey);

        return $dom->saveXML($svgRoot) ?: $rawSvgQrCode;
    }
}

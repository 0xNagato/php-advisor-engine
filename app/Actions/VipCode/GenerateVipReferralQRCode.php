<?php

namespace App\Actions\VipCode;

use App\Models\VipCode;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use DOMDocument;
use Illuminate\Support\Str;
use Storage;

class GenerateVipReferralQRCode
{
    /**
     * Generate both standard and branded QR codes for a VIP code
     */
    public function execute(VipCode $vipCode): array
    {
        // Create the URL for the QR code
        $qrLink = config('app.primary_domain').'/';

        // Generate the base route with just the code
        $baseRoute = route('v.redirect', $vipCode->code, false);
        $qrLink .= ltrim($baseRoute, '/');

        // Add concierge ID as query parameter if present
        if (isset($vipCode->concierge_id)) {
            $qrLink .= '?cid='.$vipCode->concierge_id;
        }

        // Generate QR code for display (PNG)
        $qrDisplayOptions = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_H,
            'scale' => 5,
            'outputBase64' => true,
        ]);
        $qrCodeImage = (new QRCode($qrDisplayOptions))->render($qrLink);

        // Generate branded SVG for download and printing
        $logoText = 'PRIMA';
        $ribbonText = 'Book And Go.';
        $fontFamily = 'Inter, Arial, sans-serif';

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

        $rawSvgQrCode = (new QRCode($qrCustomSvgOptions))->render($qrLink);
        $brandedSvg = $this->addBranding($rawSvgQrCode, $logoText, $ribbonText, $fontFamily, $qrCustomSvgOptions);

        // We'll store the SVG in a temporary file if needed for printing
        $svgFileName = 'vip-qr-'.$vipCode->id.'-'.Str::random(8).'.svg';
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
        string $ribbonText,
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

        // Ribbon dimensions
        $ribbonStripHeight = $vbHeight * 0.18;
        $ribbonTextSize = $vbHeight * 0.1;
        $overlapAmount = $vbHeight * 0.04;

        // Y coordinates for the ribbon assembly
        $ribbonTopY = $vbHeight - $overlapAmount;
        $ribbonMidY = $ribbonTopY + ($ribbonStripHeight / 2);
        $ribbonBottomY = $ribbonTopY + $ribbonStripHeight;

        // Parameters for tails
        $foldWidth = $ribbonStripHeight * 0.7;
        $vNotchDepth = $foldWidth * 0.6;
        $tailVerticalOffset = $ribbonStripHeight * 0.12;

        // Y-coordinates for the tails
        $tailTopY = $ribbonTopY - $tailVerticalOffset;
        $tailMidY = $ribbonMidY - $tailVerticalOffset;
        $tailBottomY = $ribbonBottomY - $tailVerticalOffset;

        // X Coordinates for central rectangle
        $mainRectLeftX = $vbX;
        $mainRectRightX = $vbX + $vbWidth;

        // X Coordinates for left tail
        $leftTailOuterX = $mainRectLeftX - $foldWidth;
        $leftTailVCutX = $leftTailOuterX + $vNotchDepth;

        // X Coordinates for right tail
        $rightTailOuterX = $mainRectRightX + $foldWidth;
        $rightTailVCutX = $rightTailOuterX - $vNotchDepth;

        // Adjust overall SVG viewBox and dimensions
        $totalSvgWidth = $vbWidth + (2 * $foldWidth);
        $viewBoxOriginX = $vbX - $foldWidth;
        $viewBoxOriginY = min($vbY, $tailTopY);
        $viewBoxHeight = $ribbonBottomY - $viewBoxOriginY;

        $svgRoot->setAttribute('width', (string) ($totalSvgWidth * $qrCustomSvgOptions->scale));
        $svgRoot->setAttribute('height', (string) ($viewBoxHeight * $qrCustomSvgOptions->scale));
        $svgRoot->setAttribute('viewBox', sprintf('%f %f %f %f',
            $viewBoxOriginX, $viewBoxOriginY, $totalSvgWidth, $viewBoxHeight
        ));

        // Add central ribbon (rectangle)
        $rectBody = $dom->createElement('rect');
        $rectBody->setAttribute('x', (string) $mainRectLeftX);
        $rectBody->setAttribute('y', (string) $ribbonTopY);
        $rectBody->setAttribute('width', (string) $vbWidth);
        $rectBody->setAttribute('height', (string) $ribbonStripHeight);
        $rectBody->setAttribute('fill', 'black');
        $svgRoot->appendChild($rectBody);

        // Add left tail path
        $leftTailPathD = sprintf(
            'M %f,%f L %f,%f L %f,%f L %f,%f L %f,%f Z',
            $mainRectLeftX, $tailTopY,
            $leftTailOuterX, $tailTopY,
            $leftTailVCutX, $tailMidY,
            $leftTailOuterX, $tailBottomY,
            $mainRectLeftX, $tailBottomY
        );
        $leftTailPath = $dom->createElement('path');
        $leftTailPath->setAttribute('d', $leftTailPathD);
        $leftTailPath->setAttribute('fill', 'black');
        $svgRoot->appendChild($leftTailPath);

        // Add right tail path
        $rightTailPathD = sprintf(
            'M %f,%f L %f,%f L %f,%f L %f,%f L %f,%f Z',
            $mainRectRightX, $tailTopY,
            $rightTailOuterX, $tailTopY,
            $rightTailVCutX, $tailMidY,
            $rightTailOuterX, $tailBottomY,
            $mainRectRightX, $tailBottomY
        );
        $rightTailPath = $dom->createElement('path');
        $rightTailPath->setAttribute('d', $rightTailPathD);
        $rightTailPath->setAttribute('fill', 'black');
        $svgRoot->appendChild($rightTailPath);

        // Add ribbon text
        $textRibbon = $dom->createElement('text', $ribbonText);
        $textRibbon->setAttribute('x', (string) ($mainRectLeftX + ($vbWidth / 2)));
        $textRibbon->setAttribute('y', (string) $ribbonMidY);
        $textRibbon->setAttribute('font-family', $fontFamily);
        $textRibbon->setAttribute('font-size', (string) max(3, $ribbonTextSize));
        $textRibbon->setAttribute('text-anchor', 'middle');
        $textRibbon->setAttribute('dominant-baseline', 'central');
        $textRibbon->setAttribute('fill', 'white');
        $svgRoot->appendChild($textRibbon);

        return $dom->saveXML($svgRoot) ?: $rawSvgQrCode;
    }
}

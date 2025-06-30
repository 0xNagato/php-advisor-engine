<!DOCTYPE html>
<html>
<head>
    <title>Print QR Code - {{ $code }}</title>
    <style>
        @page {
            size: letter;
            margin: 0;
        }
        html, body {
            width: 8.5in;
            height: 11in;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        body {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        /* Force background printing */
        * {
            -webkit-print-color-adjust: exact !important;
            color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .template-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 8.5in;
            height: 11in;
            z-index: 1;
        }
        .qr-container {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .qr-code {
            width: 500px;
            height: 500px;
        }
    </style>
    <script>
        // Auto-print when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 1000);
        };
    </script>
</head>
<body>
    <!-- Template as content image instead of background -->
    <img src="{{ $templateUrl }}" class="template-image" alt="Prima Template">

    <div class="qr-container">
        <img src="{{ $qrUrl }}" class="qr-code" alt="QR Code for {{ $code }}">
    </div>
</body>
</html>

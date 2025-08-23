<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use DOMDocument;
use DOMXPath;

class SyncDesignerUpdates extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'site:sync-designer-updates {--force : Force update even if Blade files are newer} {--fix-tailwind : Fix Tailwind CSS class conflicts} {--detect-layout : Detect and sync layout changes} {--sync-layout : Sync header/footer from HTML to layout.blade.php} {--build : Build production assets after sync}';

    /**
     * The console command description.
     */
    protected $description = 'Sync HTML updates from designer to Blade templates';

    /**
     * File mapping between HTML files and Blade templates
     */
    protected array $fileMapping = [
        'index.html' => 'index.blade.php',
        'hotels.html' => 'hotels.blade.php',
        'restaurants.html' => 'restaurants.blade.php',
        'concierges.html' => 'concierges.blade.php',
        'influencers.html' => 'influencers.blade.php',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 Syncing designer updates to Blade templates...');

        $htmlDir = storage_path('app/Prima-web');
        $bladeDir = resource_path('views/site');
        $force = $this->option('force');

        if (!File::exists($htmlDir)) {
            $this->error("❌ HTML directory not found: {$htmlDir}");
            return 1;
        }

        $updatedFiles = [];
        $skippedFiles = [];

        foreach ($this->fileMapping as $htmlFile => $bladeFile) {
            $htmlPath = "{$htmlDir}/{$htmlFile}";
            $bladePath = "{$bladeDir}/{$bladeFile}";

            if (!File::exists($htmlPath)) {
                $this->warn("⚠️  HTML file not found: {$htmlFile}");
                continue;
            }

            if (!File::exists($bladePath)) {
                $this->warn("⚠️  Blade file not found: {$bladeFile}");
                continue;
            }

            // Check if HTML file is newer than Blade file
            $htmlModified = File::lastModified($htmlPath);
            $bladeModified = File::lastModified($bladePath);

            if (!$force && $htmlModified <= $bladeModified) {
                $skippedFiles[] = $htmlFile;
                $this->line("⏭️  Skipping {$htmlFile} (not newer than Blade template)");
                continue;
            }

            try {
                $this->info("🔄 Processing {$htmlFile}...");

                // Check for layout changes first
                $this->detectLayoutChanges($htmlPath, $htmlFile);

                $this->syncHtmlToBlade($htmlPath, $bladePath, $htmlFile);
                $updatedFiles[] = $htmlFile;
                $this->info("✅ Updated {$bladeFile}");
            } catch (\Exception $e) {
                $this->error("❌ Failed to update {$bladeFile}: " . $e->getMessage());
            }
        }

        // Sync CSS if it exists
        $this->syncCss();

        // Sync images
        $this->syncImages();

        // Sync layout if requested
        if ($this->option('sync-layout')) {
            $this->syncLayout();
        }

        // Run Tailwind conflict resolution once for all files
        if ($this->option('fix-tailwind')) {
            $this->newLine();
            $this->info('🔧 Running Tailwind conflict resolution for all templates...');
            $this->runTailwindTools();
        }

        // Build assets if requested and not already built by Tailwind tools
        if ($this->option('build') && !$this->option('fix-tailwind')) {
            $this->newLine();
            $this->info('🏗️  Rebuilding production assets...');
            $this->executeShellCommand(['npm', 'run', 'build'], '📦 Building optimized CSS and JS...');
        }

        // Summary
        $this->newLine();
        $this->info('📊 Sync Summary:');
        $this->line("✅ Updated files: " . count($updatedFiles));
        $this->line("⏭️  Skipped files: " . count($skippedFiles));

        if (!empty($updatedFiles)) {
            $this->line("Updated: " . implode(', ', $updatedFiles));
        }

        if (!empty($skippedFiles)) {
            $this->line("Skipped: " . implode(', ', $skippedFiles));
        }

        $this->newLine();
        $this->info('🎉 Designer sync completed!');

        return 0;
    }

    /**
     * Sync HTML content to Blade template
     */
    protected function syncHtmlToBlade(string $htmlPath, string $bladePath, string $htmlFile): void
    {
        $htmlContent = File::get($htmlPath);
        $currentBladeContent = File::get($bladePath);

        // Extract title from HTML
        $title = $this->extractTitle($htmlContent);

        // Extract body content (everything inside <body> except header/footer)
        $bodyContent = $this->extractBodyContent($htmlContent);

        // Extract lead form if it exists
        $leadForm = $this->extractLeadForm($htmlContent);

        // Convert HTML to Blade (includes asset paths, booking URLs, HTML links, etc.)
        $bodyContent = $this->convertHtmlToBlade($bodyContent);
        $leadForm = $this->convertHtmlToBlade($leadForm);

        // Note: Tailwind conflict resolution will run once at the end for efficiency

        // Preserve custom content sections from existing Blade file
        $preservedSections = $this->extractPreservedSections($bladePath);

        // Build new Blade template
        $newBladeContent = $this->buildBladeTemplate($title, $leadForm, $bodyContent, basename($bladePath));

        // Merge preserved sections back into new content
        $newBladeContent = $this->mergePreservedSections($newBladeContent, $preservedSections);

        File::put($bladePath, $newBladeContent);
    }

    /**
     * Extract title from HTML
     */
    protected function extractTitle(string $html): string
    {
        if (preg_match('/<title>(.*?)<\/title>/i', $html, $matches)) {
            return trim($matches[1]);
        }
        return 'PRIMA';
    }

    /**
     * Extract body content excluding header and footer
     */
    protected function extractBodyContent(string $html): string
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new DOMXPath($dom);

        // Find body content, excluding header and footer
        $body = $xpath->query('//body')->item(0);
        if (!$body) {
            return '';
        }

        $content = '';
        foreach ($body->childNodes as $node) {
            if ($node->nodeType === XML_ELEMENT_NODE) {
                $tagName = strtolower($node->tagName);

                // Skip header, footer, and script tags
                if (in_array($tagName, ['header', 'footer', 'script'])) {
                    continue;
                }

                // Skip lead form panels (we handle these separately)
                if ($node->hasAttribute('id') && $node->getAttribute('id') === 'panelHeader') {
                    continue;
                }

                $content .= $dom->saveHTML($node) . "\n";
            }
        }

        return trim($content);
    }

    /**
     * Extract lead form panel
     */
    protected function extractLeadForm(string $html): string
    {
        if (preg_match('/<div[^>]*id="panelHeader"[^>]*>.*?<\/div>\s*<\/div>/s', $html, $matches)) {
            return trim($matches[0]);
        }
        return '';
    }

    /**
     * Convert asset paths to Laravel helpers
     */
    protected function convertAssetPaths(string $content): string
    {
        // Convert CSS url() calls - handle both single and double quotes
        $content = preg_replace('/url\([\'"]images\/([^\'")]+\.(png|jpg|jpeg|webp|svg))[\'"]([^)]*)\)/', 'url(\'{{ asset(\'images/site/$1\') }}\' $3)', $content);

        // Convert image src attributes
        $content = preg_replace('/src=[\'"]images\/([^\'")]+\.(png|jpg|jpeg|webp|svg))[\'"]/', 'src="{{ asset(\'images/site/$1\') }}"', $content);

        // Convert CSS href attributes
        $content = preg_replace('/href=[\'"]css\/([^\'")]+\.css)[\'"]/', 'href="{{ asset(\'css/$1\') }}"', $content);

        return $content;
    }

    /**
     * Convert booking URLs from path-based to query parameter format
     */
    protected function convertBookingUrls(string $content): string
    {
        // Convert book.primaapp.com URLs from /region to /?region=region format
        $content = preg_replace('/href=[\'"]https:\/\/book\.primaapp\.com\/(miami|los-angeles|ibiza)[\'"]/', 'href="https://book.primaapp.com/?region=$1"', $content);

        return $content;
    }

    /**
     * Replace HTML contact forms with Livewire Talk to PRIMA component
     */
    protected function replaceForms(string $html): string
    {
        // Replace any form element with the Livewire component
        // This pattern looks for <form> tags and replaces them with our component
        $pattern = '/<form[^>]*>.*?<\/form>/s';
        $replacement = '      <!-- Livewire Site Contact Form Component -->
      <div class="mt-3">
        <livewire:site-contact-form />
      </div>';

        $html = preg_replace($pattern, $replacement, $html);

        // Also update form headings to "Talk to PRIMA"
        $html = preg_replace('/<h3([^>]*?)>Tell us a bit about you<\/h3>/', '<h3$1>Talk to PRIMA</h3>', $html);
        $html = preg_replace('/<h3([^>]*?)>Join PRIMA<\/h3>/', '<h3$1>Talk to PRIMA</h3>', $html);

        return $html;
    }

    /**
     * Convert "Schedule A Call" links to buttons that open the contact drawer
     */
    protected function convertScheduleCallButtons(string $html): string
    {
        // Pattern to find "Schedule A Call" links and convert to buttons
        $pattern = '/<a([^>]*href=["\'][#]?["\'][^>]*)>(\s*Schedule\s*A?\s*Call\s*)<\/a>/i';
        $replacement = '<button type="button" data-target="panelHeader"$1>$2</button>';
        $html = preg_replace($pattern, $replacement, $html);

        // Remove href attribute from the converted buttons
        $html = preg_replace('/(<button[^>]+)href=["\'][^"\'^]*["\']([^>]*>)/', '$1$2', $html);

        return $html;
    }

    /**
     * Convert PRIMA logo div to a home page link
     */
    protected function convertPrimaLogoToLink(string $html): string
    {
        // Pattern to find PRIMA logo div/text and convert to home link
        $pattern = '/<div([^>]*class="[^"]*header-logo[^"]*"[^>]*)>(\s*PRIMA\s*)<\/div>/i';
        $replacement = '<a href="{{ route(\'home\') }}"$1 hover:text-indigo-700 transition-colors">$2</a>';
        $html = preg_replace($pattern, $replacement, $html);

        // Also handle cases where PRIMA might be in different HTML elements
        $pattern2 = '/<([^>]*class="[^"]*header-logo[^"]*"[^>]*)>(\s*PRIMA\s*)<\/[^>]*>/i';
        $replacement2 = '<a href="{{ route(\'home\') }}"$1 hover:text-indigo-700 transition-colors">$2</a>';
        $html = preg_replace($pattern2, $replacement2, $html);

        return $html;
    }

    /**
     * Fix footer overlap issue by removing negative margin from last image
     */
    protected function fixFooterOverlap(string $content): string
    {
        // Find the last image with negative margin before the footer
        // Remove mb-[-175px] and md:-mb-0 from the last image in the content
        $pattern = '/(<img[^>]*class="[^"]*?)md:-mb-0\s+mb-\[-175px\]([^"]*"[^>]*>)(?!.*<img)/s';
        $replacement = '$1$2';

        $content = preg_replace($pattern, $replacement, $content);

        return $content;
    }

    /**
     * Extract preserved content sections from existing Blade file
     */
    protected function extractPreservedSections(string $bladePath): array
    {
        if (!File::exists($bladePath)) {
            return [];
        }

        $content = File::get($bladePath);
        $sections = [];

        // Match content between SYNC:PRESERVE:START and SYNC:PRESERVE:END delimiters
        $pattern = '/<!-- SYNC:PRESERVE:START(?::([^-]+))? -->(.*?)<!-- SYNC:PRESERVE:END -->/s';

        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $sectionId = $match[1] ?? 'default'; // Optional section ID
                $sectionContent = $match[2];
                $sections[$sectionId] = $sectionContent;
            }
        }

        return $sections;
    }

    /**
     * Merge preserved sections back into new content
     */
    protected function mergePreservedSections(string $content, array $preservedSections): string
    {
        if (empty($preservedSections)) {
            return $content;
        }

        foreach ($preservedSections as $sectionId => $sectionContent) {
            // Create the delimiter pattern for this section
            $sectionIdPart = ($sectionId === 'default' || empty($sectionId)) ? '' : ':' . $sectionId;
            $startDelimiter = "<!-- SYNC:PRESERVE:START{$sectionIdPart} -->";
            $endDelimiter = "<!-- SYNC:PRESERVE:END -->";

            // Replace placeholder or add at the end if no delimiter found
            $pattern = '/<!-- SYNC:PRESERVE:START' . preg_quote($sectionIdPart, '/') . ' -->.*?<!-- SYNC:PRESERVE:END -->/s';

            if (preg_match($pattern, $content)) {
                // Replace existing placeholder
                $replacement = $startDelimiter . $sectionContent . $endDelimiter;
                $content = preg_replace($pattern, $replacement, $content);
            } else {
                // Add at the end of content section if no delimiter found
                $contentEndPattern = '/(@endsection)$/';
                if (preg_match($contentEndPattern, $content)) {
                    $addition = "\n{$startDelimiter}{$sectionContent}{$endDelimiter}\n";
                    $content = preg_replace($contentEndPattern, $addition . '$1', $content);
                }
            }
        }

        return $content;
    }

    /**
     * Build complete Blade template
     */
    protected function buildBladeTemplate(string $title, string $leadForm, string $content, string $bladeFile): string
    {
        // Fix footer overlap issue on index page
        if ($bladeFile === 'index.blade.php') {
            $content = $this->fixFooterOverlap($content);
        }

        $template = "@extends('site.layout')\n\n";
        $template .= "@section('title', '{$title}')\n\n";

        if ($leadForm) {
            // Generate clean Livewire form section that's preserved from future syncs
            $template .= "@section('lead-form')\n";
            $template .= "<!-- SYNC:PRESERVE:START:lead-form -->\n";
            $template .= "<div id=\"panelHeader\" class=\"collapsible\">\n";
            $template .= "    <div class=\"max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 border-t border-slate-200 bg-white shadow-sm\">\n";
            $template .= "      <div class=\"flex items-center justify-between mb-4\">\n";
            $template .= "        <h3 class=\"text-xl font-bold\">Talk to PRIMA</h3>\n";
            $template .= "        <button type=\"button\" class=\"close-btn text-slate-500 hover:text-slate-700\" data-close=\"panelHeader\"\n";
            $template .= "          aria-label=\"Close\"><i data-lucide=\"x\" class=\"w-5 h-5\"></i></button>\n";
            $template .= "      </div>\n";
            $template .= "      <livewire:site-contact-form />\n";
            $template .= "    </div>\n";
            $template .= "  </div>\n";
            $template .= "<!-- SYNC:PRESERVE:END -->\n";
            $template .= "@endsection\n\n";
        }

        $template .= "@section('content')\n";
        $template .= $content . "\n";
        $template .= "@endsection";

        return $template;
    }

    /**
     * Sync CSS files
     */
    protected function syncCss(): void
    {
        $sourceCss = storage_path('app/Prima-web/css/style.css');
        $targetCss = resource_path('css/site.css');

        if (File::exists($sourceCss)) {
            $sourceModified = File::lastModified($sourceCss);
            $targetModified = File::exists($targetCss) ? File::lastModified($targetCss) : 0;

            if ($this->option('force') || $sourceModified > $targetModified) {
                // For now, skip CSS sync to avoid corrupting the carefully crafted site.css
                // In the future, this could be enhanced to properly merge designer CSS
                $this->line('⏭️  CSS sync skipped - manual CSS management in use');
                $this->line('💡 To update CSS: manually edit resources/css/site.css and run npm run build or use --build flag');
            } else {
                $this->line('⏭️  CSS file is up to date');
            }
        }
    }

    /**
     * Sync images
     */
    protected function syncImages(): void
    {
        $sourceImages = storage_path('app/Prima-web/images');
        $targetImages = public_path('images/site');

        if (File::exists($sourceImages)) {
            $this->info('🖼️  Syncing images...');

            // Create target directory if it doesn't exist
            if (!File::exists($targetImages)) {
                File::makeDirectory($targetImages, 0755, true);
            }

            $files = File::files($sourceImages);
            $copiedCount = 0;

            foreach ($files as $file) {
                $sourceFile = $file->getPathname();
                $targetFile = $targetImages . '/' . $file->getFilename();

                if (!File::exists($targetFile) ||
                    $this->option('force') ||
                    File::lastModified($sourceFile) > File::lastModified($targetFile)) {

                    File::copy($sourceFile, $targetFile);
                    $copiedCount++;
                }
            }

            if ($copiedCount > 0) {
                $this->info("✅ Copied {$copiedCount} image(s)");
            } else {
                $this->line('⏭️  All images are up to date');
            }
        }
    }

    /**
     * Sync layout elements (header/footer) from HTML to layout.blade.php
     */
    protected function syncLayout(): void
    {
        $this->newLine();
        $this->info('🏗️  Syncing layout elements from HTML...');

        $htmlFile = storage_path('app/Prima-web/index.html');
        $layoutFile = resource_path('views/site/layout.blade.php');

        if (!File::exists($htmlFile)) {
            $this->warn('⚠️  HTML file not found for layout sync: index.html');
            return;
        }

        if (!File::exists($layoutFile)) {
            $this->warn('⚠️  Layout file not found: layout.blade.php');
            return;
        }

        // Read HTML content
        $htmlContent = File::get($htmlFile);
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($htmlContent);
        libxml_clear_errors();

        // Extract header and footer
        $xpath = new \DOMXPath($dom);
        $header = $xpath->query('//header')->item(0);
        $footer = $xpath->query('//footer')->item(0);

        if (!$header) {
            $this->warn('⚠️  No header found in HTML file');
            return;
        }

        if (!$footer) {
            $this->warn('⚠️  No footer found in HTML file');
            return;
        }

        // Convert header to Blade content
        $headerHtml = $dom->saveHTML($header);
        $bladeHeader = $this->convertHtmlToBlade($headerHtml);

        // Convert footer to Blade content
        $footerHtml = $dom->saveHTML($footer);
        $bladeFooter = $this->convertHtmlToBlade($footerHtml);

        // Read current layout
        $layoutContent = File::get($layoutFile);

        // Replace header content
        $layoutContent = preg_replace(
            '/<!-- HEADER -->.*?<\/header>/s',
            "<!-- HEADER -->\n    " . $bladeHeader,
            $layoutContent
        );

        // Replace footer content and fix margin issues
        $bladeFooter = str_replace('mt-[175px]', 'mt-8', $bladeFooter); // Fix footer margin
        $layoutContent = preg_replace(
            '/<!-- FOOTER -->.*?<\/footer>/s',
            "<!-- FOOTER -->\n    " . $bladeFooter,
            $layoutContent
        );

        // Preserve custom content sections from existing layout
        $preservedSections = $this->extractPreservedSections($layoutFile);

        // Merge preserved sections back into layout
        $layoutContent = $this->mergePreservedSections($layoutContent, $preservedSections);

        // Write updated layout
        File::put($layoutFile, $layoutContent);
        $this->info('✅ Updated layout.blade.php with synced header/footer');
    }

    /**
     * Convert HTML content to Blade template syntax
     */
    protected function convertHtmlToBlade(string $html): string
    {
        // Convert asset paths
        $html = $this->convertAssetPaths($html);

        // Convert booking URLs to query parameter format
        $html = $this->convertBookingUrls($html);

        // Replace contact forms with Livewire Talk to PRIMA component
        $html = $this->replaceForms($html);

        // Convert "Schedule A Call" links to contact drawer buttons
        $html = $this->convertScheduleCallButtons($html);

        // Convert PRIMA logo to home page link
        $html = $this->convertPrimaLogoToLink($html);

        // Convert navigation links to Laravel routes (now at root level)
        $html = preg_replace('/href="index\.html"/', 'href="{{ route(\'home\') }}"', $html);
        $html = preg_replace('/href="hotels\.html"/', 'href="{{ route(\'hotels\') }}"', $html);
        $html = preg_replace('/href="restaurants\.html"/', 'href="{{ route(\'restaurants\') }}"', $html);
        $html = preg_replace('/href="concierges\.html"/', 'href="{{ route(\'concierges\') }}"', $html);
        $html = preg_replace('/href="influencers\.html"/', 'href="{{ route(\'influencers\') }}"', $html);

        // Handle Home link with javascript:void(0) - specifically for Home navigation
        $html = preg_replace('/<a href="javascript:void\(0\)" class="nav_menu-item">Home<\/a>/', '<a href="{{ route(\'home\') }}" class="nav_menu-item">Home</a>', $html);

        // Add active state detection for navigation items (now at root level)
        $html = preg_replace(
            '/href="{{ route\(\'home\'\) }}" class="nav_menu-item"/',
            'href="{{ route(\'home\') }}" class="nav_menu-item @if(request()->routeIs(\'home\')) active @endif"',
            $html
        );
        $html = preg_replace(
            '/href="{{ route\(\'hotels\'\) }}" class="nav_menu-item"/',
            'href="{{ route(\'hotels\') }}" class="nav_menu-item @if(request()->routeIs(\'hotels\')) active @endif"',
            $html
        );
        $html = preg_replace(
            '/href="{{ route\(\'restaurants\'\) }}" class="nav_menu-item"/',
            'href="{{ route(\'restaurants\') }}" class="nav_menu-item @if(request()->routeIs(\'restaurants\')) active @endif"',
            $html
        );
        $html = preg_replace(
            '/href="{{ route\(\'concierges\'\) }}" class="nav_menu-item"/',
            'href="{{ route(\'concierges\') }}" class="nav_menu-item @if(request()->routeIs(\'concierges\')) active @endif"',
            $html
        );
        $html = preg_replace(
            '/href="{{ route\(\'influencers\'\) }}" class="nav_menu-item"/',
            'href="{{ route(\'influencers\') }}" class="nav_menu-item @if(request()->routeIs(\'influencers\')) active @endif"',
            $html
        );

        return $html;
    }

    /**
     * Run JavaScript-based Tailwind CSS tools
     */
    protected function runTailwindTools(): void
    {
        // Run the tailwind-merge based conflict resolver
        $this->executeShellCommand(['node', 'scripts/fix-tailwind.js'], '🔄 Resolving class conflicts...');

        // Run prettier with tailwind plugin for class ordering - exclude layout.blade.php to preserve exact class order
        $this->line('🎨 Organizing Tailwind class order (preserving layout.blade.php)...');
        $bladeFiles = ['concierges.blade.php', 'hotels.blade.php', 'index.blade.php', 'influencers.blade.php', 'restaurants.blade.php'];

        foreach ($bladeFiles as $file) {
            $this->executeShellCommand(['prettier', '--write', "resources/views/site/{$file}"], "   📄 Formatting {$file}...");
        }

        // Rebuild production assets if requested
        if ($this->option('build')) {
            $this->line('🏗️  Rebuilding production assets...');
            $this->executeShellCommand(['npm', 'run', 'build'], '📦 Building optimized CSS and JS...');
        }
    }

    /**
     * Execute a shell command and display output
     */
    protected function executeShellCommand(array $command, string $message): void
    {
        $this->line($message);

        $process = new Process($command);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->warn("Command failed: " . $process->getErrorOutput());
        } else {
            $output = trim($process->getOutput());
            if (!empty($output)) {
                $this->info("✅ " . $output);
            } else {
                $this->info("✅ Command completed successfully");
            }
        }
    }

    /**
     * Detect layout changes in HTML files
     */
    protected function detectLayoutChanges(string $htmlPath, string $htmlFile): void
    {
        if (!$this->option('detect-layout')) {
            return;
        }

        $htmlContent = File::get($htmlPath);
        $dom = new DOMDocument();
        @$dom->loadHTML($htmlContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new DOMXPath($dom);

        // Extract header
        $header = $xpath->query('//header')->item(0);
        if ($header) {
            $headerContent = $dom->saveHTML($header);
            $this->checkLayoutSection('header', $headerContent, $htmlFile);
        }

        // Extract footer
        $footer = $xpath->query('//footer')->item(0);
        if ($footer) {
            $footerContent = $dom->saveHTML($footer);
            $this->checkLayoutSection('footer', $footerContent, $htmlFile);
        }
    }

    /**
     * Check if layout section has changed
     */
    protected function checkLayoutSection(string $section, string $content, string $htmlFile): void
    {
        $layoutPath = resource_path('views/site/layout.blade.php');
        $layoutContent = File::get($layoutPath);

        // Check for critical CSS classes that are often lost during sync
        $criticalClasses = [
            'header' => ['border-b', 'sticky', 'backdrop-blur'],
            'footer' => ['border-t', 'mt-[175px]', 'bg-slate-100']
        ];

        if (isset($criticalClasses[$section])) {
            $missingClasses = [];
            foreach ($criticalClasses[$section] as $class) {
                if (str_contains($content, $class) && !str_contains($layoutContent, $class)) {
                    $missingClasses[] = $class;
                }
            }

            if (!empty($missingClasses)) {
                $this->warn("⚠️  {$section} in {$htmlFile} contains classes missing from layout: " . implode(', ', $missingClasses));
                $this->line("   💡 You may need to manually update resources/views/site/layout.blade.php");
            }
        }

        // Original basic checks
        if ($section === 'header' && !str_contains($layoutContent, 'PRIMA')) {
            $this->warn("⚠️  Header structure may have changed in {$htmlFile} - manual review recommended");
        }

        if ($section === 'footer' && !str_contains($layoutContent, '</footer>')) {
            $this->warn("⚠️  Footer structure may have changed in {$htmlFile} - manual review recommended");
        }
    }
}

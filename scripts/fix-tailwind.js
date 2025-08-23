#!/usr/bin/env node

import { readFileSync, writeFileSync, readdirSync, statSync } from 'fs';
import { join, extname } from 'path';
import { twMerge } from 'tailwind-merge';

/**
 * Fix Tailwind CSS conflicts in Blade templates using tailwind-merge
 */
class TailwindFixer {
    constructor() {
        this.filesFixed = 0;
        this.conflictsResolved = 0;
        this.dryRun = process.argv.includes('--dry-run');
        this.verbose = process.argv.includes('--verbose');
    }

    /**
     * Process all Blade files in the site views directory
     */
    async fixFiles() {
        const sitePath = 'resources/views/site';
        const files = this.getBladeFiles(sitePath);

        console.log(`🔧 Processing ${files.length} Blade template(s)...`);

        for (const file of files) {
            await this.processFile(file);
        }

        this.printSummary();
    }

    /**
     * Get all Blade files recursively
     */
    getBladeFiles(dir) {
        const files = [];

        function walkDir(currentDir) {
            const items = readdirSync(currentDir);

            for (const item of items) {
                const itemPath = join(currentDir, item);
                const stat = statSync(itemPath);

                if (stat.isDirectory()) {
                    walkDir(itemPath);
                } else if (extname(item) === '.php' && item.endsWith('.blade.php')) {
                    files.push(itemPath);
                }
            }
        }

        walkDir(dir);
        return files;
    }

    /**
     * Process a single file
     */
    async processFile(filePath) {
        try {
            const content = readFileSync(filePath, 'utf8');
            const fixedContent = this.fixTailwindClasses(content);

            if (content !== fixedContent) {
                this.filesFixed++;

                if (this.dryRun) {
                    console.log(`🔍 Would fix conflicts in: ${filePath}`);
                } else {
                    writeFileSync(filePath, fixedContent);
                    console.log(`✅ Fixed conflicts in: ${filePath}`);
                }
            } else if (this.verbose) {
                console.log(`⏭️  No conflicts in: ${filePath}`);
            }
        } catch (error) {
            console.error(`❌ Error processing ${filePath}:`, error.message);
        }
    }

    /**
     * Fix Tailwind classes in content using tailwind-merge
     */
    fixTailwindClasses(content) {
        // Match class attributes and merge conflicting classes
        return content.replace(/class="([^"]*)"/g, (match, classString) => {
            const originalClasses = classString.trim();

            if (!originalClasses) {
                return match;
            }

            // Use tailwind-merge to resolve conflicts
            const mergedClasses = twMerge(originalClasses);

            // Check if any changes were made
            if (originalClasses !== mergedClasses) {
                this.conflictsResolved++;

                if (this.verbose) {
                    console.log(`  Resolved: "${originalClasses}" → "${mergedClasses}"`);
                }
            }

            return `class="${mergedClasses}"`;
        });
    }

    /**
     * Print processing summary
     */
    printSummary() {
        console.log('\n📊 Summary:');
        console.log(`Files processed: ${this.filesFixed}`);
        console.log(`Conflicts resolved: ${this.conflictsResolved}`);

        if (this.filesFixed === 0) {
            console.log('✅ No Tailwind conflicts found!');
        } else {
            const action = this.dryRun ? 'Would fix' : 'Fixed';
            console.log(`🎉 ${action} conflicts in ${this.filesFixed} file(s)`);
        }
    }
}

// Run the fixer
const fixer = new TailwindFixer();
fixer.fixFiles().catch(console.error);

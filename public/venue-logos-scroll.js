// Venue Logos Scroll - PROPER Web Component
// This follows the Web Components specification: https://developer.mozilla.org/en-US/docs/Web/API/Web_components

// Capture the script source URL at load time to determine the API host
const scriptSrc = document.currentScript?.src || '';
let apiBaseUrl = '';

if (scriptSrc) {
    // Extract the base URL from where this script is loaded
    const url = new URL(scriptSrc);
    apiBaseUrl = url.origin;
} else {
    // Fallback - will only work if script is on same domain
    apiBaseUrl = window.location.origin;
}

// Create the HTML template for the component
const template = document.createElement('template');
template.innerHTML = `
    <style>
        :host {
            display: block;
            width: 100%;
            overflow: hidden;
            background-color: var(--background-color, #ffffff);
        }

        .venue-logos-scroll {
            position: relative;
        }

        /* Exact same CSS from original component */
        @keyframes slide {
            from {
                transform: translateX(0);
            }
            to {
                transform: translateX(-100%);
            }
        }

        @keyframes slide-reverse {
            from {
                transform: translateX(-100%);
            }
            to {
                transform: translateX(0);
            }
        }

        .logos-row {
            overflow: hidden;
            padding: 1rem 0;
        }

        .logos-container {
            display: inline-flex;
            will-change: transform;
        }

        .logo-item {
            flex: none;
            margin: 0 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .logo-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f3f4f6;
            color: #374151;
            font-weight: 600;
            text-align: center;
            border-radius: 0.375rem;
            font-size: 0.875rem;
        }

        /* Responsive sizing - exact same as original */
        .logo-item {
            width: 6rem; /* w-24 */
            height: 3.75rem; /* h-[3.75rem] */
        }

        @media (min-width: 640px) {
            .logo-item {
                width: 8rem; /* sm:w-32 */
                height: 5rem; /* sm:h-20 */
            }
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: #6b7280;
        }

        .error {
            text-align: center;
            padding: 2rem;
            color: #ef4444;
            background: #fef2f2;
            border-radius: 0.5rem;
            margin: 1rem 0;
        }
    </style>

    <div class="venue-logos-scroll">
        <div id="loading" class="loading">Loading venues...</div>
        <div id="error" class="error" style="display: none;"></div>

        <!-- Top Row: Scrolls visually RIGHT via flipping -->
        <div class="logos-row">
            <div id="row1-flip-container" style="transform: scaleX(-1);">
                <div class="logos-container" id="row1-wrapper" style="will-change: transform;">
                    <!-- Logos will be inserted here -->
                </div>
            </div>
        </div>

        <!-- Bottom Row: Scrolls LEFT (normal order) -->
        <div class="logos-row">
            <div class="logos-container" id="row2-wrapper" style="will-change: transform;">
                <!-- Logos will be inserted here -->
            </div>
        </div>
    </div>
`;

// Define the custom element class
class VenueLogosScroll extends HTMLElement {
    constructor() {
        super();

        // Attach shadow DOM - this is what makes it a web component
        this.attachShadow({ mode: 'open' });

        // Clone the template content into shadow DOM
        this.shadowRoot.appendChild(template.content.cloneNode(true));

        // Animation state
        this.row1Pos = 0;
        this.row2Pos = 0;
        this.row1Speed = 0.25;
        this.row2Speed = 0.25;
        this.row1Width = 0;
        this.row2Width = 0;

        this.init();
    }

    async init() {
        // Use the API base URL captured when the script was loaded
        const apiEndpoint = this.getAttribute('api-endpoint') || `${apiBaseUrl}/api/venue-logos`;

        try {
            const response = await fetch(apiEndpoint);
            const data = await response.json();

            this.shadowRoot.getElementById('loading').style.display = 'none';

            if (data.first_row && data.first_row.length > 0) {
                this.renderRow('row1-wrapper', data.first_row, true);  // Top row (flipped)
                this.renderRow('row2-wrapper', data.second_row, false); // Bottom row (normal)
                this.startAnimation();
            } else {
                throw new Error('No venue data received');
            }
        } catch (error) {
            console.error('Error:', error);
            this.shadowRoot.getElementById('loading').style.display = 'none';
            this.shadowRoot.getElementById('error').style.display = 'block';
            this.shadowRoot.getElementById('error').textContent = `Error: ${error.message}`;
        }
    }

    renderRow(containerId, venues, isFlipped) {
        const container = this.shadowRoot.getElementById(containerId);
        container.innerHTML = '';

        const mainSet = document.createElement('div');
        mainSet.className = 'logos-container';

        venues.forEach(venue => {
            const logoItem = document.createElement('div');
            logoItem.className = 'logo-item';

            if (venue.logo_path) {
                const img = document.createElement('img');
                img.className = 'logo-image';
                img.src = venue.logo_path;
                img.alt = venue.name;
                img.loading = 'lazy';
                img.onerror = () => {
                    logoItem.innerHTML = `<div class="logo-placeholder">${venue.name}</div>`;
                };
                logoItem.appendChild(img);
            } else {
                logoItem.innerHTML = `<div class="logo-placeholder">${venue.name}</div>`;
            }

            // For the top row, flip each logo back so they aren't mirrored
            if (isFlipped) {
                logoItem.style.transform = 'scaleX(-1)';
            }

            mainSet.appendChild(logoItem);
        });

        container.appendChild(mainSet);

        // Duplicate for seamless scrolling
        const duplicateSet = mainSet.cloneNode(true);
        container.appendChild(duplicateSet);

        // Measure width after DOM update
        setTimeout(() => {
            if (isFlipped) {
                this.row1Width = mainSet.offsetWidth;
            } else {
                this.row2Width = mainSet.offsetWidth;
            }
        }, 100);
    }

    startAnimation() {
        const animate = () => {
            // Top row scrolls RIGHT: increase the translation value
            this.row1Pos += this.row1Speed;
            // Bottom row scrolls LEFT: increase similarly (we apply a negative shift)
            this.row2Pos += this.row2Speed;

            // Reset when one copy has fully scrolled
            if (this.row1Pos >= this.row1Width) {
                this.row1Pos = 0;
            }
            if (this.row2Pos >= this.row2Width) {
                this.row2Pos = 0;
            }

            // Apply transforms
            const row1Wrapper = this.shadowRoot.getElementById('row1-wrapper');
            const row2Wrapper = this.shadowRoot.getElementById('row2-wrapper');

            if (row1Wrapper) {
                row1Wrapper.style.transform = `translateX(-${this.row1Pos}px)`;
            }
            if (row2Wrapper) {
                row2Wrapper.style.transform = `translateX(-${this.row2Pos}px)`;
            }

            requestAnimationFrame(animate);
        };

        requestAnimationFrame(animate);
    }
}

// Register the custom element - this is what makes it a web component
customElements.define('venue-logos-scroll', VenueLogosScroll);
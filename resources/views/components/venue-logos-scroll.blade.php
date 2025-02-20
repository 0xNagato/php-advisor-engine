<div class="relative overflow-hidden bg-white" x-data="rotatingLogos({
    firstRow: @js(
        $firstRow->map(
                fn($venue) => [
                    'id' => $venue->id,
                    'name' => $venue->name,
                    'logo_path' => $venue->logo_path ? Storage::disk('do')->url($venue->logo_path) : null,
                ],
            )->values()
    ),
    secondRow: @js(
        $secondRow->map(
                fn($venue) => [
                    'id' => $venue->id,
                    'name' => $venue->name,
                    'logo_path' => $venue->logo_path ? Storage::disk('do')->url($venue->logo_path) : null,
                ],
            )->values()
    )
})" x-init="init()">
    <style>
        .logos-slide {
            display: inline-flex;
            animation: 35s slide infinite linear;
        }

        .logos-slide-reverse {
            display: inline-flex;
            animation: 35s slide-reverse infinite linear;
        }

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

        @keyframes scroll-left {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(-50%);
            }
        }

        @keyframes scroll-right {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(50%);
            }
        }

        .animate-scroll-left {
            animation: scroll-left 35s linear infinite;
        }

        .animate-scroll-right {
            animation: scroll-right 35s linear infinite;
        }
    </style>

    <!-- Top Row: Scrolls visually RIGHT via flipping -->
    <div class="py-4 overflow-hidden">
        <!-- Flip the container so that left scroll appears as right scroll -->
        <div class="transform scale-x-[-1]">
            <div class="flex" x-ref="row1Wrapper" style="will-change: transform;">
                <!-- Duplicate copy -->
                <div class="flex" x-ref="set1">
                    <template x-for="venue in firstRowLogos" :key="venue.id">
                        <!-- Flip each logo back so that they aren't mirrored -->
                        <div class="flex-none w-24 h-[3.75rem] mx-4 transform scale-x-[-1] sm:w-32 sm:h-20">
                            <template x-if="venue.logo_path">
                                <img :src="venue.logo_path" :alt="venue.name" class="object-contain w-full h-full"
                                    loading="lazy">
                            </template>
                            <template x-if="!venue.logo_path">
                                <div class="flex items-center justify-center w-full h-full text-sm font-semibold text-center text-gray-700 bg-gray-100 rounded"
                                    x-text="venue.name"></div>
                            </template>
                        </div>
                    </template>
                </div>
                <!-- Main copy -->
                <div class="flex">
                    <template x-for="venue in firstRowLogos" :key="'orig-' + venue.id">
                        <div class="flex-none w-24 h-[3.75rem] mx-4 transform scale-x-[-1] sm:w-32 sm:h-20">
                            <template x-if="venue.logo_path">
                                <img :src="venue.logo_path" :alt="venue.name" class="object-contain w-full h-full"
                                    loading="lazy">
                            </template>
                            <template x-if="!venue.logo_path">
                                <div class="flex items-center justify-center w-full h-full text-sm font-semibold text-center text-gray-700 bg-gray-100 rounded"
                                    x-text="venue.name"></div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Row: Scrolls LEFT (normal order) -->
    <div class="py-4 overflow-hidden">
        <div class="flex" x-ref="row2Wrapper" style="will-change: transform;">
            <!-- Main copy -->
            <div class="flex" x-ref="set2">
                <template x-for="venue in secondRowLogos" :key="venue.id">
                    <div class="flex-none w-24 h-[3.75rem] mx-4 sm:w-32 sm:h-20">
                        <template x-if="venue.logo_path">
                            <img :src="venue.logo_path" :alt="venue.name" class="object-contain w-full h-full"
                                loading="lazy">
                        </template>
                        <template x-if="!venue.logo_path">
                            <div class="flex items-center justify-center w-full h-full text-sm font-semibold text-center text-gray-700 bg-gray-100 rounded"
                                x-text="venue.name"></div>
                        </template>
                    </div>
                </template>
            </div>
            <!-- Duplicate copy -->
            <div class="flex">
                <template x-for="venue in secondRowLogos" :key="'dup-' + venue.id">
                    <div class="flex-none w-24 h-[3.75rem] mx-4 sm:w-32 sm:h-20">
                        <template x-if="venue.logo_path">
                            <img :src="venue.logo_path" :alt="venue.name" class="object-contain w-full h-full"
                                loading="lazy">
                        </template>
                        <template x-if="!venue.logo_path">
                            <div class="flex items-center justify-center w-full h-full text-sm font-semibold text-center text-gray-700 bg-gray-100 rounded"
                                x-text="venue.name"></div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <script>
        function rotatingLogos(data) {
            return {
                // The arrays passed from PHP
                firstRowLogos: data.firstRow,
                secondRowLogos: data.secondRow,
                // Animation state for each row
                row1Pos: 0,
                row2Pos: 0,
                // Speed (in pixels per frame); adjusted by half
                row1Speed: 0.25,
                row2Speed: 0.25,
                // The width of one copy of the logos (for seamless looping)
                row1Width: 0,
                row2Width: 0,
                init() {
                    // Wait for DOM to be ready
                    this.$nextTick(() => {
                        // For the top row, the first copy's width is used
                        this.row1Width = this.$refs.set1.offsetWidth;
                        // For the bottom row, measure from the first copy too
                        this.row2Width = this.$refs.set2.offsetWidth;
                        this.animate();
                    });
                },
                animate() {
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
                    // For the top row, apply a positive translate (scrolling right)
                    this.$refs.row1Wrapper.style.transform = `translateX(-${this.row1Pos}px)`;
                    // For the bottom row, apply a negative translate (scrolling left)
                    this.$refs.row2Wrapper.style.transform = `translateX(-${this.row2Pos}px)`;
                    requestAnimationFrame(() => this.animate());
                }
            }
        }
    </script>
</div>

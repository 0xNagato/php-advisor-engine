<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRIMA Booking Calculator</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs" defer></script>
</head>

<body class="bg-gray-100 p-4 md:p-8">
    <div class="max-w-7xl mx-auto" x-data="calculator()" x-init="$watch('hasPartnerVenue', recalculate);
    $watch('hasPartnerConcierge', recalculate);
    $watch('hasReferral1', recalculate);
    $watch('hasReferral2', recalculate)">
        <h1 class="text-2xl md:text-3xl font-bold mb-6 md:mb-8 text-center text-gray-900">PRIMA Booking Calculator</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 mb-8">
            <div class="space-y-4 md:space-y-6">
                <div>
                    <label class="block text-sm font-semibold mb-2">Number of Guests (2-8)</label>
                    <input type="number" x-model.number="guests" min="2" max="8"
                        class="w-full border rounded-lg p-2" @input="guests = Math.min(8, Math.max(2, guests))">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Venue %</label>
                    <input type="number" x-model.number="venuePct" min="0" max="100"
                        class="w-full border rounded-lg p-2">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Concierge %</label>
                    <input type="number" x-model.number="conciergePct" min="0" max="100"
                        class="w-full border rounded-lg p-2">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Partner (Venue) % (max 20%)</label>
                    <input type="number" x-model.number="partnerVenuePct" min="0" max="20"
                        class="w-full border rounded-lg p-2">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Partner (Concierge) % (max 10%)</label>
                    <input type="number" x-model.number="partnerConciergePct" min="0" max="10"
                        class="w-full border rounded-lg p-2">
                </div>
            </div>

            <div class="space-y-2 md:pt-9">
                <div class="h-8 flex items-center">
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" x-model="hasPartnerVenue"
                            class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span>Include Partner (Venue)</span>
                    </label>
                </div>
                <div class="h-8 flex items-center">
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" x-model="hasPartnerConcierge"
                            class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span>Include Partner (Concierge)</span>
                    </label>
                </div>
                <div class="h-8 flex items-center">
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" x-model="hasReferral1"
                            class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span>Include Concierge Referral 1 (10%)</span>
                    </label>
                </div>
                <div class="h-8 flex items-center">
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" x-model="hasReferral2"
                            class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span>Include Concierge Referral 2 (5%)</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Prime Booking Column -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-6">Prime Booking Breakdown</h2>

                <div class="mb-8">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-600 mb-3">INCOME</h3>
                    <div class="flex justify-between py-2 border-b border-dashed border-gray-200">
                        <span class="text-sm text-gray-600">Customer Payment Received by PRIMA:</span>
                        <span class="text-sm font-medium" x-text="'$' + customerPayment.toFixed(2)"></span>
                    </div>
                </div>

                <div class="mb-8">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-600 mb-3">INITIAL PAYOUTS BY
                        PRIMA</h3>
                    <div class="flex justify-between py-2 border-b border-dashed border-gray-200">
                        <span class="text-sm text-gray-600">To Venue (<span x-text="venuePct"></span>%):</span>
                        <span class="text-sm font-medium" x-text="'$' + venueEarnings.toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-dashed border-gray-200">
                        <span class="text-sm text-gray-600">To Concierge (<span x-text="conciergePct"></span>%):</span>
                        <span class="text-sm font-medium" x-text="'$' + conciergeEarnings.toFixed(2)"></span>
                    </div>
                </div>

                <div class="mb-8">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-600 mb-3">OPERATIONAL REMAINDER
                    </h3>
                    <div class="flex justify-between py-2 border-b border-dashed border-gray-200">
                        <span class="text-sm text-gray-600">Funds Remaining Held by PRIMA:</span>
                        <span class="text-sm font-semibold bg-blue-100 text-blue-800 px-2 py-0.5 rounded"
                            x-text="'$' + remainderPrime.toFixed(2)"></span>
                    </div>
                </div>

                <div class="mb-8">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-600 mb-3"
                        x-text="'DISTRIBUTION OF REMAINDER ($' + remainderPrime.toFixed(2) + ')'"></h3>
                    <template x-if="hasPartnerVenue">
                        <div class="flex justify-between py-2 border-b border-dashed border-gray-200">
                            <span class="text-sm text-gray-600">Partner (Venue Referrer) (<span
                                    x-text="partnerVenuePct"></span>%):</span>
                            <span class="text-sm font-medium" x-text="'$' + primePartnerVenue.toFixed(2)"></span>
                        </div>
                    </template>
                    <template x-if="hasPartnerConcierge">
                        <div class="flex justify-between py-2 border-b border-dashed border-gray-200">
                            <span class="text-sm text-gray-600">Partner (Concierge Referrer) (<span
                                    x-text="partnerConciergePct"></span>%):</span>
                            <span class="text-sm font-medium" x-text="'$' + primePartnerConcierge.toFixed(2)"></span>
                        </div>
                    </template>
                    <template x-if="hasReferral1">
                        <div class="flex justify-between py-2 border-b border-dashed border-gray-200">
                            <span class="text-sm text-gray-600">Concierge Referral 1 (10%):</span>
                            <span class="text-sm font-medium" x-text="'$' + primeReferral1.toFixed(2)"></span>
                        </div>
                    </template>
                    <template x-if="hasReferral2">
                        <div class="flex justify-between py-2 border-b border-dashed border-gray-200">
                            <span class="text-sm text-gray-600">Concierge Referral 2 (5%):</span>
                            <span class="text-sm font-medium" x-text="'$' + primeReferral2.toFixed(2)"></span>
                        </div>
                    </template>
                    <div class="flex justify-between py-2 border-b border-dashed border-gray-200">
                        <span class="text-sm font-medium text-gray-600">PRIMA Share from Remainder:</span>
                        <span class="text-sm font-semibold" x-text="'$' + primaPrime.toFixed(2)"></span>
                    </div>
                </div>

                <div class="mt-8">
                    <p class="text-xs font-semibold text-gray-500 text-center uppercase tracking-wider mb-4">FINAL NET
                        POSITION</p>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-600">Venue Net:</span>
                            <span class="text-sm font-semibold text-gray-800"
                                x-text="'+$' + venueEarnings.toFixed(2)"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-600">Concierge Net:</span>
                            <span class="text-sm font-semibold text-gray-800"
                                x-text="'+$' + conciergeEarnings.toFixed(2)"></span>
                        </div>
                        <template x-if="hasPartnerVenue">
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-600">Venue Partner Net:</span>
                                <span class="text-sm font-semibold text-gray-800"
                                    x-text="'+$' + primePartnerVenue.toFixed(2)"></span>
                            </div>
                        </template>
                        <template x-if="hasPartnerConcierge">
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-600">Concierge Partner Net:</span>
                                <span class="text-sm font-semibold text-gray-800"
                                    x-text="'+$' + primePartnerConcierge.toFixed(2)"></span>
                            </div>
                        </template>
                        <template x-if="hasReferral1">
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-600">Referral 1 Net:</span>
                                <span class="text-sm font-semibold text-gray-800"
                                    x-text="'+$' + primeReferral1.toFixed(2)"></span>
                            </div>
                        </template>
                        <template x-if="hasReferral2">
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-600">Referral 2 Net:</span>
                                <span class="text-sm font-semibold text-gray-800"
                                    x-text="'+$' + primeReferral2.toFixed(2)"></span>
                            </div>
                        </template>
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-600">PRIMA Net:</span>
                            <span class="text-sm font-semibold text-gray-800"
                                x-text="'+$' + primaPrime.toFixed(2)"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Non-Prime Booking Column -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-6">Non-Prime Booking Breakdown</h2>

                <div class="mb-8">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-600 mb-3">INCOME (PAID BY VENUE
                        TO PRIMA)</h3>
                    <div class="flex justify-between py-2 border-b border-dashed border-gray-200">
                        <span class="text-sm text-gray-600">Venue Fee (Operational Fund):</span>
                        <span class="text-sm font-medium" x-text="'$' + venueFee.toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-dashed border-gray-200">
                        <span class="text-sm text-gray-600">Processing Fee (Separate):</span>
                        <span class="text-sm font-medium" x-text="'$' + processingFee.toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-dashed border-gray-200">
                        <span class="text-sm font-medium text-gray-600">Total Received by PRIMA:</span>
                        <span class="text-sm font-semibold" x-text="'$' + grossNonPrime.toFixed(2)"></span>
                    </div>
                </div>

                <div class="mb-8">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-600 mb-3">INITIAL PAYOUTS BY
                        PRIMA</h3>
                    <div class="flex justify-between py-2 border-b border-dashed border-gray-200">
                        <span class="text-sm text-gray-600">Concierge Bounty (80% of $<span
                                x-text="venueFee.toFixed(2)"></span>):</span>
                        <span class="text-sm font-medium" x-text="'$' + conciergeBounty.toFixed(2)"></span>
                    </div>
                </div>

                <div class="mb-8">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-600 mb-3">OPERATIONAL REMAINDER
                    </h3>
                    <div class="flex justify-between py-2 border-b border-dashed border-gray-200">
                        <span class="text-sm text-gray-600">Pool for Distribution:</span>
                        <span class="text-sm font-semibold bg-blue-100 text-blue-800 px-2 py-0.5 rounded"
                            x-text="'$' + remainderNonPrime.toFixed(2)"></span>
                    </div>
                </div>

                <div class="mb-8">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-600 mb-3"
                        x-text="'DISTRIBUTION OF REMAINDER ($' + remainderNonPrime.toFixed(2) + ')'"></h3>
                    <template x-if="hasPartnerVenue">
                        <div class="flex justify-between py-2 border-b border-dashed border-gray-200">
                            <span class="text-sm text-gray-600">Partner (Venue Referrer) (<span
                                    x-text="partnerVenuePct"></span>%):</span>
                            <span class="text-sm font-medium" x-text="'$' + nonPrimePartnerVenue.toFixed(2)"></span>
                        </div>
                    </template>
                    <template x-if="hasPartnerConcierge">
                        <div class="flex justify-between py-2 border-b border-dashed border-gray-200">
                            <span class="text-sm text-gray-600">Partner (Concierge Referrer) (<span
                                    x-text="partnerConciergePct"></span>%):</span>
                            <span class="text-sm font-medium"
                                x-text="'$' + nonPrimePartnerConcierge.toFixed(2)"></span>
                        </div>
                    </template>
                    <template x-if="hasReferral1">
                        <div class="flex justify-between py-2 border-b border-dashed border-gray-200">
                            <span class="text-sm text-gray-600">Concierge Referral 1 (10%):</span>
                            <span class="text-sm font-medium" x-text="'$' + nonPrimeReferral1.toFixed(2)"></span>
                        </div>
                    </template>
                    <template x-if="hasReferral2">
                        <div class="flex justify-between py-2 border-b border-dashed border-gray-200">
                            <span class="text-sm text-gray-600">Concierge Referral 2 (5%):</span>
                            <span class="text-sm font-medium" x-text="'$' + nonPrimeReferral2.toFixed(2)"></span>
                        </div>
                    </template>
                    <div class="flex justify-between py-2 border-b border-dashed border-gray-200">
                        <span class="text-sm font-medium text-gray-600">PRIMA Share from Remainder:</span>
                        <span class="text-sm font-semibold" x-text="'$' + primaNonPrime.toFixed(2)"></span>
                    </div>
                </div>

                <div class="mt-8">
                    <p class="text-xs font-semibold text-gray-500 text-center uppercase tracking-wider mb-4">FINAL NET
                        POSITION</p>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-600">Venue Net:</span>
                            <span class="text-sm font-semibold text-red-600"
                                x-text="'-$' + grossNonPrime.toFixed(2)"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-600">Concierge Net:</span>
                            <span class="text-sm font-semibold text-gray-800"
                                x-text="'+$' + conciergeBounty.toFixed(2)"></span>
                        </div>
                        <template x-if="hasPartnerVenue">
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-600">Venue Partner Net:</span>
                                <span class="text-sm font-semibold text-gray-800"
                                    x-text="'+$' + nonPrimePartnerVenue.toFixed(2)"></span>
                            </div>
                        </template>
                        <template x-if="hasPartnerConcierge">
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-600">Concierge Partner Net:</span>
                                <span class="text-sm font-semibold text-gray-800"
                                    x-text="'+$' + nonPrimePartnerConcierge.toFixed(2)"></span>
                            </div>
                        </template>
                        <template x-if="hasReferral1">
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-600">Referral 1 Net:</span>
                                <span class="text-sm font-semibold text-gray-800"
                                    x-text="'+$' + nonPrimeReferral1.toFixed(2)"></span>
                            </div>
                        </template>
                        <template x-if="hasReferral2">
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-600">Referral 2 Net:</span>
                                <span class="text-sm font-semibold text-gray-800"
                                    x-text="'+$' + nonPrimeReferral2.toFixed(2)"></span>
                            </div>
                        </template>
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-600">PRIMA Net:</span>
                            <span class="text-sm font-semibold text-gray-800"
                                x-text="'+$' + primaNonPrime.toFixed(2)"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function calculator() {
            return {
                guests: 2,
                venuePct: 60,
                conciergePct: 10,
                partnerVenuePct: 20,
                partnerConciergePct: 10,
                hasPartnerVenue: true,
                hasPartnerConcierge: true,
                hasReferral1: true,
                hasReferral2: true,
                get customerPayment() {
                    return 200 + Math.max(this.guests - 2, 0) * 50
                },
                get venueEarnings() {
                    return this.customerPayment * (this.venuePct / 100)
                },
                get conciergeEarnings() {
                    return this.customerPayment * (this.conciergePct / 100)
                },
                get remainderPrime() {
                    return this.customerPayment - this.venueEarnings - this.conciergeEarnings
                },
                get primePartnerVenue() {
                    return this.hasPartnerVenue ? this.remainderPrime * (this.partnerVenuePct / 100) : 0
                },
                get primePartnerConcierge() {
                    return this.hasPartnerConcierge ? this.remainderPrime * (this.partnerConciergePct / 100) : 0
                },
                get primeReferral1() {
                    return this.hasReferral1 ? this.remainderPrime * 0.10 : 0
                },
                get primeReferral2() {
                    return this.hasReferral2 ? this.remainderPrime * 0.05 : 0
                },
                get primaPrime() {
                    return this.remainderPrime - (this.primePartnerVenue + this.primePartnerConcierge + this
                        .primeReferral1 + this.primeReferral2)
                },
                get grossPrime() {
                    return this.customerPayment - this.venueEarnings
                },
                get venueFee() {
                    return this.guests * 10
                },
                get processingFee() {
                    return this.venueFee * 0.10
                },
                get grossNonPrime() {
                    return this.venueFee + this.processingFee
                },
                get conciergeBounty() {
                    return this.venueFee * 0.80
                },
                get remainderNonPrime() {
                    return this.venueFee - this.conciergeBounty
                },
                get nonPrimePartnerVenue() {
                    return this.hasPartnerVenue ? this.remainderNonPrime * (this.partnerVenuePct / 100) : 0
                },
                get nonPrimePartnerConcierge() {
                    return this.hasPartnerConcierge ? this.remainderNonPrime * (this.partnerConciergePct / 100) : 0
                },
                get nonPrimeReferral1() {
                    return this.hasReferral1 ? this.remainderNonPrime * 0.10 : 0
                },
                get nonPrimeReferral2() {
                    return this.hasReferral2 ? this.remainderNonPrime * 0.05 : 0
                },
                get primaNonPrime() {
                    return this.remainderNonPrime - (this.nonPrimePartnerVenue + this.nonPrimePartnerConcierge + this
                        .nonPrimeReferral1 + this.nonPrimeReferral2) + this.processingFee
                },
                recalculate() {
                    this.guests = this.guests
                }
            }
        }
    </script>
</body>

</html>

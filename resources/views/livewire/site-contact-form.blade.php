<div>
    @if($hasSent)
        <div class="text-center">
            <p class="text-lg font-semibold text-green-600">Thank you for your message!</p>
            <p class="mt-2 text-sm text-slate-600">We'll get back to you within 24 hours.</p>

            <div class="mt-4">
                <button
                    wire:click="resetForm"
                    class="inline-flex items-center justify-center gap-2 cursor-pointer px-4 py-2 rounded-full border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-medium transition-all duration-150">
                    Submit Another
                </button>
            </div>
        </div>
    @else
        <form wire:submit.prevent="send" class="mt-3 grid grid-cols-2 gap-3">
            <select wire:model="data.persona" required class="col-span-2 sm:col-span-1 w-full px-3 py-2 rounded-xl border border-slate-300">
                <option value="">I am a…</option>
                <option value="hotel">Hotel / Property</option>
                <option value="concierge">Concierge</option>
                <option value="restaurant">Restaurant</option>
                <option value="creator">Creator / Influencer</option>
                <option value="other">Other</option>
            </select>

            <input wire:model="data.fullName" required placeholder="Full Name" class="col-span-2 sm:col-span-1 px-3 py-2 rounded-xl border border-slate-300" />

            <input wire:model="data.company" placeholder="Company / Property" class="col-span-2 px-3 py-2 rounded-xl border border-slate-300" />

            <input wire:model="data.email" required type="email" placeholder="Email" class="col-span-2 sm:col-span-1 px-3 py-2 rounded-xl border border-slate-300" />

            <input wire:model="data.phone" placeholder="Phone" class="col-span-2 sm:col-span-1 px-3 py-2 rounded-xl border border-slate-300" />

            <input wire:model="data.city" placeholder="City" class="col-span-2 sm:col-span-1 px-3 py-2 rounded-xl border border-slate-300" />

            <input wire:model="data.preferredTime" placeholder="Preferred Contact Time" class="col-span-2 sm:col-span-1 px-3 py-2 rounded-xl border border-slate-300" />

            <textarea wire:model="data.notes" rows="3" placeholder="Anything else we should know?" class="col-span-2 px-3 py-2 rounded-xl border border-slate-300"></textarea>

            <div class="col-span-2 flex items-center gap-3">
                <button type="submit"
                    class="inline-flex items-center justify-center gap-2 cursor-pointer px-6 py-3 rounded-full bg-gradient-to-r from-emerald-500 to-green-600 text-white text-lg font-semibold shadow-[3px_3px_0_#047857] hover:opacity-90 active:translate-x-[3px] active:translate-y-[3px] active:shadow-none transition-all duration-150">
                    Submit
                </button>
                <small class="text-xs text-slate-500">We'll get back to you within 24 hours.</small>
            </div>
        </form>
    @endif
</div>

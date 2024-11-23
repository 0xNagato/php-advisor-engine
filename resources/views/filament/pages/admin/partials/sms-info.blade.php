<div x-data="{
    message: $wire.get('data.message') ?? '',
    recipients: $wire.get('data.recipients') ?? [],
    recipientCounts: {{ json_encode($recipientCounts) }},
    get length() { return this.message.length },
    get recipientCount() {
        return this.recipients.reduce((total, group) => total + (this.recipientCounts[group] || 0), 0)
    },
    get credits() {
        if (this.length <= 160) return 1;
        if (this.length <= 306) return 2;
        return 3;
    },
    get totalCredits() { return this.credits * this.recipientCount }
}" x-init="$watch('$wire.get(\'data.message\')', value => message = value ?? '');
$watch('$wire.get(\'data.recipients\')', value => recipients = value ?? []);" class="space-y-4">
    <div class="grid grid-cols-3 gap-4">
        <div class="p-3 bg-white border border-gray-100 rounded-lg shadow-sm">
            <div class="text-sm font-medium text-gray-500">Characters</div>
            <div class="flex items-center justify-center h-9">
                <div class="flex items-baseline">
                    <span class="w-8 text-sm font-bold text-center text-gray-900" x-text="length"></span>
                    <span class="text-sm text-gray-500">/1600</span>
                </div>
            </div>
        </div>

        <div class="p-3 bg-white border border-gray-100 rounded-lg shadow-sm">
            <div class="text-sm font-medium text-gray-500">Recipients</div>
            <div class="flex items-center justify-center h-9">
                <span class="w-8 text-2xl font-bold text-center text-gray-900" x-text="recipientCount"></span>
            </div>
        </div>

        <div class="p-3 bg-white border border-gray-100 rounded-lg shadow-sm">
            <div class="text-sm font-medium text-gray-500">Total credits</div>
            <div class="flex items-center justify-center h-9">
                <span class="w-8 text-2xl font-bold text-center text-gray-900" x-text="totalCredits"></span>
            </div>
        </div>
    </div>

    <div class="space-y-2">
        <div class="text-sm font-medium text-gray-500">Message Types:</div>
        <div class="ml-4 space-y-1">
            <div class="flex items-center space-x-2">
                <div class="w-2 h-2 rounded-full bg-primary-500"></div>
                <div class="text-sm text-gray-600">Standard SMS (160 chars) - 1 credit</div>
            </div>
            <div class="flex items-center space-x-2">
                <div class="w-2 h-2 rounded-full bg-primary-500"></div>
                <div class="text-sm text-gray-600">Extended SMS (306 chars) - 2 credits</div>
            </div>
            <div class="flex items-center space-x-2">
                <div class="w-2 h-2 rounded-full bg-primary-500"></div>
                <div class="text-sm text-gray-600">MMS (1600 chars) - 3 credits</div>
            </div>
        </div>
    </div>
</div>

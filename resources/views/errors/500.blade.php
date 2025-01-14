<!-- resources/views/errors/403.blade.php -->

<x-layouts.app>
    <div>
        <div class="w-full pt-8 text-3xl font-black text-center">
            PRIMA
        </div>
        <div class="p-2 text-2xl font-black text-center dm-serif">
            Everybody Wins<span class="font-sans font-normal">™</span>
        </div>
    </div>
    <div class="flex items-center justify-center p-4">
        <div class="w-full max-w-md space-y-8">
            <div class="p-4 mt-6 space-y-4 bg-white rounded-lg shadow">
                <h2 class="text-2xl font-semibold text-center">Something Went Wrong!</h2>

                <div class="space-y-4 text-base">
                    <img src="/images/chef.png" alt="Chef" class="w-1/3 ml-4 float-end" />
                    <p class="text-left text-gray-600">
                        Oops! It looks like something broke. We're actively working to make PRIMA the best platform
                        for you.
                    </p>

                    <p class="text-left text-gray-600">
                        Please help us by describing what you were doing when this error occurred. This will assist
                        us in
                        resolving the issue more quickly. You can leave a note below, and we'll take care of it
                        promptly.
                        Thank you for your patience and support!
                    </p>
                </div>

                <form class="space-y-2" method="post" action="{{ route('exception.form') }}">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                    <input type="hidden" name="exceptionMessage" value="{{ $exception->getMessage() }}" />
                    <input type="hidden" name="exceptionTrace" value="{{ $exception->getTraceAsString() }}" />

                    <textarea required name="message" class="w-full h-32 p-2 border border-gray-300 rounded-lg"
                        placeholder="What happened? (Optional)"></textarea>

                    <button type="submit"
                        class="w-full p-2 font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                        Send Report and Return to Dashboard
                    </button>
                </form>

                <p class="text-sm text-center text-gray-600">
                    <a href="/platform" class="font-medium text-indigo-600 hover:text-indigo-500">
                        Return to Dashboard
                    </a>
                </p>
            </div>
        </div>
    </div>

</x-layouts.app>

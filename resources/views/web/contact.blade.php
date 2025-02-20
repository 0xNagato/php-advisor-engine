<x-layouts.web>
    <x-slot name="title">Contact Us - PRIMA</x-slot>

    <section class="mt-40 max-w-[1320px] mx-auto px-6">
        <div class="flex flex-col lg:flex-row gap-12">
            <!-- Contact Info Section -->
            <div class="flex-1">
                <div
                    class="inline-flex bg-white border-[1px] border-solid border-[#D7D7D7] text-gray-800 px-3 py-2 rounded-full items-center shadow-sm mb-8 justify-center mx-auto">
                    <x-ri-phone-line class="w-4 h-4 mr-2" />
                    <span class="text-lg font-semibold">Contact Us</span>
                </div>

                <!-- Align text to the left for smaller and larger screens -->
                <div class="text-left mb-8">
                    <h2 class="text-3xl font-bold mb-4">Get in Touch with PRIMA</h2>
                    <p class="text-lg mb-8 text-[#565656]">
                        We’d love to hear from you! Whether you have questions,
                        feedback, or need assistance, our team is here to help.
                    </p>
                </div>

                <!-- Email Section -->
                <div
                    class="flex items-center justify-between bg-white border-[1px] border-solid border-[#E6E9EE] rounded-2xl shadow-lg p-4 mb-6 group transition-all duration-500 ease-in-out transform hover:scale-110">
                    <!-- Left Side: Icon and Text -->
                    <div class="flex items-center space-x-4">
                        <div class="p-4 rounded-full text-white bg-gradient-to-r from-[#F451A5] to-[#FD8FAB]">
                            <x-ri-mail-line class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-md text-[#666]">You can email us here</p>
                            <a href="mailto:hello@primavip.co" class="font-semibold text-lg text-gray-700">hello@primavip.co</a>
                        </div>
                    </div>
                    <!-- Right Side: External Link Icon -->
                    <div class="bg-[#E5E5E5] p-3 rounded-full">
                        <x-ri-arrow-right-up-line class="w-6 h-6" />
                    </div>
                </div>

                <!-- Call Section -->
                <div
                    class="flex items-center justify-between bg-white border-[1px] border-solid border-[#E6E9EE] rounded-2xl shadow-lg p-4 mb-6 group transition-all duration-500 ease-in-out transform hover:scale-110">
                    <!-- Left Side: Icon and Text -->
                    <div class="flex items-center space-x-4">
                        <div class="p-4 rounded-full text-white bg-gradient-to-r from-[#03AADD] to-[#5046E5]">
                            <x-ri-phone-line class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-md text-[#666]">Or give us a call</p>
                            <a href="tel:+0123 456789" class="font-semibold text-lg text-gray-700">+0123 456789</a>
                        </div>
                    </div>
                    <!-- Right Side: External Link Icon -->
                    <div class="bg-[#E5E5E5] p-3 rounded-full">
                        <x-ri-arrow-right-up-line class="w-6 h-6" />
                    </div>
                </div>

                <!-- Location Section -->
                <div
                    class="flex items-center justify-between bg-white border-[1px] border-solid border-[#E6E9EE] rounded-2xl shadow-lg p-4 mb-6 group transition-all duration-500 ease-in-out transform hover:scale-110">
                    <!-- Left Side: Icon and Text -->
                    <div class="flex items-center space-x-4">
                        <div class="p-4 rounded-full text-white bg-gradient-to-r from-[#2B5D89] to-[#58AC70]">
                            <x-gmdi-location-on-o class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-md text-[#666]">Locations</p>
                            <a href="https://maps.app.goo.gl/ZZctKopgV4LoXckW9" target="_blank"
                               class="font-semibold text-lg text-gray-700">Get Directions</a>
                        </div>
                    </div>
                    <!-- Right Side: External Link Icon -->
                    <div class="bg-[#E5E5E5] p-3 rounded-full">
                        <x-ri-arrow-right-up-line class="w-6 h-6" />
                    </div>
                </div>

            </div>

            <div class="flex-1 bg-white p-6 rounded-lg shadow-lg">
                <h3 class="text-xl font-semibold mb-4">Send Us a Message</h3>
                <p class="text-lg mb-6">Use our convenient contact form to reach out with questions, feedback, or
                    collaboration inquiries.</p>

                <form action="#" method="POST">
                    <!-- Name and Email Inputs Side by Side -->
                    <div class="flex space-x-6 mb-4">
                        <div class="flex-1">
                            <input type="text" name="name" placeholder="Name"
                                   class="w-full px-4 py-3 rounded-2xl bg-[#F6F7F9] border-transparent text-[#343434] focus:border-[#333] focus:outline-none focus:ring-2 focus:ring-[#5046E5]"
                                   required>
                        </div>
                        <div class="flex-1">
                            <input type="email" name="email" placeholder="Email"
                                   class="w-full px-4 py-3 rounded-2xl bg-[#F6F7F9] border-transparent text-[#343434] focus:border-[#333] focus:outline-none focus:ring-2 focus:ring-[#5046E5]"
                                   required>
                        </div>
                    </div>

                    <!-- Message Input -->
                    <div class="mb-4">
            <textarea name="message" placeholder="Message"
                      class="w-full px-4 py-3 rounded-2xl bg-[#F6F7F9] border-transparent text-[#343434] focus:border-[#333] focus:outline-none focus:ring-2 focus:ring-[#5046E5]"
                      rows="6" required></textarea>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit"
                            class="w-full bg-[#5046E5] text-white px-6 py-3 rounded-md hover:bg-opacity-90">
                        Send Message
                    </button>
                </form>
            </div>


        </div>
    </section>

@include('web.banner-become-part')

</x-layouts.web>

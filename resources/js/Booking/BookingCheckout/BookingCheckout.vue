<!--suppress TypeScriptCheckImport -->
<script setup lang="ts">
import { onMounted, ref } from 'vue';

import {
  Stripe,
  loadStripe,
  StripeElements,
  StripePaymentElementOptions,
  Appearance,
} from '@stripe/stripe-js';

import { Mail, Download } from 'lucide-vue-next';

interface Wire {
  createPaymentIntent: () => Promise<string>;
  completeBooking: (
    paymentIntentId: string,
    additionalData: {
      concierge_referral_type?: string | null;
      firstName: string;
      lastName: string;
      email: string;
      phone: string;
      notes: string;
    },
  ) => Promise<{ success: boolean; message: string }>;
  emailInvoice: () => Promise<void>;
  getDownloadInvoiceUrl: () => Promise<string>;
  checkForExistingBooking: (data: { phone: string }) => Promise<{
    error?: string;
    message?: string;
  } | null>;
  submitCustomerMessage: (
    message: string,
    phone: string,
  ) => Promise<{
    success: boolean;
    message: string;
  }>;
  formatPhoneNumber: (phone: string) => Promise<{
    success: boolean;
    formattedNumber: string;
    message?: string;
  }>;
}

interface MingleData {
  stripeKey: string;
  expiresAt: string;
  status: string;
  allowedPaymentMethods: string[];
  formData: {
    firstName: string;
    lastName: string;
    email: string;
    phone: string;
    notes: string;
  };
  vipCode?: string;
  totalWithTaxesInCents: number;
  isOmakase: boolean;
  omakaseDetails: string;
  minimumSpendPerGuest?: number;
}

interface Props {
  wire: Wire;
  mingleData: MingleData;
}

const { wire, mingleData } = defineProps<Props>();

const clientSecret = ref('');
const stripe = ref<Stripe | null>(null);
const elements = ref<StripeElements | null>(null);
const isLoading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

const firstName = ref(mingleData.formData.firstName);
const lastName = ref(mingleData.formData.lastName);
const email = ref(mingleData.formData.email);
const phone = ref(mingleData.formData.phone);
const notes = ref(mingleData.formData.notes);
const phoneError = ref('');

const hasExpired = ref(false);
const formattedTime = ref('');

const updateTimer = () => {
  const now = new Date();
  const expiresAt = new Date(mingleData.expiresAt);
  const timeRemaining = expiresAt.getTime() - now.getTime();

  if (timeRemaining > 0) {
    const minutes = Math.floor(timeRemaining / 60000);
    const seconds = Math.floor((timeRemaining % 60000) / 1000);
    formattedTime.value = `${minutes}:${seconds.toString().padStart(2, '0')}`;
  } else if (!isBookingSuccessful.value) {
    hasExpired.value = true;
    clearInterval(timerInterval);
  }
};

let timerInterval: ReturnType<typeof setInterval>;

const isBookingSuccessful = ref(false);

const showMultipleBookingModal = ref(false);
const customerMessage = ref('');

const validatePhone = (): boolean => {
  const phoneInput = document.querySelector('#phone') as HTMLInputElement | null;
  if (!phoneInput) return false;

  const number = phoneInput.value.trim();
  if (!number) {
    phoneError.value = 'Phone number is required';
    return false;
  }

  // If we already have a validated phone value, just use that
  if (phone.value && phone.value.startsWith('+')) {
    return true;
  }

  // Let the server validate the number - don't make assumptions about length
  phoneError.value = 'Please enter a valid phone number';
  return false;
};

const formatPhoneOnBlur = async () => {
  const phoneInput = document.querySelector('#phone') as HTMLInputElement | null;
  if (!phoneInput) return;

  const number = phoneInput.value.trim();
  if (!number) {
    // Don't set error for empty field - browser will handle required validation
    return;
  }

  try {
    isLoading.value = true; // Show loading state
    const result = await wire.formatPhoneNumber(number);

    if (result.success && result.formattedNumber) {
      // Store the clean E.164 format for submission
      phone.value = result.formattedNumber;

      // Update the input value with the formatted version for better user experience
      phoneInput.value = result.formattedNumber;

      phoneError.value = '';
    } else {
      // Server returned an error or empty formatted number - use the server's message or a default
      phoneError.value = result.message || 'Please enter a valid phone number that can receive SMS';
      phone.value = ''; // Clear the phone value since it's invalid
    }
  } catch (error) {
    console.error('Error formatting phone number:', error);
    phoneError.value = 'Unable to validate phone number. Please try again.';
    phone.value = ''; // Clear the phone value since validation failed
  } finally {
    isLoading.value = false; // Hide loading state
  }
};

const submitCustomerMessage = async () => {
  if (!customerMessage.value || !phone.value) return;

  if (!validatePhone()) return;

  isLoading.value = true;
  try {
    const response = await wire.submitCustomerMessage(
      customerMessage.value,
      phone.value,
    );
    if (response.success) {
      showMultipleBookingModal.value = false;
      successMessage.value = response.message;
      customerMessage.value = '';
    } else {
      errorMessage.value = response.message;
    }
  } catch (error) {
    console.error('Error submitting message:', error);
    errorMessage.value = 'An error occurred while sending your message.';
  } finally {
    isLoading.value = false;
  }
};

onMounted(async () => {
  if (mingleData.status === 'confirmed') {
    isBookingSuccessful.value = true;
  }

  updateTimer();
  timerInterval = setInterval(updateTimer, 1000);

  if (!hasExpired.value && mingleData.totalWithTaxesInCents > 0) {
    try {
      stripe.value = await loadStripe(mingleData.stripeKey);
      clientSecret.value = await wire.createPaymentIntent();

      const appearance: Appearance = {
        rules: {},
        variables: {
          colorPrimary: '#4f46e5',
        },
      };

      const options: StripePaymentElementOptions = {
        layout: {
          type: 'accordion',
        },
        paymentMethodOrder: mingleData.allowedPaymentMethods,
        wallets: {
          applePay: 'auto',
          googlePay: 'auto',
        },
      };

      if (stripe.value) {
        elements.value = stripe.value.elements({
          clientSecret: clientSecret.value,
          appearance,
          loader: 'auto'
        });

        const paymentElement = elements.value.create('payment', options);
        paymentElement.mount('#payment-element');
      }
    } catch (error) {
      console.error('Error initializing Stripe:', error);
      errorMessage.value =
        'Unable to initialize payment form. Please try again.';
    }
  }

  const phoneInput = document.querySelector(
    '#phone',
  ) as HTMLInputElement | null;
  if (phoneInput) {
    // If we have an initial phone value, set it
    if (mingleData.formData.phone) {
      phoneInput.value = mingleData.formData.phone;
      phone.value = mingleData.formData.phone;
    }

    // Add blur handler to format the phone number using server-side validation
    phoneInput.addEventListener('blur', formatPhoneOnBlur);

    // Add input handler to clear errors
    phoneInput.addEventListener('input', function() {
      phoneError.value = '';
    });
  }

  try {
    downloadInvoiceUrl.value = await wire.getDownloadInvoiceUrl();
  } catch (error) {
    console.error('Error fetching download invoice URL:', error);
  }
});

const agreeToText = ref(true);
const agreeToArrival = ref(true);
const agreeToMinimumSpend = ref(true);

const handleSubmit = async (event: Event) => {
  event.preventDefault();
  errorMessage.value = '';
  phoneError.value = '';

  // Get the phone input for validation
  const phoneInput = document.querySelector('#phone') as HTMLInputElement | null;
  if (!phoneInput || !phoneInput.value.trim()) {
    // Don't set error for empty field - browser will handle required validation
    return;
  }

  // If we don't already have a validated phone, try to format it now
  if (!phone.value || !phone.value.startsWith('+')) {
    try {
      isLoading.value = true;
      const result = await wire.formatPhoneNumber(phoneInput.value.trim());

      if (result.success && result.formattedNumber) {
        phone.value = result.formattedNumber;
        phoneInput.value = result.formattedNumber; // Update the input display
      } else {
        phoneError.value = result.message || 'Please enter a valid phone number that can receive SMS';
        isLoading.value = false;
        return;
      }
    } catch (e) {
      console.error("Error formatting phone during submission:", e);
      phoneError.value = 'Please enter a valid phone number that can receive SMS';
      isLoading.value = false;
      return;
    }
  }

  // Final check - if still no valid phone number, return error
  if (!phone.value || !phone.value.startsWith('+')) {
    phoneError.value = 'Please enter a valid phone number that can receive SMS';
    return;
  }

  isLoading.value = true;

  try {
    const bookingCheck = await wire.checkForExistingBooking({
      phone: phone.value,
    });

    if (bookingCheck?.error === 'multiple_booking') {
      showMultipleBookingModal.value = true;
      errorMessage.value = bookingCheck.message ?? 'Multiple booking detected';
      isLoading.value = false;
      return;
    }

    if (mingleData.totalWithTaxesInCents === 0) {
      const additionalData = {
        firstName: firstName.value,
        lastName: lastName.value,
        email: email.value,
        phone: phone.value,
        notes: notes.value,
      };

      const result = await wire.completeBooking('', additionalData);
      if (result.success) {
        successMessage.value = 'Booking successful! ' + result.message;
        isBookingSuccessful.value = true;
      } else {
        errorMessage.value = 'Booking failed: ' + result.message;
      }
    } else {
      if (!stripe.value || !elements.value) {
        console.error('Stripe not initialized');
        return;
      }

      try {
        const { error, paymentIntent } = await stripe.value.confirmPayment({
          elements: elements.value,
          confirmParams: {
            return_url: window.location.href,
            payment_method_data: {
              billing_details: {
                name: `${firstName.value} ${lastName.value}`,
                email: email.value,
                phone: phone.value
              }
            }
          },
          redirect: 'if_required',
        });

        if (error) {
          // More specific handling for different error types
          if (error.type === 'validation_error') {
            errorMessage.value = 'Please check your payment information and try again.';
          } else if (error.type === 'card_error') {
            errorMessage.value = error.message || 'Your card was declined. Please try a different payment method.';
          } else if (error.message && error.message.includes('Apple Pay')) {
            errorMessage.value = 'Something went wrong. Unable to show Apple Pay. Please choose a different payment method and try again.';
          } else {
            errorMessage.value = error.message || 'An error occurred during payment.';
          }
        } else if (paymentIntent) {
          const additionalData = {
            firstName: firstName.value,
            lastName: lastName.value,
            email: email.value,
            phone: phone.value,
            notes: notes.value,
          };

          const result = await wire.completeBooking(
            paymentIntent.id,
            additionalData,
          );
          if (result.success) {
            successMessage.value = 'Payment successful! ' + result.message;
            isBookingSuccessful.value = true;
          } else {
            errorMessage.value =
              'Payment processed, but booking failed: ' + result.message;
          }
        }
      } catch (stripeError) {
        console.error('Stripe exception:', stripeError);
        errorMessage.value = 'Payment processing failed. Please try again or use a different payment method.';
      }
    }
  } catch (error) {
    console.error('Submission error:', error);
    errorMessage.value = 'An unexpected error occurred. Please try again.';
  } finally {
    isLoading.value = false;
  }
};

const downloadInvoiceUrl = ref('');

const emailInvoice = async () => {
  try {
    await wire.emailInvoice();
  } catch (error) {
    console.error('Error emailing invoice:', error);
  }
};
</script>

<template>
  <div class="w-full">
    <template v-if="hasExpired">
      <h1
        class="text-2xl font-semibold tracking-tight text-center dm-serif text-gray-950 dark:text-white sm:text-3xl"
      >
        Reservation Expired
      </h1>
      <p class="my-4 text-center">
        Sorry, this payment link is expired.
        <template v-if="mingleData.vipCode">
          Please return to the availability calendar to try again.
        </template>
        <template v-else>
          Please consult with your PRIMA Concierge to request a new payment
          link.
        </template>
      </p>
      <div v-if="mingleData.vipCode" class="flex justify-center">
        <a
          :href="`/v/${mingleData.vipCode}`"
          class="px-4 py-2 font-semibold text-center text-white bg-indigo-600 rounded hover:bg-indigo-700"
        >
          Return to Availability Calendar
        </a>
      </div>
    </template>
    <template v-else-if="isBookingSuccessful">
      <h1
        class="mb-2 text-2xl tracking-tight text-center dm-serif font-semi text-gray-950 dark:text-white sm:text-3xl"
      >
        Thank you for your reservation!
      </h1>
      <p class="mb-4 text-center">
        Your reservation request has been received. Please check your phone for
        a text confirmation. We are notifying the venue now.
      </p>
      <p class="mb-4 font-semibold text-center">Thank you for using PRIMA!</p>
      <div class="flex justify-center space-x-4">
        <button
          class="flex items-center justify-center w-1/2 px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded hover:bg-indigo-700"
          @click="emailInvoice"
        >
          <Mail class="mr-2 size-4" />
          Email Invoice
        </button>
        <a
          :href="downloadInvoiceUrl"
          class="flex items-center justify-center w-1/2 px-4 py-2 text-sm font-semibold text-center text-white bg-indigo-600 rounded hover:bg-indigo-700"
        >
          <Download class="mr-2 size-4" />
          Download PDF
        </a>
      </div>
    </template>
    <template v-else>
      <h1
        class="text-2xl font-semibold tracking-tight text-center dm-serif text-gray-950 dark:text-white sm:text-3xl"
      >
        Secure Your Reservation
      </h1>
      <p class="mb-4 text-center">
        {{
          mingleData.totalWithTaxesInCents > 0
            ? 'Enter Payment Information To Confirm.'
            : 'Enter Contact Information To Confirm.'
        }}
      </p>
      <p class="mb-4 text-xl font-semibold text-center">
        Time Remaining: {{ formattedTime }}
      </p>
      <div v-if="mingleData.isOmakase" class="mb-4 text-center">
        <p
          class="p-2 text-sm font-semibold text-indigo-600 border border-indigo-200 rounded-lg bg-indigo-50"
        >
          {{ mingleData.omakaseDetails }}
        </p>
      </div>
      <div v-if="mingleData.minimumSpendPerGuest" class="mb-4 text-center">
        <p
          class="p-2 text-sm font-semibold text-blue-700 border border-blue-200 rounded-lg bg-blue-50"
        >
          <strong>Important:</strong> This reservation requires a ${{
            mingleData.minimumSpendPerGuest
          }}
          per diner minimum spend. Booking fees do not apply toward minimum
          spend or restaurant bill.
        </p>
      </div>
      <form class="w-full" @submit.prevent="handleSubmit">
        <div class="flex mb-2 space-x-2">
          <div class="flex-1">
            <label for="first-name" class="sr-only">First Name</label>
            <input
              id="first-name"
              v-model="firstName"
              type="text"
              placeholder="First Name"
              class="focus:ring-opacity/50 w-full rounded-md border-gray-300 shadow-sm transition duration-200 ease-in-out focus:border-[#A7A4F2] focus:ring focus:ring-[#D1CFF5] sm:text-sm"
              required
            />
          </div>
          <div class="flex-1">
            <label for="last-name" class="sr-only">Last Name</label>
            <input
              id="last-name"
              v-model="lastName"
              type="text"
              placeholder="Last Name"
              class="focus:ring-opacity/50 w-full rounded-md border-gray-300 shadow-sm transition duration-200 ease-in-out focus:border-[#A7A4F2] focus:ring focus:ring-[#D1CFF5] sm:text-sm"
              required
            />
          </div>
        </div>
        <div class="mb-2">
          <label for="phone" class="sr-only">Phone</label>
          <input
            id="phone"
            type="tel"
            placeholder="+15551234567"
            class="focus:ring-opacity/50 w-full rounded-md border-gray-300 shadow-sm transition duration-200 ease-in-out focus:border-[#A7A4F2] focus:ring focus:ring-[#D1CFF5] sm:text-sm"
            required
          />
          <p v-if="phoneError" class="mt-1 text-sm font-medium text-red-600">{{ phoneError }}</p>
        </div>
        <div class="mb-2">
          <label for="email" class="sr-only">Email</label>
          <input
            id="email"
            v-model="email"
            type="email"
            placeholder="Email (Optional)"
            class="focus:ring-opacity/50 w-full rounded-md border-gray-300 shadow-sm transition duration-200 ease-in-out focus:border-[#A7A4F2] focus:ring focus:ring-[#D1CFF5] sm:text-sm"
          />
        </div>
        <div class="mb-2">
          <label for="special-notes" class="sr-only">Special Notes</label>
          <textarea
            id="special-notes"
            v-model="notes"
            placeholder="Notes/Special Requests (Optional)"
            class="focus:ring-opacity/50 w-full rounded-md border-gray-300 shadow-sm transition duration-200 ease-in-out focus:border-[#A7A4F2] focus:ring focus:ring-[#D1CFF5] sm:text-sm"
            rows="3"
          ></textarea>
        </div>

        <div
          v-if="mingleData.totalWithTaxesInCents > 0"
          id="payment-element"
          class="w-full"
        />

        <div class="mx-2 mt-4">
          <label class="flex items-center">
            <input
              v-model="agreeToText"
              type="checkbox"
              class="text-indigo-600 rounded form-checkbox size-4"
            />
            <span class="ml-2 text-sm text-gray-700">
              I agree to receive my reservation confirmation via text message.
            </span>
          </label>
        </div>
        <div v-if="mingleData.minimumSpendPerGuest" class="mx-2 mt-2">
          <label class="flex items-center">
            <input
              v-model="agreeToMinimumSpend"
              type="checkbox"
              required
              class="text-indigo-600 rounded form-checkbox size-4"
            />
            <span class="ml-2 text-sm text-gray-700">
              I understand that this restaurant will require a ${{
                mingleData.minimumSpendPerGuest
              }}
              per diner minimum spend. Booking fees do not apply towards
              restaurant bill.
            </span>
          </label>
        </div>
        <div v-if="!mingleData.isOmakase" class="mx-2 mt-2">
          <label class="flex items-center">
            <input
              v-model="agreeToArrival"
              type="checkbox"
              required
              class="text-indigo-600 rounded form-checkbox size-4"
            />
            <span class="ml-2 text-sm text-gray-700">
              <template v-if="mingleData.totalWithTaxesInCents > 0">
                <span class="font-semibold">
                  I understand that this fee is for a prime time reservation and
                  is not applied towards my bill at the restaurant.
                </span>
              </template>
              <template v-else>
                I am booking a real reservation and will arrive within 15
                minutes of the reserved time.
              </template>
            </span>
          </label>
        </div>
        <button
          type="submit"
          :disabled="isLoading"
          class="w-full px-4 py-2 mt-4 font-semibold text-white bg-indigo-600 rounded hover:bg-indigo-700 disabled:opacity-50"
        >
          {{ isLoading ? 'Processing...' : 'Complete Reservation' }}
        </button>
        <a
          v-if="mingleData.vipCode"
          :href="`/v/${mingleData.vipCode}`"
          class="block w-full px-4 py-2 mt-4 font-semibold text-center text-white bg-gray-700 rounded hover:bg-gray-800"
        >
          Return to Availability Calendar
        </a>
      </form>
      <div v-if="errorMessage" class="mt-4 text-red-500">
        {{ errorMessage }}
      </div>
    </template>
    <div
      v-if="showMultipleBookingModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0"
    >
      <div
        class="fixed inset-0 bg-black/50"
        @click="showMultipleBookingModal = false"
      ></div>
      <div class="relative w-full max-w-md p-6 bg-white rounded-lg">
        <h3 class="mb-4 text-lg font-medium">Multiple Booking Request</h3>
        <p class="mb-4 text-sm text-gray-600">
          You already have a non-prime reservation for this day. Please let us
          know why you're trying to make another reservation and our team will
          review your request.
        </p>
        <textarea
          v-model="customerMessage"
          class="w-full p-3 mb-4 text-sm border rounded"
          rows="3"
          placeholder="Please explain why you need another reservation..."
          required
        ></textarea>
        <div class="flex justify-end gap-x-3">
          <button
            class="px-4 py-2 text-sm text-gray-600 border rounded hover:bg-gray-50"
            @click="showMultipleBookingModal = false"
          >
            Cancel
          </button>
          <button
            class="px-4 py-2 text-sm text-white bg-indigo-600 rounded hover:bg-indigo-700"
            :disabled="isLoading"
            @click="submitCustomerMessage"
          >
            {{ isLoading ? 'Submitting...' : 'Submit Request' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style>
/* Remove intlTelInput styling */
</style>


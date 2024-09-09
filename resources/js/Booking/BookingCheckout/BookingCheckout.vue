<!--suppress TypeScriptCheckImport -->
<script setup lang="ts">
import { onMounted, ref } from 'vue';
import intlTelInput from 'intl-tel-input';
import 'intl-tel-input/build/css/intlTelInput.css';

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

onMounted(async () => {
  if (mingleData.status === 'confirmed') {
    isBookingSuccessful.value = true;
  }

  updateTimer();
  timerInterval = setInterval(updateTimer, 1000);

  if (!hasExpired.value) {
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
    intlTelInput(phoneInput, {
      initialCountry: 'us',
      utilsScript:
        'https://cdn.jsdelivr.net/npm/intl-tel-input@24.3.6/build/js/utils.js',
    });
  }

  try {
    downloadInvoiceUrl.value = await wire.getDownloadInvoiceUrl();
  } catch (error) {
    console.error('Error fetching download invoice URL:', error);
  }
});

const agreeToText = ref(true);

const handleSubmit = async (event: Event) => {
  event.preventDefault();

  if (!stripe.value || !elements.value) {
    console.error('Stripe not initialized');
    return;
  }

  isLoading.value = true;
  errorMessage.value = '';

  try {
    const { error, paymentIntent } = await stripe.value.confirmPayment({
      elements: elements.value,
      confirmParams: {
        return_url: window.location.href,
      },
      redirect: 'if_required',
    });

    if (error) {
      errorMessage.value = error.message || 'An error occurred during payment.';
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
  } catch (error) {
    console.error('Payment error:', error);
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
        class="dm-serif text-center text-2xl font-semibold tracking-tight text-gray-950 dark:text-white sm:text-3xl"
      >
        Reservation Expired
      </h1>
      <p class="mb-4 text-center">
        Sorry, this payment link is expired. Please consult with your PRIMA
        Concierge to request a new payment link.
      </p>
    </template>
    <template v-else-if="isBookingSuccessful">
      <h1
        class="dm-serif font-semi mb-2 text-center text-2xl tracking-tight text-gray-950 dark:text-white sm:text-3xl"
      >
        Thank you for your reservation!
      </h1>
      <p class="mb-4 text-center">
        Your reservation request has been received. Please check your phone for
        a text confirmation. We are notifying the venue now.
      </p>
      <p class="mb-4 text-center font-semibold">Thank you for using PRIMA!</p>
      <div class="flex justify-center space-x-4">
        <button
          class="flex w-1/2 items-center justify-center rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
          @click="emailInvoice"
        >
          <Mail class="mr-2 size-4" /> Email Invoice
        </button>
        <a
          :href="downloadInvoiceUrl"
          class="flex w-1/2 items-center justify-center rounded bg-indigo-600 px-4 py-2 text-center text-sm font-semibold text-white hover:bg-indigo-700"
        >
          <Download class="mr-2 size-4" /> Download PDF
        </a>
      </div>
    </template>
    <template v-else>
      <h1
        class="dm-serif text-center text-2xl font-semibold tracking-tight text-gray-950 dark:text-white sm:text-3xl"
      >
        Secure Your Reservation
      </h1>
      <p class="mb-4 text-center">Enter Payment Information To Confirm.</p>
      <p class="mb-4 text-center text-xl font-semibold">
        Time Remaining: {{ formattedTime }}
      </p>
      <form class="w-full" @submit.prevent="handleSubmit">
        <div class="mb-2 flex space-x-2">
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
          <label for="email" class="sr-only">Email</label>
          <input
            id="email"
            v-model="email"
            type="email"
            placeholder="Email"
            class="focus:ring-opacity/50 w-full rounded-md border-gray-300 shadow-sm transition duration-200 ease-in-out focus:border-[#A7A4F2] focus:ring focus:ring-[#D1CFF5] sm:text-sm"
            required
          />
        </div>
        <div class="mb-2">
          <label for="phone" class="sr-only">Phone</label>
          <input
            id="phone"
            v-model="phone"
            type="tel"
            placeholder="Phone"
            class="focus:ring-opacity/50 w-full rounded-md border-gray-300 shadow-sm transition duration-200 ease-in-out focus:border-[#A7A4F2] focus:ring focus:ring-[#D1CFF5] sm:text-sm"
            required
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

        <div id="payment-element" class="w-full" />

        <div class="mx-2 mt-4">
          <label class="flex items-center">
            <input
              v-model="agreeToText"
              type="checkbox"
              class="form-checkbox size-4 rounded text-indigo-600"
            />
            <span class="ml-2 text-sm text-gray-700">
              I agree to receive my reservation confirmation via text message.
            </span>
          </label>
        </div>
        <button
          type="submit"
          :disabled="isLoading"
          class="mt-4 w-full rounded bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"
        >
          {{ isLoading ? 'Processing...' : 'Complete Reservation' }}
        </button>
      </form>
      <div v-if="errorMessage" class="mt-4 text-red-500">
        {{ errorMessage }}
      </div>
    </template>
  </div>
</template>

<style>
.iti {
  width: 100% !important;
}
</style>

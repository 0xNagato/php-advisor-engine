<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\VenueInvoice;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class CreateStripeVenueInvoice
{
    use AsAction;

    public function __construct(private StripeClient $stripeClient) {}

    /**
     * Create a Stripe invoice for a venue invoice
     * This only creates an invoice in draft state with a URL that can be viewed
     * It does NOT send the invoice to customers via Stripe
     *
     * @param  VenueInvoice  $venueInvoice  The venue invoice to create a Stripe invoice for
     * @param  string|null  $customerEmail  The email of the customer to associate with the invoice
     * @param  string|null  $customerId  The Stripe customer ID if already exists
     * @return array{invoice_id: string, invoice_url: string}|null The Stripe invoice ID and URL or null if creation failed
     */
    public function handle(
        VenueInvoice $venueInvoice,
        ?string $customerEmail = null,
        ?string $customerId = null
    ): ?array {
        try {
            // Skip invoice creation if the amount is positive or zero
            // Only create invoices when venue owes PRIMA money (negative amount)
            if ((int) $venueInvoice->total_amount >= 0) {
                Log::info('Skipping Stripe invoice creation as venue invoice amount is not negative', [
                    'venue_invoice_id' => $venueInvoice->id,
                    'total_amount' => $venueInvoice->total_amount,
                ]);

                return null;
            }

            // If no customer ID is provided, try to find or create one
            if (! $customerId && $customerEmail) {
                $customerData = [
                    'email' => $customerEmail,
                    'name' => $venueInvoice->venue_group_id
                        ? $venueInvoice->venueGroup->name
                        : $venueInvoice->venue->name,
                    'metadata' => [
                        'venue_id' => $venueInvoice->venue_id,
                        'venue_group_id' => $venueInvoice->venue_group_id,
                    ],
                ];

                // Search for existing customer by email
                $customers = $this->stripeClient->customers->search([
                    'query' => "email:'{$customerEmail}'",
                ]);

                if (count($customers->data) > 0) {
                    $customerId = $customers->data[0]->id;
                } else {
                    // Create a new customer
                    $customer = $this->stripeClient->customers->create($customerData);
                    $customerId = $customer->id;
                }
            }

            if (! $customerId) {
                Log::error('Unable to create Stripe invoice: No customer ID or email provided', [
                    'venue_invoice_id' => $venueInvoice->id,
                ]);

                return null;
            }

            // Calculate the amount for the invoice item
            $amount = (int) abs((int) $venueInvoice->total_amount);

            // Step 1: Create a draft invoice first
            $invoice = $this->stripeClient->invoices->create([
                'customer' => $customerId,
                'collection_method' => 'send_invoice', // This is key - it creates a payment link when finalized
                'days_until_due' => 30, // Required for 'send_invoice'
                'auto_advance' => false, // Explicitly prevent automatic processing
                'description' => "PRIMA - Venue Invoice #{$venueInvoice->invoice_number}",
                'metadata' => [
                    'venue_invoice_id' => $venueInvoice->id,
                    'venue_id' => $venueInvoice->venue_id,
                    'venue_group_id' => $venueInvoice->venue_group_id,
                ],
            ]);

            // Step 2: Create an invoice item directly attached to this invoice
            $invoiceItem = $this->stripeClient->invoiceItems->create([
                'customer' => $customerId,
                'amount' => $amount, // Amount is already in cents
                'currency' => $venueInvoice->currency ?? 'USD',
                'invoice' => $invoice->id, // Directly attach to the invoice
                'description' => "Invoice {$venueInvoice->invoice_number} - Period: {$venueInvoice->start_date->format('M d, Y')} to {$venueInvoice->end_date->format('M d, Y')}",
            ]);

            // Step 3: Finalize the invoice
            $finalizedInvoice = $this->stripeClient->invoices->finalizeInvoice($invoice->id, [
                'auto_advance' => false,
            ]);

            // Return the invoice ID and hosted invoice URL
            return [
                'invoice_id' => $finalizedInvoice->id,
                'invoice_url' => $finalizedInvoice->hosted_invoice_url,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe invoice creation failed', [
                'venue_invoice_id' => $venueInvoice->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}

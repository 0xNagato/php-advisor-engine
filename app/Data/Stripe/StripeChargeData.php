<?php

namespace App\Data\Stripe;

use Livewire\Wireable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class StripeChargeData extends Data implements Wireable
{
    use WireableData;

    public function __construct(
        public ?string                   $id,
        public ?bool                     $paid,
        public ?string                   $order,
        public ?int                      $amount,
        public ?string                   $object,
        public ?string                   $review,
        #[MapInputName('source')]
        public ?SourceData               $source,
        public ?string                   $status,
        public ?int                      $created,
        public ?string                   $dispute,
        public ?string                   $invoice,
        #[MapInputName('outcome')]
        public ?OutcomeData              $outcome,
        public ?bool                     $captured,
        public ?string                   $currency,
        public ?string                   $customer,
        public ?bool                     $disputed,
        public ?bool                     $livemode,
        public array                     $metadata,
        public ?bool                     $refunded,
        public ?string                   $shipping,
        public ?string                   $application,
        public ?string                   $description,
        public ?string                   $destination,
        public ?string                   $receiptUrl,
        public ?string                   $failureCode,
        public ?string                   $onBehalfOf,
        public ?array                    $fraudDetails,
        public ?string                   $receiptEmail,
        public ?string                   $transferData,
        public ?string                   $paymentIntent,
        public ?string                   $paymentMethod,
        public ?string                   $receiptNumber,
        public ?string                   $transferGroup,
        public ?int                      $amountCaptured,
        public ?int                      $amountRefunded,
        public ?string                   $applicationFee,
        #[MapInputName('billing_details')]
        public ?BillingDetailsData       $billingDetails,
        public ?string                   $failureMessage,
        public ?string                   $sourceTransfer,
        public ?string                   $balanceTransaction,
        public ?string                   $statementDescriptor,
        public ?string                   $applicationFeeAmount,
        #[MapInputName('payment_method_details')]
        public ?PaymentMethodDetailsData $paymentMethodDetails,
        public ?string                   $failureBalanceTransaction,
        public ?string                   $statementDescriptorSuffix,
        public ?string                   $calculatedStatementDescriptor
    )
    {
    }
}

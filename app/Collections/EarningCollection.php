<?php

namespace App\Collections;

use Illuminate\Database\Eloquent\Collection;

class EarningCollection extends Collection
{
    /**
     * Get the sum of the 'amount' field grouped by 'user_id' and 'type'.
     * Optionally includes user details if the 'user' relationship is loaded.
     */
    public function sumByUserAndType(): \Illuminate\Support\Collection
    {
        // Group items by user_id and type
        return $this->groupBy(fn ($item) => $item->user_id.'-'.$item->type)
            ->map(function ($groupedItems) {
                // Get the first item to check if 'user' is loaded and access other details
                $firstItem = $groupedItems->first();

                $result = [
                    'user_id' => $firstItem->user_id,
                    'type' => $firstItem->type,
                    'amount' => $groupedItems->sum('amount'),
                ];

                // Add user details if 'user' relationship is loaded
                if ($firstItem->relationLoaded('user')) {
                    $result['user'] = $firstItem->user;
                }

                return $result;
            });
    }

    /**
     * Get the sum of 'amount' for earnings where 'percentage_of' is 'refund'.
     */
    public function sumRefundedAmount(): float
    {
        return $this->where('percentage_of', 'refund')->sum('amount');
    }

    /**
     * Get the sum of 'amount' for earnings where 'percentage_of' is 'total_fee'.
     */
    public function sumTotalFeeAmount(): float
    {
        return $this->where('percentage_of', 'total_fee')->sum('amount');
    }

    /**
     * Calculate the remaining amount, which is the sum of
     * 'total_fee' and 'refund'.
     */
    public function calculateRemaining(): float
    {
        $totalFee = $this->sumTotalFeeAmount();
        $refunded = $this->sumRefundedAmount();

        return $totalFee + $refunded;
    }
}

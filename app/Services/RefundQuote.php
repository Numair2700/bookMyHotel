<?php

namespace App\Services;

/**
 * The refund a guest would receive for cancelling now. Computed without
 * changing anything so it can be shown before the guest confirms (NFR8).
 */
final class RefundQuote
{
    public function __construct(
        public readonly float $amount,
        public readonly float $percent,
        public readonly bool $withinFreeWindow,
        public readonly ?string $policyName,
        public readonly ?int $freeCancellationHours,
        public readonly float $penaltyPercentage,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'percent' => $this->percent,
            'within_free_window' => $this->withinFreeWindow,
            'policy_name' => $this->policyName,
            'free_cancellation_hours' => $this->freeCancellationHours,
            'penalty_percentage' => $this->penaltyPercentage,
        ];
    }
}

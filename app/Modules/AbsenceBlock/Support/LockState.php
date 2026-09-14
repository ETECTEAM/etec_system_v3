<?php

namespace App\Modules\AbsenceBlock\Support;

/**
 * The outcome of AbsenceBlockEvaluator for one student on one date.
 *
 * phase:
 *   none           - nothing in effect
 *   soft           - open 'absence' block, awaiting admin approval (locked)
 *   post_approval   - approved this cycle, extra-absence allowance running (not locked)
 *   hard           - open 'hard_lock' block, awaiting super_admin unlock (locked)
 */
final readonly class LockState
{
    public function __construct(
        public bool $locked,
        public string $phase,
        public ?string $forcedStatus,
        public ?string $reason,
        public ?int $blockId,
    ) {}

    public static function unlocked(string $phase = 'none'): self
    {
        return new self(false, $phase, null, null, null);
    }

    public static function locked(string $phase, string $reason, int $blockId): self
    {
        return new self(true, $phase, 'absent', $reason, $blockId);
    }

    public function toArray(): array
    {
        return [
            'locked' => $this->locked,
            'phase' => $this->phase,
            'forced_status' => $this->forcedStatus,
            'reason' => $this->reason,
            'block_id' => $this->blockId,
        ];
    }
}

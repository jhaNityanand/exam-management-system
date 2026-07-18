<?php

namespace App\Services\CandidateExam;

use App\Models\Exam;
use App\Models\ExamEntitlement;
use App\Models\ExamPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExamPaymentPlaceholderService
{
    public function hasActiveEntitlement(Exam $exam, User $user): bool
    {
        $entitlement = ExamEntitlement::query()
            ->where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->latest('id')
            ->first();

        return $entitlement?->isActive() ?? false;
    }

    /**
     * Placeholder checkout — marks payment paid and grants entitlement.
     *
     * @return array{payment: ExamPayment, entitlement: ExamEntitlement}
     */
    public function completePlaceholderPurchase(Exam $exam, User $user): array
    {
        return DB::transaction(function () use ($exam, $user) {
            $existing = ExamEntitlement::query()
                ->where('exam_id', $exam->id)
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if ($existing?->isActive()) {
                $payment = ExamPayment::query()
                    ->where('exam_id', $exam->id)
                    ->where('user_id', $user->id)
                    ->where('status', 'paid')
                    ->latest('id')
                    ->first();

                return [
                    'payment' => $payment ?? ExamPayment::query()->create([
                        'organization_id' => $exam->organization_id,
                        'exam_id' => $exam->id,
                        'user_id' => $user->id,
                        'entitlement_id' => $existing->id,
                        'provider' => 'placeholder',
                        'status' => 'paid',
                        'currency' => $exam->exam_currency ?: 'INR',
                        'amount' => $exam->exam_amount ?: 0,
                        'reference' => 'EXISTING-'.$existing->id,
                        'paid_at' => now(),
                    ]),
                    'entitlement' => $existing,
                ];
            }

            $entitlement = ExamEntitlement::query()->create([
                'organization_id' => $exam->organization_id,
                'exam_id' => $exam->id,
                'user_id' => $user->id,
                'source' => 'payment',
                'status' => 'active',
                'valid_from' => now(),
                'valid_until' => null,
                'meta' => ['via' => 'placeholder'],
            ]);

            $payment = ExamPayment::query()->create([
                'organization_id' => $exam->organization_id,
                'exam_id' => $exam->id,
                'user_id' => $user->id,
                'entitlement_id' => $entitlement->id,
                'provider' => 'placeholder',
                'status' => 'paid',
                'currency' => $exam->exam_currency ?: 'INR',
                'amount' => $exam->exam_amount ?: 0,
                'reference' => 'PH-'.Str::upper(Str::random(10)),
                'paid_at' => now(),
                'meta' => ['note' => 'Placeholder payment — gateway pending'],
            ]);

            return compact('payment', 'entitlement');
        });
    }
}

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
    /**
     * Demo / placeholder checkout gateway.
     *
     * Replace this class (or swap the binding in a service provider) with a real
     * payment gateway implementation that verifies webhooks before calling
     * grantEntitlement(). Controllers should keep depending on this service API.
     */
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
     * Simulate a successful payment and grant entitlement (demo only).
     *
     * @return array{payment: ExamPayment, entitlement: ExamEntitlement}
     */
    public function completePlaceholderPurchase(Exam $exam, User $user): array
    {
        return $this->grantEntitlement($exam, $user, [
            'provider' => 'placeholder',
            'meta' => ['note' => 'Demo simulated payment — replace with real gateway'],
        ]);
    }

    /**
     * Shared entitlement grant used by placeholder (and future gateways).
     *
     * @param  array{provider?: string, reference?: string, meta?: array<string, mixed>}  $options
     * @return array{payment: ExamPayment, entitlement: ExamEntitlement}
     */
    public function grantEntitlement(Exam $exam, User $user, array $options = []): array
    {
        return DB::transaction(function () use ($exam, $user, $options) {
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
                        'provider' => $options['provider'] ?? 'placeholder',
                        'status' => 'paid',
                        'currency' => $exam->exam_currency ?: 'INR',
                        'amount' => $exam->exam_amount ?: 0,
                        'reference' => $options['reference'] ?? ('EXISTING-'.$existing->id),
                        'paid_at' => now(),
                        'meta' => $options['meta'] ?? null,
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
                'meta' => ['via' => $options['provider'] ?? 'placeholder'],
            ]);

            $payment = ExamPayment::query()->create([
                'organization_id' => $exam->organization_id,
                'exam_id' => $exam->id,
                'user_id' => $user->id,
                'entitlement_id' => $entitlement->id,
                'provider' => $options['provider'] ?? 'placeholder',
                'status' => 'paid',
                'currency' => $exam->exam_currency ?: 'INR',
                'amount' => $exam->exam_amount ?: 0,
                'reference' => $options['reference'] ?? ('PH-'.Str::upper(Str::random(10))),
                'paid_at' => now(),
                'meta' => $options['meta'] ?? ['note' => 'Placeholder payment — gateway pending'],
            ]);

            return compact('payment', 'entitlement');
        });
    }
}

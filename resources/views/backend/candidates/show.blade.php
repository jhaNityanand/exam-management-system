@extends('backend.layouts.app')

@section('title', 'Candidate Details')
@section('page-title', 'Candidate Details')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Candidates', 'url' => route('admin.candidates.index')],
        ['label' => $candidate->name],
    ]" />
@endsection

@section('content')
@php
    $profile = $candidate->profile;
    $avatarMeta = user_avatar($candidate);
    $isActive = $candidate->status === 'active';
    $isTrashed = $candidate->trashed();
    $genderLabel = match ($profile?->gender) {
        'male' => 'Male',
        'female' => 'Female',
        'other' => 'Other',
        'prefer_not_to_say' => 'Prefer not to say',
        default => null,
    };
    $countries = [
        'IN' => 'India', 'US' => 'United States', 'GB' => 'United Kingdom',
        'CA' => 'Canada', 'AU' => 'Australia', 'AE' => 'United Arab Emirates', 'SG' => 'Singapore',
    ];
    $social = $profile?->social_links ?? [];
    $emailVerified = filled($candidate->email_verified_at);
    $mobileProvided = filled($profile?->phone);
    $hasDocs = count($verification_documents) > 0;
    $hasSelfie = collect($verification_documents)->contains(fn ($d) => in_array($d['type'] ?? '', ['selfie', 'webcam', 'identity'], true));

    $formatDuration = function (?int $seconds): string {
        if ($seconds === null || $seconds < 0) return '—';
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;
        if ($h > 0) return sprintf('%dh %02dm', $h, $m);
        if ($m > 0) return sprintf('%dm %02ds', $m, $s);
        return sprintf('%ds', $s);
    };

    $attemptStatusBadge = function (string $status): string {
        return match ($status) {
            'graded', 'submitted' => 'cand-badge cand-badge--success',
            'active', 'in_progress' => 'cand-badge cand-badge--info',
            'expired' => 'cand-badge cand-badge--warning',
            'abandoned' => 'cand-badge cand-badge--muted',
            default => 'cand-badge cand-badge--muted',
        };
    };
@endphp

<div class="space-y-6 cand-show">
    @if (session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
            {{ session('success') }}
            @if (session('generated_password'))
                <div class="mt-2 font-mono text-xs tracking-wide">
                    Temporary password: <strong>{{ session('generated_password') }}</strong>
                </div>
            @endif
        </div>
    @endif

    {{-- Top Action Banner --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-sm">
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-5">
            <div class="flex items-start sm:items-center gap-4 min-w-0">
                <div class="cand-show-avatar shrink-0" @if (! $avatar_url) style="background: {{ $avatarMeta['color'] }}; color: #fff" @endif>
                    @if ($avatar_url)
                        <img src="{{ $avatar_url }}" alt="{{ $candidate->name }}">
                    @else
                        <span>{{ $avatarMeta['initials'] }}</span>
                    @endif
                </div>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">ID #{{ $candidate->id }}</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $isActive ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400' }}">
                            {{ ucfirst($candidate->status) }}
                        </span>
                        @if ($isTrashed)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400">In Bin</span>
                        @endif
                        @if ($emailVerified)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-400">Email Verified</span>
                        @endif
                    </div>
                    <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white mt-1 truncate">{{ $candidate->name }}</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                        {{ $candidate->email }}
                        @if ($candidate->username)
                            <span class="mx-1.5 text-slate-300 dark:text-slate-600">·</span>
                            {{ '@'.$candidate->username }}
                        @endif
                    </p>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                        Profile {{ $completion['percent'] }}% complete
                        · Joined {{ optional($candidate->created_at)->format('d M Y') }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @unless ($isTrashed)
                    <a href="{{ route('admin.candidates.edit', $candidate) }}" class="panel-button-primary">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                        </svg>
                        Edit Candidate
                    </a>

                    <form id="toggle-status-form" action="{{ route('admin.candidates.toggle-status', $candidate) }}" method="POST" class="inline">
                        @csrf
                        @method('PATCH')
                        <button
                            type="button"
                            id="btn-toggle-status"
                            class="panel-button-secondary"
                            data-action="{{ $isActive ? 'deactivate' : 'activate' }}"
                            data-name="{{ $candidate->name }}"
                        >
                            {{ $isActive ? 'Deactivate' : 'Activate' }}
                        </button>
                    </form>

                    <button type="button" class="panel-button-secondary" id="btn-reset-password" data-modal-open="reset-password-modal">
                        Reset Password
                    </button>

                    <button type="button" class="panel-button-secondary" data-coming-soon-modal="invoice-coming-soon-modal">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Invoice
                    </button>

                    <a href="#exam-history" class="panel-button-secondary">View Exam Attempts</a>

                    @if ($feedback_count > 0)
                        <a href="#feedback-section" class="panel-button-secondary">View Feedback</a>
                    @endif

                    <form action="{{ route('admin.candidates.destroy', $candidate) }}" method="POST" class="inline" id="delete-candidate-form">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="panel-button-secondary text-rose-600 dark:text-rose-400" id="btn-delete-candidate">
                            Delete
                        </button>
                    </form>
                @else
                    <form action="{{ route('admin.candidates.restore', $candidate) }}" method="POST" class="inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="panel-button-primary">Restore Candidate</button>
                    </form>
                @endunless

                <a href="{{ route('admin.candidates.index') }}" class="panel-button-secondary">Back to List</a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        {{-- Main column --}}
        <div class="lg:col-span-8 space-y-6">
            {{-- Basic Information --}}
            <section class="cand-card">
                <h2 class="cand-card__title">Basic Information</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <span class="cand-meta-label">Full Name</span>
                        <span class="cand-meta-value">{{ $candidate->name }}</span>
                    </div>
                    <div>
                        <span class="cand-meta-label">Username</span>
                        <span class="cand-meta-value">{{ $candidate->username ?: '—' }}</span>
                    </div>
                    <div>
                        <span class="cand-meta-label">Email</span>
                        <span class="cand-meta-value break-all">{{ $candidate->email }}</span>
                    </div>
                    <div>
                        <span class="cand-meta-label">Mobile Number</span>
                        <span class="cand-meta-value">{{ $profile?->phone ?: '—' }}</span>
                    </div>
                    <div>
                        <span class="cand-meta-label">Gender</span>
                        <span class="cand-meta-value">{{ $genderLabel ?: '—' }}</span>
                    </div>
                    <div>
                        <span class="cand-meta-label">Date of Birth</span>
                        <span class="cand-meta-value">{{ optional($profile?->date_of_birth)->format('d M Y') ?: '—' }}</span>
                    </div>
                    <div>
                        <span class="cand-meta-label">Status</span>
                        <span class="cand-meta-value">{{ ucfirst($candidate->status) }}</span>
                    </div>
                    <div>
                        <span class="cand-meta-label">Registration Date</span>
                        <span class="cand-meta-value">{{ optional($candidate->created_at)->format('d M Y H:i') }}</span>
                    </div>
                    <div>
                        <span class="cand-meta-label">Last Login</span>
                        <span class="cand-meta-value">{{ $last_login ?: '—' }}</span>
                    </div>
                    <div>
                        <span class="cand-meta-label">Last Updated</span>
                        <span class="cand-meta-value">{{ optional($candidate->updated_at)->format('d M Y H:i') }}</span>
                    </div>
                    @if ($profile?->bio)
                        <div class="sm:col-span-2">
                            <span class="cand-meta-label">Bio</span>
                            <span class="cand-meta-value whitespace-pre-line">{{ $profile->bio }}</span>
                        </div>
                    @endif
                </div>
            </section>

            {{-- Verification --}}
            <section class="cand-card" id="verification-section">
                <h2 class="cand-card__title">Verification Information</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
                    <div class="cand-stat">
                        <span class="cand-stat__label">Email</span>
                        <span class="cand-stat__value {{ $emailVerified ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                            {{ $emailVerified ? 'Verified' : 'Unverified' }}
                        </span>
                    </div>
                    <div class="cand-stat">
                        <span class="cand-stat__label">Mobile</span>
                        <span class="cand-stat__value {{ $mobileProvided ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500' }}">
                            {{ $mobileProvided ? 'Provided' : 'Not provided' }}
                        </span>
                    </div>
                    <div class="cand-stat">
                        <span class="cand-stat__label">Identity</span>
                        <span class="cand-stat__value {{ $hasSelfie ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500' }}">
                            {{ $hasSelfie ? 'Selfie on file' : 'Not captured' }}
                        </span>
                    </div>
                    <div class="cand-stat">
                        <span class="cand-stat__label">Documents</span>
                        <span class="cand-stat__value">{{ $hasDocs ? count($verification_documents).' file(s)' : 'None' }}</span>
                    </div>
                </div>
            </section>

            {{-- Exam History --}}
            <section class="cand-card" id="exam-history">
                <h2 class="cand-card__title">Exam History</h2>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                    @foreach ([
                        ['Total Exams', $exam_stats['total']],
                        ['Completed', $exam_stats['completed']],
                        ['Ongoing', $exam_stats['ongoing']],
                        ['Upcoming', $exam_stats['upcoming']],
                        ['Passed', $exam_stats['passed']],
                        ['Failed', $exam_stats['failed']],
                        ['Avg Score', $exam_stats['average_score'] !== null ? $exam_stats['average_score'].'%' : '—'],
                        ['Total Attempts', $exam_stats['total_attempts']],
                    ] as [$label, $value])
                        <div class="cand-stat">
                            <span class="cand-stat__label">{{ $label }}</span>
                            <span class="cand-stat__value">{{ $value }}</span>
                        </div>
                    @endforeach
                </div>

                @if ($recent_attempts->isEmpty())
                    <div class="rounded-2xl border border-dashed border-slate-200 dark:border-slate-700 px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                        No exam attempts yet.
                    </div>
                @else
                    <div class="cand-table-scroll">
                        <table class="cand-table cand-table--history">
                            <thead>
                                <tr>
                                    <th class="cand-th-sticky">Exam</th>
                                    <th>Attempt</th>
                                    <th>Status</th>
                                    <th>Total Qs</th>
                                    <th>Attempted</th>
                                    <th>Right</th>
                                    <th>Wrong</th>
                                    <th>Unanswered</th>
                                    <th>Total Marks</th>
                                    <th>Neg. Marks</th>
                                    <th>Scored</th>
                                    <th>%</th>
                                    <th>Result</th>
                                    <th>Start</th>
                                    <th>End</th>
                                    <th>Duration</th>
                                    <th>Exam Duration</th>
                                    <th>Verification</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recent_attempts as $attempt)
                                    @php
                                        $exam = $attempt->exam;
                                        $config = is_array($attempt->exam_config_snapshot) ? $attempt->exam_config_snapshot : [];
                                        $examTitle = $exam?->title ?: ('Exam #'.$attempt->exam_id);
                                        $result = $attempt->passed === true ? 'Passed' : ($attempt->passed === false ? 'Failed' : '—');
                                        $attemptSnaps = $attempt->snapshots ?? collect();

                                        $totalQuestions = (int) ($config['total_questions'] ?? $exam?->total_questions ?? 0);
                                        $totalMarks = $config['total_marks'] ?? $exam?->total_marks;
                                        $examDurationMin = (int) ($config['duration'] ?? $exam?->duration ?? 0);
                                        $negEnabled = (bool) ($config['enable_negative_marking'] ?? $exam?->enable_negative_marking);
                                        $negPerQ = $config['negative_mark_per_question'] ?? $exam?->negative_mark_per_question;
                                        $negLabel = $negEnabled
                                            ? (filled($negPerQ) ? rtrim(rtrim(number_format((float) $negPerQ, 2, '.', ''), '0'), '.').'/Q' : 'On')
                                            : 'Off';

                                        $right = $attempt->correct_count;
                                        $wrong = $attempt->wrong_count;
                                        $unanswered = $attempt->unanswered_count;
                                        $attemptedQs = ($right !== null || $wrong !== null)
                                            ? (int) $right + (int) $wrong
                                            : (($totalQuestions > 0 && $unanswered !== null) ? max(0, $totalQuestions - (int) $unanswered) : null);
                                    @endphp
                                    <tr>
                                        <td class="cand-td-sticky">
                                            @if ($attempt->exam_id)
                                                <a
                                                    href="{{ route('admin.exams.show', $attempt->exam_id) }}"
                                                    class="cand-exam-title"
                                                    title="{{ $examTitle }}"
                                                >{{ $examTitle }}</a>
                                            @else
                                                <span class="cand-exam-title" title="{{ $examTitle }}">{{ $examTitle }}</span>
                                            @endif
                                        </td>
                                        <td>#{{ $attempt->attempt_no }}</td>
                                        <td><span class="{{ $attemptStatusBadge($attempt->status) }}">{{ str_replace('_', ' ', ucfirst($attempt->status)) }}</span></td>
                                        <td>{{ $totalQuestions > 0 ? $totalQuestions : '—' }}</td>
                                        <td>{{ $attemptedQs !== null ? $attemptedQs : '—' }}</td>
                                        <td>{{ $right !== null ? $right : '—' }}</td>
                                        <td>{{ $wrong !== null ? $wrong : '—' }}</td>
                                        <td>{{ $unanswered !== null ? $unanswered : '—' }}</td>
                                        <td>{{ $totalMarks !== null ? $totalMarks : '—' }}</td>
                                        <td>{{ $negLabel }}</td>
                                        <td>{{ $attempt->score !== null ? $attempt->score : '—' }}</td>
                                        <td>{{ $attempt->percentage !== null ? number_format((float) $attempt->percentage, 1).'%' : '—' }}</td>
                                        <td>
                                            <span class="{{ $result === 'Passed' ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : ($result === 'Failed' ? 'text-rose-600 dark:text-rose-400 font-semibold' : '') }}">
                                                {{ $result }}
                                            </span>
                                        </td>
                                        <td>{{ optional($attempt->started_at)->format('d M Y H:i') ?: '—' }}</td>
                                        <td>{{ optional($attempt->submitted_at)->format('d M Y H:i') ?: '—' }}</td>
                                        <td>{{ $formatDuration($attempt->time_spent_seconds) }}</td>
                                        <td>{{ $examDurationMin > 0 ? $examDurationMin.' min' : '—' }}</td>
                                        <td class="cand-td-verify">
                                            @if ($attemptSnaps->isEmpty())
                                                <span class="text-slate-400 dark:text-slate-500">—</span>
                                            @else
                                                <div class="cand-verify-thumbs">
                                                    @foreach ($attemptSnaps->take(4) as $snap)
                                                        @php
                                                            $snapType = match ($snap->type) {
                                                                'selfie' => 'Selfie Verification',
                                                                'webcam' => 'Webcam Snapshot',
                                                                'identity' => 'Identity Document',
                                                                default => ucfirst((string) $snap->type).' Snapshot',
                                                            };
                                                            $snapUrl = route('admin.candidates.snapshots.show', [$candidate, $snap]);
                                                            $snapDownload = route('admin.candidates.snapshots.download', [$candidate, $snap]);
                                                        @endphp
                                                        <button
                                                            type="button"
                                                            class="cand-verify-thumb js-preview-doc"
                                                            title="{{ $snapType }}"
                                                            aria-label="View {{ $snapType }}"
                                                            data-preview-url="{{ $snapUrl }}"
                                                            data-preview-label="{{ $snapType }} — {{ $examTitle }}"
                                                            data-preview-type="{{ $snapType }}"
                                                            data-preview-status="{{ ucfirst($snap->verification_status ?: 'captured') }}"
                                                            data-preview-exam="{{ $examTitle }}"
                                                            data-preview-attempt="#{{ $attempt->attempt_no }}"
                                                            data-preview-captured="{{ optional($snap->created_at)->format('d M Y H:i') }}"
                                                            data-preview-download="{{ $snapDownload }}"
                                                        >
                                                            <img src="{{ $snapUrl }}" alt="{{ $snapType }}" loading="lazy">
                                                        </button>
                                                    @endforeach
                                                    @if ($attemptSnaps->count() > 4)
                                                        <span class="cand-verify-more">+{{ $attemptSnaps->count() - 4 }}</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            {{-- Feedback --}}
            @if ($feedback->isNotEmpty())
                <section class="cand-card" id="feedback-section">
                    <h2 class="cand-card__title">Feedback Submitted</h2>
                    <div class="space-y-3">
                        @foreach ($feedback as $item)
                            @php $rating = max(0, min(5, (int) ($item->rating ?? 0))); @endphp
                            <article class="cand-feedback-card">
                                <div class="cand-feedback-card__top">
                                    <div class="min-w-0">
                                        <div class="cand-feedback-card__title">
                                            {{ $item->title ?: ($item->exam?->title ?: 'General feedback') }}
                                        </div>
                                        @if ($item->exam?->title && $item->title)
                                            <div class="cand-feedback-card__exam">{{ $item->exam->title }}</div>
                                        @endif
                                    </div>
                                    <div class="cand-feedback-card__meta">
                                        @if ($rating > 0)
                                            <span class="cand-stars" aria-label="{{ $rating }} out of 5 stars" title="{{ $rating }}/5">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    <svg class="cand-star {{ $i <= $rating ? 'is-filled' : '' }}" viewBox="0 0 20 20" aria-hidden="true">
                                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                    </svg>
                                                @endfor
                                                <span class="cand-stars__value">{{ $rating }}/5</span>
                                            </span>
                                        @endif
                                        <span class="cand-feedback-card__date">{{ optional($item->created_at)->format('d M Y H:i') }}</span>
                                    </div>
                                </div>
                                @if ($item->message)
                                    <p class="cand-feedback-card__message">{{ $item->message }}</p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="lg:col-span-4 space-y-6">
            <section class="cand-card">
                <h2 class="cand-card__title">Address</h2>
                @php
                    $addressParts = array_filter([
                        $profile?->address_line1,
                        $profile?->address_line2,
                        $profile?->city,
                        $profile?->state_region,
                        $profile?->postal_code,
                        $countries[$profile?->country] ?? $profile?->country,
                    ]);
                @endphp
                @if ($addressParts)
                    <p class="text-sm text-slate-700 dark:text-slate-200 leading-relaxed whitespace-pre-line">{{ implode("\n", $addressParts) }}</p>
                @else
                    <p class="text-sm text-slate-400 dark:text-slate-500">No address on file.</p>
                @endif
            </section>

            <section class="cand-card">
                <h2 class="cand-card__title">Social Links</h2>
                @php $filledSocial = collect($social)->filter(); @endphp
                @if ($filledSocial->isEmpty())
                    <p class="text-sm text-slate-400 dark:text-slate-500">No social links added.</p>
                @else
                    <ul class="space-y-2">
                        @foreach ($filledSocial as $network => $url)
                            <li>
                                <a href="{{ $url }}" target="_blank" rel="noopener" class="text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 break-all">
                                    {{ ucfirst($network) }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="cand-card">
                <h2 class="cand-card__title">Activity Information</h2>
                <div class="space-y-3 text-sm">
                    <div>
                        <span class="cand-meta-label">Account Created</span>
                        <span class="cand-meta-value">{{ optional($candidate->created_at)->format('d M Y H:i') }}</span>
                    </div>
                    <div>
                        <span class="cand-meta-label">Last Updated</span>
                        <span class="cand-meta-value">{{ optional($candidate->updated_at)->format('d M Y H:i') }}</span>
                    </div>
                    <div>
                        <span class="cand-meta-label">Last Login</span>
                        <span class="cand-meta-value">{{ $last_login ?: '—' }}</span>
                    </div>
                    <div>
                        <span class="cand-meta-label">Feedback Submitted</span>
                        <span class="cand-meta-value">{{ $feedback_count }}</span>
                    </div>
                    <div>
                        <span class="cand-meta-label">Rule Violations</span>
                        <span class="cand-meta-value {{ $violation_count > 0 ? 'text-rose-600 dark:text-rose-400' : '' }}">{{ $violation_count }}</span>
                    </div>
                </div>

                @if ($activity_logs->isNotEmpty())
                    <h3 class="text-sm font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mt-6 mb-3">Recent Activity</h3>
                    <ul class="space-y-3">
                        @foreach ($activity_logs as $log)
                            <li class="border-l-2 border-slate-200 dark:border-slate-700 pl-3">
                                <div class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ $log->title }}</div>
                                @if ($log->description)
                                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $log->description }}</div>
                                @endif
                                <div class="text-[11px] text-slate-400 mt-1">{{ optional($log->created_at)->format('d M Y H:i') }}</div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
</div>

{{-- Reset password modal --}}
@unless ($isTrashed)
<div id="reset-password-modal" class="cand-modal" hidden>
    <div class="cand-modal__backdrop" data-modal-close></div>
    <div class="cand-modal__panel" role="dialog" aria-modal="true" aria-labelledby="reset-password-title">
        <h3 id="reset-password-title" class="text-lg font-bold text-slate-900 dark:text-white">Reset Password</h3>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Leave blank to generate a temporary password automatically.</p>
        <form action="{{ route('admin.candidates.reset-password', $candidate) }}" method="POST" class="mt-5 space-y-4">
            @csrf
            <div>
                <label for="reset_password" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">New Password</label>
                <input type="password" id="reset_password" name="password" class="panel-input w-full" autocomplete="new-password" placeholder="Optional">
            </div>
            <div>
                <label for="reset_password_confirmation" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Confirm Password</label>
                <input type="password" id="reset_password_confirmation" name="password_confirmation" class="panel-input w-full" autocomplete="new-password" placeholder="Optional">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="panel-button-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="panel-button-primary">Reset Password</button>
            </div>
        </form>
    </div>
</div>
@endunless

{{-- Image preview lightbox --}}
<div id="doc-preview-modal" class="cand-modal" hidden>
    <div class="cand-modal__backdrop" data-modal-close></div>
    <div class="cand-modal__panel cand-modal__panel--media" role="dialog" aria-modal="true" aria-labelledby="doc-preview-title">
        <div class="cand-preview-header">
            <div class="min-w-0">
                <p class="cand-preview-eyebrow">Verification</p>
                <h3 id="doc-preview-title" class="cand-preview-title truncate">Preview</h3>
            </div>
            <button type="button" class="panel-button-secondary" data-modal-close>Close</button>
        </div>
        <div class="cand-preview-layout">
            <div class="cand-preview-media">
                <img id="doc-preview-image" src="" alt="" class="cand-modal__image">
            </div>
            <aside class="cand-preview-aside">
                <dl class="cand-preview-meta">
                    <div class="cand-preview-meta__row">
                        <dt>Type</dt>
                        <dd id="doc-preview-type">—</dd>
                    </div>
                    <div class="cand-preview-meta__row">
                        <dt>Status</dt>
                        <dd id="doc-preview-status">—</dd>
                    </div>
                    <div class="cand-preview-meta__row">
                        <dt>Exam</dt>
                        <dd id="doc-preview-exam">—</dd>
                    </div>
                    <div class="cand-preview-meta__row">
                        <dt>Attempt</dt>
                        <dd id="doc-preview-attempt">—</dd>
                    </div>
                    <div class="cand-preview-meta__row">
                        <dt>Captured</dt>
                        <dd id="doc-preview-captured">—</dd>
                    </div>
                </dl>
                <a id="doc-preview-download" href="#" class="panel-button-primary w-full justify-center" download target="_blank" rel="noopener" hidden>
                    Download
                </a>
            </aside>
        </div>
    </div>
</div>

<x-coming-soon-modal
    id="invoice-coming-soon-modal"
    size="xl"
    title="Coming Soon"
    message="The Invoice feature is currently under development and will be available in a future release."
/>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ versioned_asset('css/backend/candidate-show.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('css/backend/coming-soon-modal.css') }}">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ versioned_asset('js/backend/coming-soon-modal.js') }}"></script>
    <script src="{{ versioned_asset('js/backend/candidate-show.js') }}"></script>
@endpush

<?php

namespace App\Http\Requests\Frontend;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:160'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
            'exam_id' => ['nullable', 'integer', 'exists:exams,id'],
            'exam_attempt_id' => ['nullable', 'integer', 'exists:exam_attempts,id'],
            'feedbackable_type' => ['nullable', 'string', 'max:120'],
            'feedbackable_id' => ['nullable', 'integer'],
            'source' => ['nullable', 'string', 'max:40'],
            'is_public' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $hasExam = filled($this->input('exam_id'));
            $hasAttempt = filled($this->input('exam_attempt_id'));
            $hasMorph = filled($this->input('feedbackable_type')) && filled($this->input('feedbackable_id'));

            if (! $hasExam && ! $hasAttempt && ! $hasMorph) {
                $validator->errors()->add('feedbackable', 'A feedback target is required.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'Please select a star rating.',
            'message.required' => 'Please write your feedback.',
            'message.min' => 'Please share at least 10 characters.',
            'message.max' => 'Feedback must be 2000 characters or fewer.',
        ];
    }
}

<label class="et-field">
    <span class="et-field__label">Difficulty</span>
    <span class="et-field__control">
        <svg class="et-field__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 3l2.2 6.6H21l-5.4 4 2.1 6.4L12 16.8 6.3 20l2.1-6.4L3 9.6h6.8L12 3z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
        </svg>
        <select name="difficulty" aria-label="Difficulty" data-placeholder="Select difficulty">
            <option value="">All levels</option>
            @foreach(['easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard', 'very_hard' => 'Very hard'] as $val => $label)
                <option value="{{ $val }}" @selected(request('difficulty') === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </span>
</label>

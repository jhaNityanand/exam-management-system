@php
    $testimonials = [
        [
            'quote' => 'I prep with chapter quizzes after campus hours. The interface is clean, and blog tips were surprisingly practical.',
            'name' => 'Rahul Nair',
            'role' => 'Engineering Student — NIT Calicut',
            'rating' => 5,
            'color' => '#2563eb',
            'initials' => 'RN',
        ],
        [
            'quote' => 'Timed mocks finally felt like the real exam. Tracking weak topics week by week made a huge difference.',
            'name' => 'Ananya Sharma',
            'role' => 'UPSC Aspirant — Delhi',
            'rating' => 5,
            'color' => '#0d9488',
            'initials' => 'AS',
        ],
        [
            'quote' => 'Our coaching batch uses Examtube for weekly assessments. Students love the clarity of results.',
            'name' => 'Vikram Joshi',
            'role' => 'Mentor — Pune Academy',
            'rating' => 5,
            'color' => '#db2777',
            'initials' => 'VJ',
        ],
        [
            'quote' => 'Questions with explanations saved me hours. I jump between categories without losing focus.',
            'name' => 'Sneha Patel',
            'role' => 'Banking Exam Candidate',
            'rating' => 4,
            'color' => '#ea580c',
            'initials' => 'SP',
        ],
        [
            'quote' => 'News alerts and exam calendar posts keep me ahead of deadlines. Simple and reliable.',
            'name' => 'Arjun Mehta',
            'role' => 'MBA Aspirant — Mumbai',
            'rating' => 5,
            'color' => '#7c3aed',
            'initials' => 'AM',
        ],
        [
            'quote' => 'From free practice papers to paid proctored mocks — everything feels intentional and polished.',
            'name' => 'Meera Iyer',
            'role' => 'Campus Placement Prep',
            'rating' => 5,
            'color' => '#0891b2',
            'initials' => 'MI',
        ],
    ];
@endphp
<section class="et-section" data-reveal>
    <div class="et-container">
        @include('frontend.components.section-heading', [
            'title' => 'Stories from learners',
            'subtitle' => 'Real outcomes from students and job seekers preparing with Examtube.',
        ])
        <div class="et-review-grid">
            @foreach($testimonials as $item)
                <article class="et-testimonial et-testimonial--lift">
                    <div class="et-testimonial__rating" aria-label="{{ $item['rating'] }} out of 5 stars">
                        @for($i = 1; $i <= 5; $i++)
                            <span class="{{ $i <= $item['rating'] ? 'is-on' : '' }}">★</span>
                        @endfor
                    </div>
                    <p class="et-testimonial__quote">“{{ $item['quote'] }}”</p>
                    <div class="et-testimonial__person">
                        <div class="et-testimonial__avatar" style="background:{{ $item['color'] }};color:#fff">{{ $item['initials'] }}</div>
                        <div>
                            <div class="et-testimonial__name">{{ $item['name'] }}</div>
                            <div class="et-testimonial__role">{{ $item['role'] }}</div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>

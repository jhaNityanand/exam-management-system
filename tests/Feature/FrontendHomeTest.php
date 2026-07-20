<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('home page renders examtube frontend from database content', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Examtube', false)
        ->assertSee('Featured exams', false);
});

test('exams listing page is available', function () {
    $this->get(route('frontend.exams.index'))
        ->assertOk();
});

test('cms about page is available at clean url', function () {
    $this->get(route('frontend.pages.show', 'about-us'))
        ->assertOk()
        ->assertSee('About Examtube', false);
});

test('legacy pages prefix redirects to clean slug', function () {
    $this->get('/pages/contact-us')
        ->assertRedirect('/contact-us');
});

test('contact page renders within frontend layout', function () {
    $this->get('/contact-us')
        ->assertOk()
        ->assertSee('Send us a message', false)
        ->assertSee('Contact details', false)
        ->assertSee('Examtube', false);
});

test('newsletter subscription stores email', function () {
    $this->postJson(route('frontend.newsletter.store'), [
        'email' => 'aspirant@examtube.in',
        'name' => 'Aspirant',
    ])->assertCreated();

    $this->assertDatabaseHas('newsletter_subscribers', [
        'email' => 'aspirant@examtube.in',
        'status' => 'subscribed',
    ]);
});

test('search suggest returns json payload', function () {
    $this->getJson(route('frontend.search.suggest', ['q' => 'exam']))
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'exams',
                'blogs',
                'news',
                'categories',
                'questions',
            ],
        ]);
});

<?php

use App\Models\Organization;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\User;
use App\Models\UserOrganization;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->organization = Organization::create([
        'name' => 'QB Org',
        'slug' => 'qb-org-'.$this->user->id,
        'status' => 'active',
    ]);

    UserOrganization::create([
        'user_id' => $this->user->id,
        'organization_id' => $this->organization->id,
        'role' => 'admin',
        'status' => 'active',
    ]);

    $this->category = QuestionCategory::create([
        'organization_id' => $this->organization->id,
        'name' => 'Parent Cat',
        'status' => 'active',
    ]);

    $this->child = QuestionCategory::create([
        'organization_id' => $this->organization->id,
        'parent_id' => $this->category->id,
        'name' => 'Child Cat',
        'status' => 'active',
    ]);
});

function makeBankQuestion(int $orgId, int $categoryId, array $overrides = []): Question
{
    return Question::create(array_merge([
        'organization_id' => $orgId,
        'category_id' => $categoryId,
        'body' => 'Question body '.uniqid(),
        'type' => 'true_false',
        'correct_answer' => 'True',
        'difficulty' => 'easy',
        'marks_type' => 'single',
        'marks' => 1,
        'status' => 'active',
    ], $overrides));
}

test('question bank api is organization scoped', function () {
    $otherOrg = Organization::create([
        'name' => 'Other QB Org',
        'slug' => 'other-qb-'.uniqid(),
        'status' => 'active',
    ]);
    $otherCategory = QuestionCategory::create([
        'organization_id' => $otherOrg->id,
        'name' => 'Other',
        'status' => 'active',
    ]);

    makeBankQuestion($this->organization->id, $this->category->id, ['body' => 'Mine keyword']);
    makeBankQuestion($otherOrg->id, $otherCategory->id, ['body' => 'Mine keyword other']);

    $this->actingAs($this->user)
        ->getJson(route('admin.api.question-bank.questions', ['q' => 'Mine keyword']))
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonCount(1, 'data');
});

test('question bank api paginates with cursor continuity and no overlap', function () {
    for ($i = 1; $i <= 5; $i++) {
        makeBankQuestion($this->organization->id, $this->category->id, [
            'body' => "Paged question {$i}",
        ]);
    }

    $first = $this->actingAs($this->user)
        ->getJson(route('admin.api.question-bank.questions', [
            'categories' => $this->category->id,
            'per_page' => 2,
        ]))
        ->assertOk()
        ->assertJsonPath('meta.total', 5)
        ->assertJsonPath('meta.has_more', true)
        ->assertJsonCount(2, 'data');

    $firstIds = collect($first->json('data'))->pluck('id')->all();
    $cursor = $first->json('meta.next_cursor');
    expect($cursor)->not->toBeNull();

    $second = $this->actingAs($this->user)
        ->getJson(route('admin.api.question-bank.questions', [
            'categories' => $this->category->id,
            'per_page' => 2,
            'cursor' => $cursor,
        ]))
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $secondIds = collect($second->json('data'))->pluck('id')->all();
    expect(array_intersect($firstIds, $secondIds))->toBe([]);
});

test('question bank includes descendant categories and supports search hydration', function () {
    $parentQ = makeBankQuestion($this->organization->id, $this->category->id, ['body' => 'Parent uniquealpha']);
    $childQ = makeBankQuestion($this->organization->id, $this->child->id, ['body' => 'Child uniquebeta']);

    $this->actingAs($this->user)
        ->getJson(route('admin.api.question-bank.questions', [
            'categories' => $this->category->id,
            'per_page' => 50,
        ]))
        ->assertOk()
        ->assertJsonPath('meta.total', 2);

    $this->actingAs($this->user)
        ->getJson(route('admin.api.question-bank.questions', [
            'q' => 'uniquebeta',
        ]))
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $childQ->id);

    $this->actingAs($this->user)
        ->getJson(route('admin.api.question-bank.questions', [
            'ids' => $parentQ->id.','.$childQ->id,
        ]))
        ->assertOk()
        ->assertJsonPath('meta.total', 2);
});

test('question bank random sample respects count and filters', function () {
    for ($i = 1; $i <= 6; $i++) {
        makeBankQuestion($this->organization->id, $this->category->id, [
            'body' => "Random {$i}",
            'marks' => $i <= 3 ? 1 : 2,
        ]);
    }

    $response = $this->actingAs($this->user)
        ->getJson(route('admin.api.question-bank.random', [
            'categories' => $this->category->id,
            'marks' => '1',
            'count' => 2,
        ]))
        ->assertOk()
        ->assertJsonPath('meta.requested', 2)
        ->assertJsonPath('meta.returned', 2);

    foreach ($response->json('data') as $row) {
        expect((int) $row['marks'])->toBe(1);
    }
});

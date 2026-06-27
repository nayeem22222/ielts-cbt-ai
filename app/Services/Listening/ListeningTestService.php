<?php

declare(strict_types=1);

namespace App\Services\Listening;

use App\Actions\Listening\ArchiveListeningTestAction;
use App\Actions\Listening\DuplicateListeningTestAction;
use App\Actions\Listening\PublishListeningTestAction;
use App\Enums\Listening\ListeningConstants;
use App\Enums\Listening\ListeningTestStatus;
use App\Models\Listening\ListeningTest;
use App\Models\Listening\ListeningTestSetting;
use App\Repositories\Listening\ListeningTestRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ListeningTestService
{
    public function __construct(
        private readonly ListeningTestRepository $repository,
        private readonly PublishListeningTestAction $publishAction,
        private readonly ArchiveListeningTestAction $archiveAction,
        private readonly DuplicateListeningTestAction $duplicateAction,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForAdmin(array $filters): LengthAwarePaginator
    {
        return $this->repository->paginateForAdmin($filters);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ListeningTest
    {
        return DB::transaction(function () use ($data): ListeningTest {
            $payload = $this->preparePayload($data);

            $test = $this->repository->create($payload);
            $test->setting()->create(ListeningTestSetting::officialDefaults());

            return $test->refresh()->load('setting');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ListeningTest $test, array $data): ListeningTest
    {
        return DB::transaction(function () use ($test, $data): ListeningTest {
            $payload = $this->preparePayload($data, $test);

            if ($test->status === ListeningTestStatus::Archived) {
                $payload['is_active'] = false;
            }

            return $this->repository->update($test, $payload);
        });
    }

    public function delete(ListeningTest $test): bool
    {
        return DB::transaction(fn (): bool => $this->repository->delete($test));
    }

    public function restore(int $id): ?ListeningTest
    {
        return DB::transaction(fn (): ?ListeningTest => $this->repository->restore($id));
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function updateSettings(ListeningTest $test, array $settings): ListeningTestSetting
    {
        return DB::transaction(function () use ($test, $settings): ListeningTestSetting {
            $this->ensureSettings($test);

            $test->setting()->updateOrCreate(
                ['listening_test_id' => $test->id],
                $settings,
            );

            return $test->setting()->firstOrFail();
        });
    }

    /**
     * @return array{
     *     sections_count: int,
     *     questions_count: int,
     *     groups_count: int,
     *     sections_with_audio: int,
     *     has_settings: bool,
     *     is_publish_ready: bool,
     *     missing: list<string>
     * }
     */
    public function getReadinessSummary(ListeningTest $test): array
    {
        $test->loadCount([
            'sections' => fn ($query) => $query->where('is_active', true),
            'questions' => fn ($query) => $query->where('is_active', true),
            'questionGroups' => fn ($query) => $query->where('is_active', true),
        ]);

        $test->loadMissing([
            'setting',
            'sections' => fn ($query) => $query->where('is_active', true),
        ]);

        $sectionsWithAudio = $test->sections->filter(fn ($section) => $section->audio_id !== null)->count();
        $hasSettings = $test->setting !== null;
        $validation = $this->publishAction->validate($test);

        return [
            'sections_count' => (int) $test->sections_count,
            'questions_count' => (int) $test->questions_count,
            'groups_count' => (int) $test->question_groups_count,
            'sections_with_audio' => $sectionsWithAudio,
            'has_settings' => $hasSettings,
            'is_publish_ready' => $validation['success'],
            'missing' => $validation['errors'],
        ];
    }

    public function generateTestCode(): string
    {
        do {
            $code = 'LST-'.strtoupper(Str::random(8));
        } while ($this->repository->testCodeExists($code));

        return $code;
    }

    public function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        $slug = $base !== '' ? $base : 'listening-test';
        $suffix = 1;

        while ($this->repository->slugExists($slug, $ignoreId)) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * @return array{success: bool, errors: list<string>, test?: ListeningTest}
     */
    public function publish(ListeningTest $test): array
    {
        return $this->publishAction->execute($test);
    }

    public function unpublish(ListeningTest $test): ListeningTest
    {
        return DB::transaction(function () use ($test): ListeningTest {
            $test->forceFill([
                'status' => ListeningTestStatus::Draft,
                'is_active' => false,
                'updated_by' => auth()->id(),
            ])->save();

            return $test->refresh();
        });
    }

    public function archive(ListeningTest $test): ListeningTest
    {
        return $this->archiveAction->execute($test);
    }

    public function duplicate(ListeningTest $test, int $userId): ListeningTest
    {
        return $this->duplicateAction->execute($test, $userId);
    }

    public function ensureSettings(ListeningTest $test): ListeningTestSetting
    {
        if ($test->setting()->exists()) {
            return $test->setting()->firstOrFail();
        }

        return $test->setting()->create(ListeningTestSetting::officialDefaults());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function preparePayload(array $data, ?ListeningTest $existing = null): array
    {
        if (empty($data['status'])) {
            $data['status'] = ListeningTestStatus::Draft->value;
        }

        if (empty($data['slug']) && ! empty($data['title'])) {
            $data['slug'] = $this->generateUniqueSlug((string) $data['title'], $existing?->id);
        }

        if (empty($data['test_code'])) {
            $data['test_code'] = $this->generateTestCode();
        }

        if (! isset($data['duration_minutes']) || $data['duration_minutes'] === '') {
            $data['duration_minutes'] = ListeningConstants::DEFAULT_DURATION_MINUTES;
        }

        if (! isset($data['transfer_time_minutes']) || $data['transfer_time_minutes'] === '') {
            $data['transfer_time_minutes'] = ListeningConstants::DEFAULT_TRANSFER_TIME_MINUTES;
        }

        if (! isset($data['total_sections'])) {
            $data['total_sections'] = ListeningConstants::TOTAL_SECTIONS;
        }

        if (! isset($data['total_questions'])) {
            $data['total_questions'] = ListeningConstants::TOTAL_QUESTIONS;
        }

        if (! isset($data['total_marks'])) {
            $data['total_marks'] = ListeningConstants::DEFAULT_TOTAL_MARKS;
        }

        if (($data['status'] ?? null) === ListeningTestStatus::Published->value && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['is_featured'] = (bool) ($data['is_featured'] ?? false);

        return $data;
    }
}

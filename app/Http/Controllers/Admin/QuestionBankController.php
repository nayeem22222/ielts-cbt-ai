<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Crud\CrudQuery;
use App\Enums\Commerce\IeltsModule;
use App\Enums\Course\ExamType;
use App\Enums\Course\PublishStatus;
use App\Http\Controllers\Admin\Concerns\HandlesCrudOperations;
use App\Http\Controllers\Admin\Concerns\ManagesCourseCrud;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQuestionBankRequest;
use App\Http\Requests\Admin\UpdateQuestionBankRequest;
use App\Models\QuestionBank;
use App\Services\Admin\Exam\QuestionBankCrudService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class QuestionBankController extends Controller
{
    use HandlesCrudOperations;
    use ManagesCourseCrud;

    public function __construct(private readonly QuestionBankCrudService $banks)
    {
    }

    protected function crudService(): QuestionBankCrudService
    {
        return $this->banks;
    }

    protected function crudModelClass(): string
    {
        return QuestionBank::class;
    }

    protected function crudRoutePrefix(): string
    {
        return 'admin.question-banks';
    }

    protected function entityLabel(): string
    {
        return 'Question Bank';
    }

    protected function viewsNamespace(): string
    {
        return 'pages.admin.question-banks';
    }

    protected function crudTrashView(): string
    {
        return $this->viewsNamespace().'.trash';
    }

    protected function indexViewData(CrudQuery $crudQuery, mixed $records): array
    {
        return [
            'statuses' => PublishStatus::cases(),
            'examTypes' => ExamType::cases(),
        ];
    }

    public function create(): View
    {
        $this->authorize('create', QuestionBank::class);

        return view($this->viewsNamespace().'.create', $this->formData());
    }

    public function store(StoreQuestionBankRequest $request): RedirectResponse
    {
        $this->banks->create(array_merge($request->validated(), [
            'module' => IeltsModule::Reading->value,
            'created_by' => $request->user()?->id,
        ]));

        return redirect()
            ->route($this->crudRoutePrefix().'.index')
            ->with('status', 'Question bank created successfully.');
    }

    public function edit(QuestionBank $questionBank): View
    {
        $this->authorize('update', $questionBank);

        return view($this->viewsNamespace().'.edit', array_merge($this->formData(), [
            'questionBank' => $questionBank,
        ]));
    }

    public function update(UpdateQuestionBankRequest $request, QuestionBank $questionBank): RedirectResponse
    {
        $this->banks->update($questionBank, array_merge($request->validated(), [
            'module' => IeltsModule::Reading->value,
        ]));

        return redirect()
            ->route($this->crudRoutePrefix().'.index')
            ->with('status', 'Question bank updated successfully.');
    }

    public function destroy(QuestionBank $questionBank): RedirectResponse
    {
        $this->authorize('delete', $questionBank);
        $this->banks->delete($questionBank);

        return redirect()
            ->route($this->crudRoutePrefix().'.index')
            ->with('status', 'Question bank deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'statuses' => PublishStatus::cases(),
            'examTypes' => ExamType::cases(),
        ];
    }
}

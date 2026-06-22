@include('pages.admin.partials.course-trash', [
    'entityLabel' => 'Question Banks',
    'indexRoute' => route('admin.question-banks.index'),
    'columns' => ['name' => 'Name', 'slug' => 'Slug', 'exam_type' => 'Exam Type'],
])

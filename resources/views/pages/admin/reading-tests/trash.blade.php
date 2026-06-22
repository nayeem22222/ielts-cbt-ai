@include('pages.admin.partials.course-trash', [
    'entityLabel' => 'Reading Tests',
    'indexRoute' => route('admin.reading-tests.index'),
    'columns' => ['title' => 'Title', 'slug' => 'Slug', 'total_questions' => 'Questions'],
])

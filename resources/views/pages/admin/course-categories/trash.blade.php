@include('pages.admin.partials.course-trash', [
    'entityLabel' => 'Categories',
    'indexRoute' => route('admin.course-categories.index'),
    'columns' => ['name' => 'Name', 'slug' => 'Slug'],
])

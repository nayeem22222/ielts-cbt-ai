@include('pages.admin.partials.course-trash', [
    'entityLabel' => 'Packages',
    'indexRoute' => route('admin.packages.index'),
    'columns' => ['name' => 'Name', 'slug' => 'Slug', 'price' => 'Price'],
])

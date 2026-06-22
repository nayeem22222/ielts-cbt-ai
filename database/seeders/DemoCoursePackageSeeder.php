<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Commerce\BillingInterval;
use App\Enums\Commerce\IeltsModule;
use App\Enums\Commerce\PackageDiscountType;
use App\Enums\Commerce\PackageStatus;
use App\Enums\Course\CategoryStatus;
use App\Enums\Course\CourseLevel;
use App\Enums\Course\ExamType;
use App\Enums\Course\LessonContentType;
use App\Enums\Course\PublishStatus;
use App\Enums\Course\ResourceType;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\LessonResource;
use App\Models\Package;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class DemoCoursePackageSeeder extends Seeder
{
    /**
     * @var array<string, CourseCategory>
     */
    private array $categories = [];

    /**
     * @var array<string, Course>
     */
    private array $courses = [];

    public function run(): void
    {
        $adminId = User::query()->where('email', 'admin@example.com')->value('id');

        $this->seedCategories();
        $this->seedCourses($adminId);
        $this->seedPackages();
    }

    private function seedCategories(): void
    {
        $definitions = [
            'ielts-preparation' => [
                'name' => 'IELTS Preparation',
                'description' => 'Structured IELTS preparation courses covering reading, listening, writing, and speaking.',
                'sort_order' => 1,
            ],
            'spoken-english' => [
                'name' => 'Spoken English',
                'description' => 'Practical spoken English courses for learners of all ages.',
                'sort_order' => 2,
            ],
        ];

        foreach ($definitions as $slug => $attributes) {
            $this->categories[$slug] = CourseCategory::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $attributes['name'],
                    'description' => $attributes['description'],
                    'sort_order' => $attributes['sort_order'],
                    'status' => CategoryStatus::Active->value,
                ]
            );
        }
    }

    private function seedCourses(?int $adminId): void
    {
        $definitions = [
            'complete-ielts-course' => [
                'category' => 'ielts-preparation',
                'title' => 'Complete IELTS Course',
                'short_description' => 'A full IELTS preparation pathway with lessons, practice, and mock tests.',
                'full_description' => 'Build confidence across all four IELTS modules with guided lessons, timed practice, writing feedback workflows, and speaking drills. Ideal for Academic and General Training candidates targeting band 6.5+.',
                'exam_type' => ExamType::Academic->value,
                'level' => CourseLevel::Intermediate->value,
                'status' => PublishStatus::Published->value,
                'sort_order' => 1,
                'sections' => $this->ieltsSectionTemplates(),
                'course_resources' => [
                    ['title' => 'IELTS Course Syllabus', 'file_type' => ResourceType::Pdf, 'file' => 'ielts-syllabus.pdf'],
                    ['title' => 'Band Score Guide', 'file_type' => ResourceType::Pdf, 'file' => 'band-score-guide.pdf'],
                ],
            ],
            'spoken-english-for-beginners' => [
                'category' => 'spoken-english',
                'title' => 'Spoken English for Beginners',
                'short_description' => 'Start speaking English confidently with everyday phrases and pronunciation practice.',
                'full_description' => 'Learn essential vocabulary, sentence patterns, and conversation drills designed for adult beginners. Each lesson includes listen-and-repeat audio, role-play prompts, and printable practice sheets.',
                'exam_type' => ExamType::General->value,
                'level' => CourseLevel::Beginner->value,
                'status' => PublishStatus::Published->value,
                'sort_order' => 2,
                'sections' => $this->spokenBeginnerSectionTemplates(),
                'course_resources' => [
                    ['title' => 'Beginner Vocabulary List', 'file_type' => ResourceType::Pdf, 'file' => 'beginner-vocabulary.pdf'],
                    ['title' => 'Daily Conversation Practice Sheet', 'file_type' => ResourceType::Doc, 'file' => 'daily-conversation.doc'],
                ],
            ],
            'spoken-english-for-kids' => [
                'category' => 'spoken-english',
                'title' => 'Spoken English for Kids',
                'short_description' => 'Fun, interactive English lessons designed for young learners.',
                'full_description' => 'Colorful lessons with songs, picture stories, and simple speaking games help children build listening and speaking skills in a playful classroom-style format.',
                'exam_type' => ExamType::General->value,
                'level' => CourseLevel::Beginner->value,
                'status' => PublishStatus::Draft->value,
                'sort_order' => 3,
                'sections' => $this->spokenKidsSectionTemplates(),
                'course_resources' => [
                    ['title' => 'Kids Vocabulary Flashcards', 'file_type' => ResourceType::Pdf, 'file' => 'kids-vocabulary.pdf'],
                    ['title' => 'Parent Practice Guide', 'file_type' => ResourceType::Pdf, 'file' => 'parent-guide.pdf'],
                    ['title' => 'Assignment: Weekly Speaking Log', 'file_type' => ResourceType::Doc, 'file' => 'speaking-log.doc'],
                ],
            ],
        ];

        foreach ($definitions as $slug => $definition) {
            $course = Course::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'course_category_id' => $this->categories[$definition['category']]->id,
                    'title' => $definition['title'],
                    'description' => $this->formatDescription(
                        $definition['short_description'],
                        $definition['full_description']
                    ),
                    'exam_type' => $definition['exam_type'],
                    'level' => $definition['level'],
                    'thumbnail_path' => 'placeholders/courses/'.$slug.'.jpg',
                    'status' => $definition['status'],
                    'sort_order' => $definition['sort_order'],
                    'published_at' => $definition['status'] === PublishStatus::Published->value ? now() : null,
                    'created_by' => $adminId,
                ]
            );

            $this->courses[$slug] = $course;

            $this->seedSectionsAndLessons($course, $definition['sections'], $adminId);
            $this->seedCourseResources($course, $definition['course_resources']);
        }
    }

    /**
     * @param  list<array{slug: string, title: string, description: string, lessons: list<array<string, mixed>>}>  $sections
     */
    private function seedSectionsAndLessons(Course $course, array $sections, ?int $adminId): void
    {
        foreach ($sections as $sectionIndex => $sectionDefinition) {
            $section = CourseSection::query()->updateOrCreate(
                [
                    'course_id' => $course->id,
                    'slug' => $sectionDefinition['slug'],
                ],
                [
                    'title' => $sectionDefinition['title'],
                    'description' => $sectionDefinition['description'],
                    'sort_order' => $sectionIndex + 1,
                    'status' => PublishStatus::Published->value,
                ]
            );

            foreach ($sectionDefinition['lessons'] as $lessonIndex => $lessonDefinition) {
                $lesson = Lesson::query()->updateOrCreate(
                    [
                        'course_section_id' => $section->id,
                        'slug' => $lessonDefinition['slug'],
                    ],
                    [
                        'title' => $lessonDefinition['title'],
                        'description' => $lessonDefinition['content'],
                        'content_type' => $lessonDefinition['content_type'],
                        'video_url' => $lessonDefinition['video_url'] ?? null,
                        'duration_seconds' => $lessonDefinition['duration_seconds'],
                        'is_preview' => $lessonDefinition['is_preview'] ?? ($sectionIndex === 0 && $lessonIndex === 0),
                        'sort_order' => $lessonIndex + 1,
                        'status' => $lessonDefinition['status'] ?? PublishStatus::Published->value,
                        'published_at' => ($lessonDefinition['status'] ?? PublishStatus::Published->value) === PublishStatus::Published->value
                            ? now()
                            : null,
                        'created_by' => $adminId,
                    ]
                );

                if (! empty($lessonDefinition['resources'])) {
                    $this->seedLessonResources($course, $lesson, $lessonDefinition['resources']);
                }
            }
        }
    }

    /**
     * @param  list<array{title: string, file_type: ResourceType, file: string}>  $resources
     */
    private function seedCourseResources(Course $course, array $resources): void
    {
        foreach ($resources as $index => $resource) {
            LessonResource::query()->updateOrCreate(
                [
                    'course_id' => $course->id,
                    'lesson_id' => null,
                    'title' => $resource['title'],
                ],
                [
                    'file_path' => 'demo/resources/'.$course->slug.'/'.$resource['file'],
                    'file_type' => $resource['file_type']->value,
                    'external_url' => null,
                    'sort_order' => $index + 1,
                    'is_downloadable' => true,
                ]
            );
        }
    }

    /**
     * @param  list<array{title: string, file_type: ResourceType, file: string}>  $resources
     */
    private function seedLessonResources(Course $course, Lesson $lesson, array $resources): void
    {
        foreach ($resources as $index => $resource) {
            LessonResource::query()->updateOrCreate(
                [
                    'course_id' => $course->id,
                    'lesson_id' => $lesson->id,
                    'title' => $resource['title'],
                ],
                [
                    'file_path' => 'demo/resources/'.$course->slug.'/'.$resource['file'],
                    'file_type' => $resource['file_type']->value,
                    'external_url' => null,
                    'sort_order' => $index + 1,
                    'is_downloadable' => true,
                ]
            );
        }
    }

    private function seedPackages(): void
    {
        $definitions = [
            'free-trial' => [
                'name' => 'Free Trial',
                'description' => 'Try limited access to all IELTS modules before subscribing.',
                'price' => 0,
                'duration_days' => 7,
                'trial_days' => 7,
                'billing_interval' => BillingInterval::Monthly->value,
                'discount_type' => PackageDiscountType::None->value,
                'discount_value' => 0,
                'module_access' => IeltsModule::values(),
                'attempt_limits' => [
                    IeltsModule::Reading->value => 2,
                    IeltsModule::Listening->value => 2,
                    IeltsModule::Writing->value => 1,
                    IeltsModule::Speaking->value => 1,
                ],
                'courses' => ['complete-ielts-course'],
                'sort_order' => 0,
                'is_public' => true,
            ],
            'ielts-monthly-package' => [
                'name' => 'IELTS Monthly Package',
                'description' => 'Full IELTS access for 30 days with generous module limits.',
                'price' => 1500,
                'duration_days' => 30,
                'trial_days' => 0,
                'billing_interval' => BillingInterval::Monthly->value,
                'discount_type' => PackageDiscountType::Percent->value,
                'discount_value' => 10,
                'module_access' => IeltsModule::values(),
                'attempt_limits' => [
                    IeltsModule::Speaking->value => 3,
                ],
                'courses' => ['complete-ielts-course'],
                'sort_order' => 1,
                'is_public' => true,
            ],
            'ielts-2-month-package' => [
                'name' => 'IELTS 2 Month Package',
                'description' => 'Extended IELTS preparation with increased speaking attempts.',
                'price' => 2500,
                'duration_days' => 60,
                'trial_days' => 0,
                'billing_interval' => BillingInterval::Monthly->value,
                'discount_type' => PackageDiscountType::None->value,
                'discount_value' => 0,
                'module_access' => IeltsModule::values(),
                'attempt_limits' => [
                    IeltsModule::Speaking->value => 6,
                ],
                'courses' => ['complete-ielts-course'],
                'sort_order' => 2,
                'is_public' => true,
            ],
            'ielts-3-month-package' => [
                'name' => 'IELTS 3 Month Package',
                'description' => 'Long-term IELTS plan with maximum speaking practice attempts.',
                'price' => 3500,
                'duration_days' => 90,
                'trial_days' => 0,
                'billing_interval' => BillingInterval::Quarterly->value,
                'discount_type' => PackageDiscountType::Fixed->value,
                'discount_value' => 300,
                'module_access' => IeltsModule::values(),
                'attempt_limits' => [
                    IeltsModule::Speaking->value => 9,
                ],
                'courses' => ['complete-ielts-course'],
                'sort_order' => 3,
                'is_public' => true,
            ],
            'spoken-english-package' => [
                'name' => 'Spoken English Package',
                'description' => 'Listening and speaking focused plan for adult learners.',
                'price' => 1000,
                'duration_days' => 30,
                'trial_days' => 3,
                'billing_interval' => BillingInterval::Monthly->value,
                'discount_type' => PackageDiscountType::None->value,
                'discount_value' => 0,
                'module_access' => [
                    IeltsModule::Listening->value,
                    IeltsModule::Speaking->value,
                ],
                'attempt_limits' => [
                    IeltsModule::Listening->value => 20,
                    IeltsModule::Speaking->value => 10,
                ],
                'courses' => ['spoken-english-for-beginners'],
                'sort_order' => 4,
                'is_public' => true,
            ],
            'kids-english-package' => [
                'name' => 'Kids English Package',
                'description' => 'Kid-friendly listening and speaking practice for young learners.',
                'price' => 1200,
                'duration_days' => 30,
                'trial_days' => 0,
                'billing_interval' => BillingInterval::Monthly->value,
                'discount_type' => PackageDiscountType::None->value,
                'discount_value' => 0,
                'module_access' => [
                    IeltsModule::Listening->value,
                    IeltsModule::Speaking->value,
                ],
                'attempt_limits' => [
                    IeltsModule::Listening->value => 15,
                    IeltsModule::Speaking->value => 8,
                ],
                'courses' => ['spoken-english-for-kids'],
                'sort_order' => 5,
                'is_public' => true,
            ],
        ];

        foreach ($definitions as $slug => $definition) {
            $package = Package::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'module_access' => $definition['module_access'],
                    'attempt_limits' => $definition['attempt_limits'],
                    'billing_interval' => $definition['billing_interval'],
                    'price' => $definition['price'],
                    'currency' => 'BDT',
                    'discount_type' => $definition['discount_type'],
                    'discount_value' => $definition['discount_value'],
                    'trial_days' => $definition['trial_days'],
                    'duration_days' => $definition['duration_days'],
                    'is_active' => true,
                    'is_public' => $definition['is_public'],
                    'sort_order' => $definition['sort_order'],
                    'status' => PackageStatus::Active->value,
                ]
            );

            $courseIds = collect($definition['courses'])
                ->map(fn (string $courseSlug): int => $this->courses[$courseSlug]->id)
                ->all();

            $syncPayload = [];

            foreach ($courseIds as $index => $courseId) {
                $syncPayload[$courseId] = [
                    'sort_order' => $index + 1,
                    'is_featured' => $index === 0,
                ];
            }

            $package->courses()->sync($syncPayload);
        }
    }

    private function formatDescription(string $shortDescription, string $fullDescription): string
    {
        return $shortDescription."\n\n".$fullDescription;
    }

    /**
     * @return list<array{slug: string, title: string, description: string, lessons: list<array<string, mixed>>}>
     */
    private function ieltsSectionTemplates(): array
    {
        return [
            $this->section('introduction', 'Introduction', 'Course orientation and IELTS overview.', [
                $this->lesson('welcome', 'Welcome to Complete IELTS', LessonContentType::Video, 480, 'Overview of the course structure and how to prepare effectively.', 'https://example.com/demo/ielts/welcome'),
                $this->lesson('exam-format', 'Understanding the IELTS Exam Format', LessonContentType::Text, 900, 'Breakdown of Academic vs General Training, timing, and scoring.'),
                $this->lesson('study-plan', 'Building Your Study Plan', LessonContentType::Text, 720, 'Weekly planner template and milestone checklist.', resources: [
                    ['title' => 'Study Plan PDF Notes', 'file_type' => ResourceType::Pdf, 'file' => 'study-plan-notes.pdf'],
                ]),
            ]),
            $this->section('foundation-lessons', 'Foundation Lessons', 'Core skills and strategies for each module.', [
                $this->lesson('reading-skills', 'Reading: Skimming and Scanning', LessonContentType::Video, 1200, 'Techniques for tackling passages under time pressure.', 'https://example.com/demo/ielts/reading-skills'),
                $this->lesson('listening-skills', 'Listening: Note-taking Strategies', LessonContentType::Video, 1100, 'Capture key details during audio sections.', 'https://example.com/demo/ielts/listening-skills'),
                $this->lesson('writing-task-1', 'Writing Task 1 Foundations', LessonContentType::Text, 1500, 'Structure reports and describe visual data clearly.'),
                $this->lesson('writing-task-2', 'Writing Task 2 Essay Framework', LessonContentType::Text, 1800, 'Plan introductions, body paragraphs, and conclusions.'),
                $this->lesson('speaking-part-1', 'Speaking Part 1 Essentials', LessonContentType::Video, 900, 'Answer familiar topic questions with fluency.', 'https://example.com/demo/ielts/speaking-part-1'),
            ]),
            $this->section('practice-lessons', 'Practice Lessons', 'Guided practice for each IELTS module.', [
                $this->lesson('reading-practice-1', 'Reading Practice Set 1', LessonContentType::Quiz, 1800, 'Timed multiple-choice and matching questions.'),
                $this->lesson('listening-practice-1', 'Listening Practice Set 1', LessonContentType::Quiz, 1500, 'Section 1–4 style audio drills with answer review.'),
                $this->lesson('writing-practice-1', 'Writing Practice: Task 2 Prompt', LessonContentType::Text, 2400, 'Plan and draft a balanced opinion essay.'),
                $this->lesson('speaking-practice-1', 'Speaking Practice: Cue Card Drill', LessonContentType::Video, 1200, 'Two-minute monologue with follow-up questions.', 'https://example.com/demo/ielts/speaking-practice'),
                $this->lesson('vocabulary-builder', 'Academic Vocabulary Builder', LessonContentType::Text, 600, 'High-frequency topic vocabulary with example sentences.', resources: [
                    ['title' => 'Vocabulary List', 'file_type' => ResourceType::Pdf, 'file' => 'academic-vocabulary.pdf'],
                ]),
            ]),
            $this->section('mock-test-assignment', 'Mock Test / Assignment', 'Full-length mock components and assignments.', [
                $this->lesson('mini-mock-reading', 'Mini Mock: Reading', LessonContentType::Quiz, 3600, 'One full reading passage set under exam conditions.'),
                $this->lesson('mini-mock-listening', 'Mini Mock: Listening', LessonContentType::Quiz, 2400, 'Complete listening sections with auto-marking.'),
                $this->lesson('writing-assignment', 'Assignment: Writing Task Submission', LessonContentType::Text, 2700, 'Submit a Task 2 essay for review using the checklist.', resources: [
                    ['title' => 'Assignment File', 'file_type' => ResourceType::Doc, 'file' => 'writing-assignment.doc'],
                ]),
                $this->lesson('speaking-mock', 'Speaking Mock Interview', LessonContentType::Live, 1800, 'Simulated speaking test with examiner prompts.'),
            ]),
            $this->section('final-review', 'Final Review', 'Revision, tips, and exam-day preparation.', [
                $this->lesson('common-mistakes', 'Common Mistakes to Avoid', LessonContentType::Text, 720, 'Top errors in each module and how to fix them.'),
                $this->lesson('time-management', 'Exam Day Time Management', LessonContentType::Video, 600, 'Pacing strategies for each paper.', 'https://example.com/demo/ielts/time-management'),
                $this->lesson('final-checklist', 'Final Checklist and Next Steps', LessonContentType::Text, 480, 'Pre-exam checklist and post-course study suggestions.', resources: [
                    ['title' => 'Practice Sheet', 'file_type' => ResourceType::Pdf, 'file' => 'final-review-sheet.pdf'],
                ]),
            ]),
        ];
    }

    /**
     * @return list<array{slug: string, title: string, description: string, lessons: list<array<string, mixed>>}>
     */
    private function spokenBeginnerSectionTemplates(): array
    {
        return [
            $this->section('introduction', 'Introduction', 'Getting started with spoken English.', [
                $this->lesson('welcome-beginners', 'Welcome & Classroom Rules', LessonContentType::Video, 420, 'Meet your instructor and learn how to use the course.', 'https://example.com/demo/spoken/welcome'),
                $this->lesson('alphabet-sounds', 'Alphabet and Basic Sounds', LessonContentType::Video, 900, 'Pronunciation of vowels and consonants.', 'https://example.com/demo/spoken/sounds'),
                $this->lesson('self-introduction', 'Introducing Yourself', LessonContentType::Text, 600, 'Phrases for name, origin, and daily routine.'),
            ]),
            $this->section('foundation-lessons', 'Foundation Lessons', 'Everyday vocabulary and sentence patterns.', [
                $this->lesson('greetings', 'Greetings and Polite Expressions', LessonContentType::Video, 720, 'Hello, goodbye, please, thank you, and apologies.', 'https://example.com/demo/spoken/greetings'),
                $this->lesson('numbers-time', 'Numbers, Dates, and Time', LessonContentType::Text, 840, 'Ask and answer questions about schedules.'),
                $this->lesson('shopping-phrases', 'At the Shop', LessonContentType::Text, 780, 'Useful phrases for buying food and clothes.'),
                $this->lesson('directions', 'Asking for Directions', LessonContentType::Quiz, 900, 'Match phrases to real-world scenarios.'),
            ]),
            $this->section('practice-lessons', 'Practice Lessons', 'Guided speaking drills and role-play.', [
                $this->lesson('roleplay-cafe', 'Role-play: Ordering at a Cafe', LessonContentType::Video, 960, 'Practice a full cafe conversation.', 'https://example.com/demo/spoken/cafe'),
                $this->lesson('roleplay-doctor', 'Role-play: At the Doctor', LessonContentType::Video, 1020, 'Describe symptoms and ask for advice.', 'https://example.com/demo/spoken/doctor'),
                $this->lesson('listening-drill-1', 'Listening Drill: Daily Dialogues', LessonContentType::Quiz, 840, 'Comprehension quiz on short conversations.'),
                $this->lesson('speaking-drill-1', 'Speaking Drill: Repeat and Record', LessonContentType::Text, 720, 'Record yourself using the prompt cards.', resources: [
                    ['title' => 'Practice Sheet', 'file_type' => ResourceType::Pdf, 'file' => 'speaking-drill-sheet.pdf'],
                ]),
            ]),
            $this->section('mock-test-assignment', 'Mock Test / Assignment', 'Apply skills in structured assignments.', [
                $this->lesson('conversation-assignment', 'Assignment: 3-Minute Conversation', LessonContentType::Text, 1200, 'Record a conversation using five target phrases.'),
                $this->lesson('listening-quiz', 'Listening Quiz: Everyday English', LessonContentType::Quiz, 900, 'Multiple-choice quiz on short audio clips.'),
                $this->lesson('presentation-task', 'Mini Presentation Task', LessonContentType::Live, 1500, 'Present a hobby or daily routine to the class.'),
            ]),
        ];
    }

    /**
     * @return list<array{slug: string, title: string, description: string, lessons: list<array<string, mixed>>}>
     */
    private function spokenKidsSectionTemplates(): array
    {
        return [
            $this->section('introduction', 'Introduction', 'Fun start for young learners.', [
                $this->lesson('hello-song', 'Hello Song and Names', LessonContentType::Video, 360, 'Sing along and learn to say your name.', 'https://example.com/demo/kids/hello'),
                $this->lesson('colors-shapes', 'Colors and Shapes', LessonContentType::Video, 480, 'Identify colors and shapes in pictures.', 'https://example.com/demo/kids/colors'),
                $this->lesson('classroom-words', 'Classroom Words', LessonContentType::Text, 420, 'Book, pen, chair, table, and teacher.'),
            ]),
            $this->section('foundation-lessons', 'Foundation Lessons', 'Simple words and phrases for kids.', [
                $this->lesson('animals', 'Farm and Zoo Animals', LessonContentType::Video, 540, 'Learn animal names and sounds.', 'https://example.com/demo/kids/animals'),
                $this->lesson('family', 'My Family', LessonContentType::Text, 480, 'Mother, father, sister, brother, and pets.'),
                $this->lesson('food', 'Favorite Foods', LessonContentType::Text, 420, 'Name fruits, snacks, and meals.'),
                $this->lesson('actions', 'Action Verbs', LessonContentType::Quiz, 600, 'Jump, run, sit, clap — match words to actions.'),
            ]),
            $this->section('practice-lessons', 'Practice Lessons', 'Games and interactive speaking.', [
                $this->lesson('story-time', 'Picture Story Time', LessonContentType::Video, 720, 'Follow a short story and repeat key lines.', 'https://example.com/demo/kids/story'),
                $this->lesson('show-and-tell', 'Show and Tell Practice', LessonContentType::Live, 900, 'Bring a toy and describe it in simple sentences.'),
                $this->lesson('listening-game', 'Listening Game: Simon Says', LessonContentType::Quiz, 480, 'Follow spoken instructions in a fun game.'),
            ]),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $lessons
     * @return array{slug: string, title: string, description: string, lessons: list<array<string, mixed>>}
     */
    private function section(string $slug, string $title, string $description, array $lessons): array
    {
        return compact('slug', 'title', 'description', 'lessons');
    }

    /**
     * @param  list<array{title: string, file_type: ResourceType, file: string}>  $resources
     * @return array<string, mixed>
     */
    private function lesson(
        string $slug,
        string $title,
        LessonContentType|string $contentType,
        int $durationSeconds,
        string $content,
        ?string $videoUrl = null,
        array $resources = [],
    ): array {
        return Arr::whereNotNull([
            'slug' => $slug,
            'title' => $title,
            'content_type' => $contentType instanceof LessonContentType ? $contentType->value : $contentType,
            'duration_seconds' => $durationSeconds,
            'content' => $content,
            'video_url' => $videoUrl,
            'resources' => $resources === [] ? null : $resources,
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\Badge;
use App\Models\Certificate;
use App\Models\CompetencyCategory;
use App\Models\CompetencyProgress;
use App\Models\CompetencyUnit;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\FacultyCode;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizSet;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * DatabaseSeeder — brings a fresh clone up to a usable state.
 *
 * database.sqlite is gitignored, so without this a new checkout has no
 * roles, no accounts and nothing to click on. Run with:
 *
 *     php artisan migrate:fresh --seed
 *
 * Login for every seeded account: password123
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedRoles();

        $admin = $this->seedAdmin();
        $faculty = $this->seedFaculty();
        $students = $this->seedStudents();

        $this->seedFacultyCodes($admin, $faculty);
        $courses = $this->seedCourses($faculty);
        $this->seedEnrolments($courses, $students);
        $this->seedAnalyticsCredentials($courses, $students);
        $this->seedAnalyticsCompetencies($students);
    }

    private function seedRoles(): void
    {
        foreach ([
            ['id' => User::ROLE_ADMIN,   'name' => 'admin',   'display_name' => 'Administrator'],
            ['id' => User::ROLE_FACULTY, 'name' => 'faculty', 'display_name' => 'Faculty'],
            ['id' => User::ROLE_STUDENT, 'name' => 'student', 'display_name' => 'Student'],
            ['id' => User::ROLE_SUPERADMIN, 'name' => 'superadmin', 'display_name' => 'Superadmin'],
            ['id' => User::ROLE_SUBADMIN, 'name' => 'subadmin', 'display_name' => 'Sub-admin'],
        ] as $role) {
            Role::updateOrCreate(['id' => $role['id']], $role);
        }
    }

    private function seedAdmin(): User
    {
        return User::updateOrCreate(
            ['user_code' => '26-AD-0001'],
            [
                'email' => 'admin@upskill.test',
                'first_name' => 'Ramon',
                'last_name' => 'Aquino',
                'username' => 'raquino',
                'password' => Hash::make('password123'),
                'role_id' => User::ROLE_SUPERADMIN,
                'user_code' => '26-AD-0001',
                'student_id' => '26-AD-0001',
                'is_active' => true,
                'profile_completed' => true,
            ]
        );
    }

    private function seedFaculty(): User
    {
        return User::updateOrCreate(
            ['user_code' => '26-FC-0001'],
            [
                'email' => 'faculty@upskill.test',
                'first_name' => 'Liza',
                'last_name' => 'Ferrer',
                'username' => 'lferrer',
                'password' => Hash::make('password123'),
                'role_id' => User::ROLE_FACULTY,
                'user_code' => '26-FC-0001',
                'student_id' => '26-FC-0001',
                'phone' => '09171234567',
                'location' => 'Pangasinan',
                'is_active' => true,
                'profile_completed' => true,
            ]
        );
    }

    /** @return Collection<int, User> */
    private function seedStudents()
    {
        // Explicit usernames rather than a generated "name+index" — the old
        // scheme produced logins like "juan0", which are awkward to read out
        // and to type on a phone during a demo.
        $students = [
            ['Miguel', 'Reyes',      'mreyes'],
            ['Carmen', 'Bautista',   'cbautista'],
            ['Noel',   'Villanueva', 'nvillanueva'],
            ['Angela', 'Santos',     'asantos'],
            ['Paolo',  'Garcia',     'pgarcia'],
            ['Jessa',  'Mendoza',    'jmendoza'],
            ['Rafael', 'Cruz',       'rcruz'],
            ['Sofia',  'Navarro',    'snavarro'],
            ['Diego',  'Ramos',      'dramos'],
            ['Leah',   'Flores',     'lflores'],
            ['Marco',  'Torres',     'mtorres'],
            ['Nina',   'Castillo',   'ncastillo'],
        ];

        return collect($students)->values()->map(function (array $s, int $i) {
            [$first, $last, $username] = $s;

            $code = $i < 3 ? sprintf('26-LN-%04d', $i + 1) : sprintf('26-DEMO-%04d', $i + 1);
            $email = strtolower($first).'.'.strtolower(str_replace(' ', '', $last)).'@upskill.test';

            return User::updateOrCreate(
                ['email' => $email],
                [
                    'first_name' => $first,
                    'last_name' => $last,
                    'username' => $username,
                    'password' => Hash::make('password123'),
                    'role_id' => User::ROLE_STUDENT,
                    'user_code' => $code,
                    'student_id' => $code,
                    'is_active' => true,
                    // Left false on purpose so the profile-completion form
                    // on the student dashboard can actually be exercised.
                    'profile_completed' => $i > 0,
                ]
            );
        });
    }

    private function seedFacultyCodes(User $admin, User $faculty): void
    {
        FacultyCode::updateOrCreate(
            ['code' => 'FAC-DEMO01'],
            ['created_by' => $admin->id, 'used_by' => $faculty->id, 'used_at' => now()]
        );

        // Two spare codes so faculty registration can be tested.
        foreach (['FAC-SPARE1', 'FAC-SPARE2'] as $code) {
            FacultyCode::updateOrCreate(
                ['code' => $code],
                ['created_by' => $admin->id, 'used_by' => null, 'used_at' => null]
            );
        }
    }

    /** @return Collection<int, Course> */
    private function seedCourses(User $faculty): Collection
    {
        $definitions = [
            [
                'course' => [
                    'title' => 'Introduction to Web Development',
                    'heading' => 'Build your first accessible website',
                    'subheading' => 'A practical introduction to the technologies behind the web.',
                    'slug' => 'intro-to-web-development',
                    'short_description' => 'Learn how the web works and create responsive pages with HTML, CSS, and JavaScript.',
                    'description' => 'This beginner course introduces the browser, the web request lifecycle, semantic HTML, accessible forms, responsive CSS, and JavaScript interactions. Learners finish with a small, well-structured website and the skills to continue into frontend development.',
                    'skills' => ['HTML', 'CSS', 'JavaScript', 'Accessibility', 'Responsive Design'],
                    'related_skills' => ['Web Development', 'Frontend Development', 'UI Implementation'],
                    'objectives' => [
                        'Explain how browsers request and display web pages.',
                        'Create semantic HTML pages and accessible forms.',
                        'Apply responsive CSS layouts and basic JavaScript interactions.',
                        'Publish a small website and evaluate it for usability.',
                    ],
                    'category' => 'Information Technology',
                    'program' => 'BS Information Technology',
                    'term' => 'Open enrollment',
                    'level' => 'Beginner',
                    'pqf_level' => 'Level 5',
                    'target_learners' => 'Beginners preparing to build websites or continue into frontend development.',
                    'delivery_mode' => 'Self-paced online',
                    'duration' => '4 weeks',
                    'learning_hours' => 20,
                    'assessment_strategy' => 'Short knowledge checks, lesson practice, and a final responsive website project.',
                    'grading_rubric' => 'Pass with at least 75% on the knowledge check and a project that meets the structure, accessibility, and responsive-layout criteria.',
                    'mastery_passing_percent' => 75,
                    'certificate_enabled' => true,
                    'certificate_title' => 'Certificate in Web Development Foundations',
                    'certificate_mode' => 'auto',
                    'instructor' => $faculty->name,
                    'created_by' => $faculty->id,
                    'passing_score' => 75,
                    'is_featured' => true,
                    'is_published' => true,
                    'approval_status' => 'approved',
                    'is_approved' => true,
                    'approved_at' => now(),
                ],
                'modules' => [
                    [
                        'title' => 'How the Web Works',
                        'description' => 'Explore browsers, servers, URLs, and the request-and-response cycle that delivers a page.',
                        'lessons' => [
                            [
                                'title' => 'Browsers, Servers, and Web Pages',
                                'duration' => '12',
                                'content' => '<h2>The parts of a web page</h2><p>A website is delivered through communication between a client and a server. The browser is the client: it requests a resource, receives a response, and turns the returned HTML, CSS, images, and scripts into the page you see.</p><p>A URL identifies where a resource can be found. Its scheme, host, path, and optional query parameters help the browser make the right request. The server responds with a status code and content; the browser then parses and renders that content.</p><h3>Remember</h3><ul><li>HTML describes page structure.</li><li>CSS controls presentation and layout.</li><li>JavaScript adds behavior in the browser.</li></ul>',
                            ],
                            [
                                'title' => 'URLs and the Request Lifecycle',
                                'duration' => '14',
                                'content' => '<h2>Follow a request</h2><p>A browser resolves the host in a URL, opens a connection, and sends an HTTP request for a resource. The path identifies the resource and a query string can carry additional values. A GET request commonly retrieves information, while a POST request commonly submits data.</p><p>The response includes a status code and headers as well as a body when content is returned. A successful response may contain a page; a redirect points the browser to another URL; a not-found response means the requested resource could not be located. Browser developer tools let you inspect requests and diagnose missing files.</p><h3>Try it</h3><p>Open the network panel, reload a page, and identify its document request, stylesheet, and one response status.</p>',
                            ],
                        ],
                    ],
                    [
                        'title' => 'Semantic HTML and Accessibility',
                        'description' => 'Structure information with meaningful elements and make common form interactions clear to everyone.',
                        'lessons' => [
                            [
                                'title' => 'Page Structure and Semantic Elements',
                                'duration' => '16',
                                'content' => '<h2>Give content meaningful structure</h2><p>Semantic HTML describes the purpose of content, not only how it looks. Use headings in a logical hierarchy, paragraphs for prose, lists for grouped items, and landmarks such as header, navigation, main, and footer to help people and assistive technologies understand a page.</p><p>Choose an element that matches the action: links navigate to another location, while buttons perform an action on the current page. Good structure also makes code easier to maintain because the intent of each region is clear.</p><h3>Practice</h3><p>Outline a simple course page with one main heading, a navigation area, two sections, and a footer before adding visual styles.</p>',
                            ],
                            [
                                'title' => 'Accessible Forms and Controls',
                                'duration' => '16',
                                'content' => '<h2>Make forms understandable</h2><p>Every input needs a visible, descriptive label that is programmatically associated with the control. Group related choices together and explain required formats before a person submits the form. Do not use placeholder text as the only label because it disappears as the person types.</p><p>When validation fails, identify the field, explain how to fix it, and keep the entered values where possible. Ensure controls can be reached and operated by keyboard, and provide a visible focus indicator.</p><ul><li>Use labels that describe the expected information.</li><li>Show errors next to the relevant field.</li><li>Check focus order and contrast.</li></ul>',
                            ],
                        ],
                    ],
                    [
                        'title' => 'Responsive Styling and Interactions',
                        'description' => 'Use CSS layout tools and small JavaScript behaviors to complete a responsive page.',
                        'lessons' => [
                            [
                                'title' => 'CSS Layouts for Different Screens',
                                'duration' => '18',
                                'content' => '<h2>Build flexible layouts</h2><p>CSS separates presentation from document structure. Use flexible sizing and layout systems such as Flexbox or Grid to arrange content, and let elements adapt to the available space instead of assuming one fixed screen width.</p><p>Responsive design starts with readable content on a narrow screen, then adds layout changes when there is enough room. Use media queries for meaningful breakpoints, keep text comfortable to read, and check that controls do not overflow or become too small to use.</p><h3>Practice</h3><p>Create a two-column card layout that becomes a single column on a narrow viewport, then test it at several widths.</p>',
                            ],
                            [
                                'title' => 'JavaScript and the Final Page',
                                'duration' => '18',
                                'content' => '<h2>Add purposeful interaction</h2><p>JavaScript can respond to user events and update the page. Select an element, listen for a specific event such as a button click, and change the interface in a way that is visible and understandable. Keep behavior focused so that the page remains usable when scripts are delayed or unavailable.</p><p>For the final page, combine semantic structure, accessible controls, and responsive styling. Test the main journey with a keyboard and a small viewport. Check links, form feedback, and browser-console errors before publishing the project.</p><ul><li>Give every action a clear result.</li><li>Preserve accessible names and focus states.</li><li>Verify the page at more than one screen width.</li></ul>',
                            ],
                        ],
                    ],
                ],
                'quiz' => [
                    'question' => 'What does HTML stand for?',
                    'options' => ['HyperText Markup Language', 'Hyperlink Text Mode', 'Home Tool Markup Language'],
                    'correct_answer' => 'HyperText Markup Language',
                ],
            ],
            [
                'course' => [
                    'title' => 'Data Structures & Algorithms',
                    'heading' => 'Choose the right structure for the problem',
                    'subheading' => 'Build a practical foundation in algorithmic thinking and core data structures.',
                    'slug' => 'data-structures-algorithms',
                    'short_description' => 'Analyze algorithms and use common data structures to solve programming problems.',
                    'description' => 'Learn to describe a problem precisely, estimate algorithm cost, and select data structures that fit the work. The course moves from arrays, linked lists, stacks, and queues to trees and graph traversal, using practical examples and implementation exercises.',
                    'skills' => ['Algorithm Design', 'Big-O Analysis', 'Arrays', 'Stacks and Queues', 'Trees', 'Graphs'],
                    'related_skills' => ['Programming Logic', 'Problem Solving', 'Software Development'],
                    'objectives' => [
                        'Translate a problem into inputs, outputs, and clear constraints.',
                        'Compare time and space complexity using Big-O notation.',
                        'Choose and implement arrays, linked lists, stacks, and queues.',
                        'Traverse trees and graphs with standard search strategies.',
                    ],
                    'category' => 'Information Technology',
                    'program' => 'Computer Science',
                    'term' => 'Open enrollment',
                    'level' => 'Intermediate',
                    'pqf_level' => 'Level 6',
                    'target_learners' => 'Learners with basic programming experience who want to strengthen problem solving and computer science foundations.',
                    'delivery_mode' => 'Self-paced online',
                    'duration' => '6 weeks',
                    'learning_hours' => 36,
                    'assessment_strategy' => 'Module knowledge checks, implementation exercises, complexity analysis, and a final problem-solving task.',
                    'grading_rubric' => 'Pass with at least 75% on the knowledge check and a final solution that is correct, clearly reasoned, and appropriate in time and space complexity.',
                    'mastery_passing_percent' => 75,
                    'certificate_enabled' => true,
                    'certificate_title' => 'Certificate in Data Structures and Algorithms',
                    'certificate_mode' => 'auto',
                    'instructor' => $faculty->name,
                    'created_by' => $faculty->id,
                    'passing_score' => 75,
                    'is_featured' => false,
                    'is_published' => true,
                    'approval_status' => 'approved',
                    'is_approved' => true,
                    'approved_at' => now(),
                ],
                'modules' => [
                    [
                        'title' => 'Problem Solving and Complexity',
                        'description' => 'Define computational problems, plan solutions, and compare their resource requirements.',
                        'lessons' => [
                            [
                                'title' => 'Breaking Problems into Steps',
                                'duration' => '18',
                                'content' => '<h2>Start with a precise problem</h2><p>Before writing code, state what information is provided, what result is required, and which constraints matter. Small examples help reveal edge cases and clarify whether the result must be ordered, unique, or optimal.</p><p>Turn the solution into a sequence of steps that can be checked independently. For a search task, for example, identify the collection, the comparison rule, and the outcome when no match exists. This separates the reasoning from the syntax of a particular language.</p><h3>Practice</h3><p>Write down inputs, outputs, assumptions, and two edge cases for a task before implementing it.</p>',
                            ],
                            [
                                'title' => 'Reading Time and Space Complexity',
                                'duration' => '20',
                                'content' => '<h2>Estimate how work grows</h2><p>Big-O notation describes how an algorithm scales as its input grows. A single pass through n items is linear, O(n). Comparing every pair can be quadratic, O(n squared), while repeatedly halving a search range is logarithmic, O(log n).</p><p>Complexity focuses on the growth trend rather than exact machine time. Also consider space: a solution may trade additional memory for fewer repeated calculations. When comparing approaches, state the input size and the operation that dominates.</p><ul><li>Count repeated work.</li><li>Identify nested loops and recursion.</li><li>Include meaningful extra storage in the space estimate.</li></ul>',
                            ],
                        ],
                    ],
                    [
                        'title' => 'Linear Data Structures',
                        'description' => 'Understand the access and update trade-offs of arrays, linked lists, stacks, and queues.',
                        'lessons' => [
                            [
                                'title' => 'Arrays and Linked Lists',
                                'duration' => '20',
                                'content' => '<h2>Sequential collections</h2><p>Arrays store items in indexed positions and provide fast access when the index is known. Inserting into the middle may require shifting later items. Linked lists connect nodes with references; they can make local insertion convenient once a position is found, but accessing an arbitrary item requires traversal.</p><p>Choose based on the operations your program performs most often. Consider whether the collection size changes frequently, whether random access is needed, and how much memory overhead is acceptable.</p><h3>Compare</h3><p>Trace a lookup and an insertion for both structures, then explain how the amount of work changes as the collection grows.</p>',
                            ],
                            [
                                'title' => 'Stacks and Queues',
                                'duration' => '18',
                                'content' => '<h2>Order of processing</h2><p>A stack follows last-in, first-out order: the most recently added item is removed first. Stacks are useful for undo history, nested expressions, and depth-first traversal. A queue follows first-in, first-out order and is useful for task scheduling, request handling, and breadth-first traversal.</p><p>Both structures expose a small set of operations such as push or enqueue, and pop or dequeue. Keeping operations constrained makes the intended ordering explicit and prevents callers from depending on internal storage details.</p><ul><li>Use a stack when the newest pending item comes first.</li><li>Use a queue when pending work should be handled in arrival order.</li></ul>',
                            ],
                        ],
                    ],
                    [
                        'title' => 'Trees, Graphs, and Search',
                        'description' => 'Model hierarchical and connected information and apply traversal strategies to explore it.',
                        'lessons' => [
                            [
                                'title' => 'Tree Structure and Traversal',
                                'duration' => '20',
                                'content' => '<h2>Representing hierarchy</h2><p>A tree consists of nodes connected by parent-child relationships. The root begins the hierarchy, leaves have no children, and each node may represent a category, folder, or decision. Binary search trees order values so that smaller and larger values can be searched on different sides.</p><p>Depth-first traversals explore a branch before returning; breadth-first traversal visits nodes level by level. Choose a traversal based on whether you need a path, ordered output, or the nearest level containing a match.</p>',
                            ],
                            [
                                'title' => 'Graph Models and Search Strategies',
                                'duration' => '22',
                                'content' => '<h2>Model relationships</h2><p>A graph represents entities as vertices and relationships as edges. Edges may be directed or undirected, and may carry weights such as distance or cost. An adjacency list stores each vertex with its neighbors and is effective for many sparse graphs.</p><p>Depth-first search explores one route deeply; breadth-first search explores by distance in an unweighted graph. Keep a visited set so cycles do not cause repeated work. For a weighted route, use an algorithm designed to account for edge costs rather than treating every connection equally.</p><h3>Practice</h3><p>Draw a small campus map as a graph, identify its vertices and edges, then trace a breadth-first search from one building.</p>',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'course' => [
                    'title' => 'Mobile App Development',
                    'heading' => 'Design and build a useful mobile app',
                    'subheading' => 'Plan mobile experiences, manage application state, and prepare a reliable release.',
                    'slug' => 'mobile-app-development',
                    'short_description' => 'Learn mobile interface patterns, app state, data handling, testing, and release fundamentals.',
                    'description' => 'This course covers the foundations of mobile app development, from translating user needs into screen flows to managing state and device data. Learners practice building a small application, handling common failure cases, testing important journeys, and preparing a release checklist.',
                    'skills' => ['Mobile UI', 'Application State', 'Data Persistence', 'API Integration', 'Testing'],
                    'related_skills' => ['User Experience Design', 'Software Development', 'Quality Assurance'],
                    'objectives' => [
                        'Plan mobile screens and navigation around user tasks.',
                        'Build interfaces that adapt to different screen sizes and input methods.',
                        'Manage application state and handle loading, empty, and error conditions.',
                        'Test key user journeys and prepare an app for release.',
                    ],
                    'category' => 'Information Technology',
                    'program' => 'Computer Science',
                    'term' => 'Open enrollment',
                    'level' => 'Intermediate',
                    'pqf_level' => 'Level 6',
                    'target_learners' => 'Learners with basic programming knowledge who want to develop or maintain mobile applications.',
                    'delivery_mode' => 'Self-paced online',
                    'duration' => '6 weeks',
                    'learning_hours' => 40,
                    'assessment_strategy' => 'Design exercises, working app milestones, module knowledge checks, and a final tested application prototype.',
                    'grading_rubric' => 'Pass with at least 75% on the knowledge check and a prototype that completes its core user journey, handles common states, and passes the documented test checklist.',
                    'mastery_passing_percent' => 75,
                    'certificate_enabled' => true,
                    'certificate_title' => 'Certificate in Mobile App Development',
                    'certificate_mode' => 'auto',
                    'instructor' => $faculty->name,
                    'created_by' => $faculty->id,
                    'passing_score' => 75,
                    'is_featured' => false,
                    'is_published' => true,
                    'approval_status' => 'approved',
                    'is_approved' => true,
                    'approved_at' => now(),
                ],
                'modules' => [
                    [
                        'title' => 'Mobile Foundations and Interface Design',
                        'description' => 'Plan mobile user journeys and create interfaces that work across devices and accessibility needs.',
                        'lessons' => [
                            [
                                'title' => 'Mobile App Structure and User Journeys',
                                'duration' => '20',
                                'content' => '<h2>Begin with the user task</h2><p>A mobile app should make its main task easy to discover and complete on a small screen. Describe the user, their goal, and the context in which they use the app before deciding which screens to build.</p><p>Map the journey from entry to completion, including cancellation and recovery. Keep navigation predictable, make important actions visible, and avoid asking for information that is not needed at that step.</p><h3>Practice</h3><p>Sketch a three-screen flow for a simple task and label the information and actions each screen needs.</p>',
                            ],
                            [
                                'title' => 'Responsive Layout and Accessible Controls',
                                'duration' => '20',
                                'content' => '<h2>Design for real devices</h2><p>Mobile devices vary in screen size, orientation, text scale, and input method. Use flexible layouts, allow content to scroll when necessary, and verify that important controls remain reachable without depending on a precise tap.</p><p>Provide clear labels, sufficient contrast, and meaningful focus or selection feedback. Do not rely on color alone to communicate status. Test the interface with larger text and with keyboard or assistive technology where available.</p>',
                            ],
                        ],
                    ],
                    [
                        'title' => 'Application State and Data',
                        'description' => 'Keep the interface consistent as data loads, changes, and persists between app sessions.',
                        'lessons' => [
                            [
                                'title' => 'Application State and Screen Updates',
                                'duration' => '20',
                                'content' => '<h2>Make state visible</h2><p>Application state is the information that determines what the user sees and what actions are available. A screen may be loading, ready, empty, or displaying an error. Represent these states deliberately so that the interface does not appear frozen or silently fail.</p><p>Keep one clear source of truth for each piece of data. When an action changes state, update the relevant view and provide feedback. Consider what should happen when the user navigates away and then returns.</p><ul><li>Show progress for work that takes time.</li><li>Explain empty results and offer a next step.</li><li>Preserve user input when a recoverable error occurs.</li></ul>',
                            ],
                            [
                                'title' => 'Local Data, APIs, and Failure Handling',
                                'duration' => '22',
                                'content' => '<h2>Work with data safely</h2><p>Apps may store preferences or drafts locally and request shared information from an API. Decide which data can be cached, how it is refreshed, and how the app behaves without a network connection. Validate data before using it and handle missing or unexpected fields.</p><p>Network requests can be slow, unavailable, or rejected. Show an understandable error, provide a retry when it is safe, and avoid duplicating a user action when the original request may already have succeeded.</p>',
                            ],
                        ],
                    ],
                    [
                        'title' => 'Testing and Release Readiness',
                        'description' => 'Test the app around user outcomes, then prepare a practical release checklist.',
                        'lessons' => [
                            [
                                'title' => 'Testing Core Mobile User Flows',
                                'duration' => '20',
                                'content' => '<h2>Test behavior, not only screens</h2><p>Choose tests from the most important user journeys. Verify the expected result, but also check empty input, slow data, interrupted navigation, and recovery after an error. Repeat critical flows on more than one screen size or platform configuration when possible.</p><p>Separate focused logic tests from UI interaction tests. A small, reliable set of checks around app behavior can catch regressions earlier than manually exploring every screen after each change.</p>',
                            ],
                            [
                                'title' => 'Preparing an App for Release',
                                'duration' => '18',
                                'content' => '<h2>Review before publishing</h2><p>A release candidate should have a clear app name and description, a tested build, and the permissions required for its features. Confirm that debug-only behavior is disabled and that errors do not expose private information.</p><p>Use a checklist to verify the main journey, accessibility basics, data and network failure behavior, and the installation experience. Record known limitations and a plan for monitoring feedback after release.</p><ul><li>Test a clean install and an upgrade where available.</li><li>Confirm that the app requests only necessary permissions.</li><li>Document the build version and release notes.</li></ul>',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return collect($definitions)->map(function (array $definition, int $courseIndex): Course {
            $course = Course::updateOrCreate(
                ['slug' => $definition['course']['slug']],
                $definition['course']
            );

            foreach ($definition['modules'] as $moduleIndex => $moduleDefinition) {
                $module = CourseModule::updateOrCreate(
                    ['course_id' => $course->id, 'order' => $moduleIndex + 1],
                    [
                        'title' => $moduleDefinition['title'],
                        'description' => $moduleDefinition['description'],
                    ]
                );

                foreach ($moduleDefinition['lessons'] as $lessonIndex => $lessonDefinition) {
                    CourseLesson::updateOrCreate(
                        ['course_id' => $course->id, 'module_id' => $module->id, 'order' => $lessonIndex + 1],
                        [
                            'title' => $lessonDefinition['title'],
                            'type' => 'Text',
                            'duration' => $lessonDefinition['duration'],
                            'content' => $lessonDefinition['content'],
                            'module_order' => ($lessonIndex + 1) * 1000000,
                        ]
                    );
                }

                if ($courseIndex === 0 && $moduleIndex === 0) {
                    $quiz = Quiz::updateOrCreate(
                        ['course_id' => $course->id, 'module_id' => $module->id],
                        [
                            'title' => $moduleDefinition['title'].' Quiz',
                            'description' => 'Check your understanding of web fundamentals.',
                            'instructions' => 'Choose the best answer for each question.',
                            'passing_score' => 75,
                            'is_active' => true,
                        ]
                    );

                    $set = QuizSet::firstOrCreate(
                        ['quiz_id' => $quiz->id, 'position' => 0],
                        ['name' => 'Set A']
                    );

                    QuizQuestion::updateOrCreate(
                        ['quiz_id' => $quiz->id, 'question' => $definition['quiz']['question']],
                        [
                            'quiz_set_id' => $set->id,
                            'position' => 0,
                            'type' => 'Multiple Choice',
                            'options' => $definition['quiz']['options'],
                            'correct_answer' => $definition['quiz']['correct_answer'],
                            'points' => 1,
                        ]
                    );
                }
            }

            $course->update(['lessons_count' => $course->lessons()->count()]);

            return $course;
        });
    }

    /** @param Collection<int, Course> $courses */
    private function seedEnrolments($courses, $students): void
    {
        foreach ($courses as $courseIndex => $course) {
            foreach ($students as $studentIndex => $student) {
                $progress = (($studentIndex * 19) + ($courseIndex * 13)) % 101;
                Enrollment::updateOrCreate(
                    ['user_id' => $student->id, 'course_id' => $course->id],
                    [
                        'enrolled_at' => now()->subMonths(($studentIndex + $courseIndex) % 9)->subDays($studentIndex),
                        'is_completed' => $progress >= 80,
                        'progress_percent' => $progress,
                        'progress_state' => ['completed_lessons' => [], 'module_scores' => []],
                    ]
                );
            }

            $course->update(['enrolled_count' => $course->enrollments()->count()]);
        }
    }

    /** @param Collection<int, Course> $courses */
    private function seedAnalyticsCredentials($courses, $students): void
    {
        $badges = collect(['Web Foundations', 'Algorithm Builder', 'Mobile Creator', 'Design Thinker'])->mapWithKeys(function (string $name) {
            $badge = Badge::updateOrCreate(
                ['name' => $name],
                ['description' => 'Analytics demo badge', 'is_active' => true, 'badge_level' => 'Foundational']
            );

            return [$name => $badge];
        });

        foreach ($students as $studentIndex => $student) {
            foreach ($badges->values()->take(($studentIndex % 4) + 1) as $badgeIndex => $badge) {
                DB::table('user_badges')->updateOrInsert(
                    ['user_id' => $student->id, 'badge_id' => $badge->id],
                    ['earned_at' => now()->subMonths(($studentIndex + $badgeIndex) % 9), 'updated_at' => now(), 'created_at' => now()]
                );
            }

            foreach ($courses->take(3) as $courseIndex => $course) {
                $enrollment = Enrollment::where('user_id', $student->id)->where('course_id', $course->id)->first();
                if ($enrollment?->is_completed) {
                    Certificate::updateOrCreate(
                        ['user_id' => $student->id, 'course_id' => $course->id],
                        ['serial' => sprintf('DEMO-%04d-%02d', $student->id, $courseIndex + 1), 'title' => 'Certificate of Completion', 'issued_at' => now()->subMonths(($studentIndex + $courseIndex) % 9)]
                    );
                }
            }
        }

        foreach ($courses as $courseIndex => $course) {
            $quiz = Quiz::updateOrCreate(
                ['course_id' => $course->id, 'title' => $course->title.' Assessment'],
                ['passing_score' => $course->passing_score, 'is_active' => true]
            );

            foreach ($students->take(8) as $studentIndex => $student) {
                DB::table('quiz_attempts')->updateOrInsert(
                    ['user_id' => $student->id, 'quiz_id' => $quiz->id],
                    ['score' => 62 + (($studentIndex * 7 + $courseIndex * 3) % 35), 'passed' => $studentIndex % 4 !== 0, 'submitted_at' => now()->subMonths(($studentIndex + $courseIndex) % 9), 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }
    }

    private function seedAnalyticsCompetencies($students): void
    {
        $definitions = [
            'Web Development' => ['HTML & CSS', 'Frontend Foundations'],
            'Programming' => ['Programming Logic', 'Algorithms'],
            'Database Management' => ['Relational Data', 'SQL Queries'],
            'Mobile Development' => ['Mobile UI', 'Mobile Services'],
        ];

        foreach ($definitions as $categoryName => $unitNames) {
            $category = CompetencyCategory::updateOrCreate(
                ['name' => $categoryName],
                ['description' => 'Analytics demo competency area', 'is_active' => true]
            );

            foreach ($unitNames as $unitIndex => $unitName) {
                $unit = CompetencyUnit::updateOrCreate(
                    ['competency_category_id' => $category->id, 'title' => $unitName],
                    ['description' => 'Analytics demo competency unit', 'order' => $unitIndex + 1, 'is_active' => true]
                );

                foreach ($students->take(6) as $studentIndex => $student) {
                    $score = max(35, 88 - ($unitIndex * 8) - (($studentIndex + $category->id) % 15));
                    $assessment = Assessment::updateOrCreate(
                        ['user_id' => $student->id, 'competency_unit_id' => $unit->id],
                        ['type' => 'Competency Review', 'title' => $unitName.' Review', 'passing_score' => 70, 'status' => $studentIndex === 5 ? 'pending' : 'completed', 'score' => $studentIndex === 5 ? null : $score]
                    );

                    CompetencyProgress::updateOrCreate(
                        ['user_id' => $student->id, 'competency_unit_id' => $unit->id],
                        ['assessment_id' => $assessment->id, 'status' => $studentIndex === 5 ? 'pending' : 'completed', 'mastery_score' => $score, 'completed_at' => $studentIndex === 5 ? null : now()->subMonths($studentIndex % 6)]
                    );
                }
            }
        }
    }
}

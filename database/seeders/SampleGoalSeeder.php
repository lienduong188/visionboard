<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Goal;
use App\Models\Milestone;
use App\Models\User;
use Illuminate\Database\Seeder;

class SampleGoalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Sample goals for v!t's 2026 VisionBoard
     */
    public function run(): void
    {
        $user = User::first();

        if (!$user) {
            $this->command->warn('No user found. Please create a user first.');
            return;
        }

        $goals = [
            // 1. Trả nợ Credit Card
            [
                'category_slug' => 'career-finance',
                'title' => 'Trả hết nợ Credit Card',
                'description' => 'Trả hết 200万円 nợ credit card trong năm 2026',
                'target_value' => 2000000,
                'current_value' => 0,
                'unit' => '円',
                'start_date' => '2026-01-01',
                'target_date' => '2026-12-31',
                'priority' => 'high',
                'status' => 'in_progress',
                'milestones' => [
                    ['title' => 'Trả 500,000円 (Q1)', 'target_value' => 500000, 'due_date' => '2026-03-31'],
                    ['title' => 'Trả 1,000,000円 (Q2)', 'target_value' => 1000000, 'due_date' => '2026-06-30'],
                    ['title' => 'Trả 1,500,000円 (Q3)', 'target_value' => 1500000, 'due_date' => '2026-09-30'],
                    ['title' => 'Trả hết 2,000,000円 (Q4)', 'target_value' => 2000000, 'due_date' => '2026-12-31'],
                ],
            ],

            // 2. Full Marathon Tottori
            [
                'category_slug' => 'health-fitness',
                'title' => 'Full Marathon Finisher - Tottori',
                'description' => 'Hoàn thành giải Full Marathon tại Tottori (42.195km)',
                'target_value' => 42.195,
                'unit' => 'km',
                'start_date' => '2026-01-01',
                'target_date' => '2026-03-15',
                'priority' => 'high',
                'status' => 'in_progress',
                'milestones' => [
                    ['title' => 'Chạy được 10km liên tục', 'due_date' => '2026-01-15'],
                    ['title' => 'Chạy được 21km (Half)', 'due_date' => '2026-02-01'],
                    ['title' => 'Chạy được 30km', 'due_date' => '2026-02-15'],
                    ['title' => 'Race day - Finish!', 'due_date' => '2026-03-15'],
                ],
            ],

            // 3. Body Transformation
            [
                'category_slug' => 'health-fitness',
                'title' => 'Body Transformation',
                'description' => 'Pull up & Push up dễ dàng. Gym 3 buổi/tuần. Giảm body fat xuống 20%',
                'target_value' => 20,
                'unit' => '% body fat',
                'start_date' => '2026-01-01',
                'target_date' => '2026-12-31',
                'priority' => 'high',
                'status' => 'in_progress',
                'milestones' => [
                    ['title' => '10 Push-ups liên tục', 'due_date' => '2026-02-28'],
                    ['title' => '5 Pull-ups liên tục', 'due_date' => '2026-04-30'],
                    ['title' => 'Body fat 25%', 'due_date' => '2026-06-30'],
                    ['title' => '20 Push-ups, 10 Pull-ups', 'due_date' => '2026-09-30'],
                    ['title' => 'Body fat 20%', 'due_date' => '2026-12-31'],
                ],
            ],

            // 4. FE資格
            [
                'category_slug' => 'personal-growth',
                'title' => 'FE資格 (基本情報技術者)',
                'description' => 'Đạt chứng chỉ Fundamental IT Engineer Examination',
                'target_date' => '2026-10-31',
                'priority' => 'high',
                'status' => 'not_started',
                'milestones' => [
                    ['title' => 'Mua giáo trình, lên kế hoạch học', 'due_date' => '2026-03-31'],
                    ['title' => 'Hoàn thành phần lý thuyết', 'due_date' => '2026-06-30'],
                    ['title' => 'Làm đề thi thử', 'due_date' => '2026-09-30'],
                    ['title' => 'Thi và đậu!', 'due_date' => '2026-10-31'],
                ],
            ],

            // 5. Scrum Master資格
            [
                'category_slug' => 'personal-growth',
                'title' => 'Scrum Master / Agile 資格',
                'description' => 'Đạt chứng chỉ Professional Scrum Master (PSM I) hoặc tương đương',
                'target_date' => '2026-12-31',
                'priority' => 'medium',
                'status' => 'not_started',
                'milestones' => [
                    ['title' => 'Nghiên cứu các chứng chỉ Agile', 'due_date' => '2026-04-30'],
                    ['title' => 'Học Scrum Guide', 'due_date' => '2026-07-31'],
                    ['title' => 'Làm mock exams', 'due_date' => '2026-10-31'],
                    ['title' => 'Thi và đậu!', 'due_date' => '2026-12-31'],
                ],
            ],

            // 6. Project CSS 100
            [
                'category_slug' => 'creativity-hobbies',
                'title' => 'Project CSS 100',
                'description' => 'Hoàn thành 100 CSS mini projects để nâng cao kỹ năng',
                'target_value' => 100,
                'current_value' => 0,
                'unit' => 'projects',
                'start_date' => '2026-01-01',
                'target_date' => '2026-12-31',
                'priority' => 'medium',
                'status' => 'not_started',
                'milestones' => [
                    ['title' => '25 projects (Q1)', 'target_value' => 25, 'due_date' => '2026-03-31'],
                    ['title' => '50 projects (Q2)', 'target_value' => 50, 'due_date' => '2026-06-30'],
                    ['title' => '75 projects (Q3)', 'target_value' => 75, 'due_date' => '2026-09-30'],
                    ['title' => '100 projects (Q4)', 'target_value' => 100, 'due_date' => '2026-12-31'],
                ],
            ],

            // 7. Content Creator Journey
            [
                'category_slug' => 'creativity-hobbies',
                'title' => 'Content Creator Journey',
                'description' => 'Đăng ít nhất 1 blog/tuần (52 blogs/năm). Xây dựng YouTube channel.',
                'target_value' => 52,
                'current_value' => 0,
                'unit' => 'blogs',
                'start_date' => '2026-01-01',
                'target_date' => '2026-12-31',
                'priority' => 'medium',
                'status' => 'not_started',
                'milestones' => [
                    ['title' => 'Setup blog platform', 'due_date' => '2026-01-15'],
                    ['title' => 'Tạo YouTube channel', 'due_date' => '2026-01-31'],
                    ['title' => '13 blogs (Q1)', 'target_value' => 13, 'due_date' => '2026-03-31'],
                    ['title' => 'Upload video YouTube đầu tiên', 'due_date' => '2026-06-30'],
                    ['title' => '52 blogs hoàn thành', 'target_value' => 52, 'due_date' => '2026-12-31'],
                ],
            ],

            // 8. Hong Kong Travel
            [
                'category_slug' => 'travel-experiences',
                'title' => 'Hong Kong Travel with Thu',
                'description' => 'Du lịch Hong Kong cùng Thu vào tháng 12/2026',
                'target_date' => '2026-12-15',
                'priority' => 'medium',
                'status' => 'not_started',
                'milestones' => [
                    ['title' => 'Lên kế hoạch & budget', 'due_date' => '2026-09-30'],
                    ['title' => 'Đặt vé máy bay', 'due_date' => '2026-10-15'],
                    ['title' => 'Đặt khách sạn', 'due_date' => '2026-10-31'],
                    ['title' => 'Lên lịch trình chi tiết', 'due_date' => '2026-11-30'],
                    ['title' => 'Go to Hong Kong! 🇭🇰', 'due_date' => '2026-12-15'],
                ],
            ],
        ];

        foreach ($goals as $goalData) {
            $category = Category::where('slug', $goalData['category_slug'])->first();

            if (!$category) {
                continue;
            }

            $milestones = $goalData['milestones'] ?? [];
            unset($goalData['category_slug'], $goalData['milestones']);

            $goal = Goal::create([
                ...$goalData,
                'user_id' => $user->id,
                'category_id' => $category->id,
            ]);

            foreach ($milestones as $index => $milestone) {
                $goal->milestones()->create([
                    ...$milestone,
                    'sort_order' => $index + 1,
                ]);
            }
        }

        $this->command->info('Sample goals created successfully!');
    }
}

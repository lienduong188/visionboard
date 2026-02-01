<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Career & Finance',
                'name_ja' => 'キャリア・財務',
                'slug' => 'career-finance',
                'description' => 'Career growth, income, investments, debt management',
                'icon' => '💰',
                'color' => '#10B981',
                'sort_order' => 1,
                'is_default' => true,
            ],
            [
                'name' => 'Health & Fitness',
                'name_ja' => '健康・フィットネス',
                'slug' => 'health-fitness',
                'description' => 'Physical health, exercise, nutrition, wellness',
                'icon' => '🏃',
                'color' => '#EF4444',
                'sort_order' => 2,
                'is_default' => true,
            ],
            [
                'name' => 'Relationships',
                'name_ja' => '人間関係',
                'slug' => 'relationships',
                'description' => 'Family, friends, romantic relationships, networking',
                'icon' => '❤️',
                'color' => '#EC4899',
                'sort_order' => 3,
                'is_default' => true,
            ],
            [
                'name' => 'Personal Growth',
                'name_ja' => '自己成長',
                'slug' => 'personal-growth',
                'description' => 'Learning, skills, certifications, self-improvement',
                'icon' => '📚',
                'color' => '#8B5CF6',
                'sort_order' => 4,
                'is_default' => true,
            ],
            [
                'name' => 'Travel & Experiences',
                'name_ja' => '旅行・体験',
                'slug' => 'travel-experiences',
                'description' => 'Travel goals, adventures, new experiences',
                'icon' => '✈️',
                'color' => '#F59E0B',
                'sort_order' => 5,
                'is_default' => true,
            ],
            [
                'name' => 'Creativity & Hobbies',
                'name_ja' => '創造性・趣味',
                'slug' => 'creativity-hobbies',
                'description' => 'Creative projects, hobbies, side projects',
                'icon' => '🎨',
                'color' => '#06B6D4',
                'sort_order' => 6,
                'is_default' => true,
            ],
            [
                'name' => 'Mindfulness & Spirituality',
                'name_ja' => 'マインドフルネス',
                'slug' => 'mindfulness-spirituality',
                'description' => 'Meditation, gratitude, mental health, spiritual growth',
                'icon' => '🧘',
                'color' => '#6366F1',
                'sort_order' => 7,
                'is_default' => true,
            ],
            [
                'name' => 'Giving Back',
                'name_ja' => '社会貢献',
                'slug' => 'giving-back',
                'description' => 'Charity, volunteering, community contribution',
                'icon' => '🤝',
                'color' => '#84CC16',
                'sort_order' => 8,
                'is_default' => true,
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pre-flight migration: ghi lại các migration đã được áp dụng
 * nhưng bị mất record trong bảng migrations (vd: sau khi bảng migrations bị xóa).
 *
 * Chạy trước tất cả các migration khác nhờ prefix "0000".
 * Logic: nếu table/column đã tồn tại MÀ record migration chưa có → insert record.
 * Kết quả: các migration sau đó sẽ thấy đã được ghi lại và bỏ qua (skip).
 *
 * Fresh install: tất cả check đều false → không insert gì → mọi migration chạy bình thường.
 * Corrupted state: table tồn tại nhưng record thiếu → insert record → migration tiếp theo skip.
 * Healthy state: record đã có → không insert gì (isset check ở đầu).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Danh sách migration → hàm kiểm tra xem đã áp dụng chưa
        $migrations = [
            '0001_01_01_000000_create_users_table'                      => fn() => Schema::hasTable('users'),
            '0001_01_01_000001_create_cache_table'                      => fn() => Schema::hasTable('cache'),
            '0001_01_01_000002_create_jobs_table'                       => fn() => Schema::hasTable('jobs'),
            '2026_01_01_000001_create_categories_table'                 => fn() => Schema::hasTable('categories'),
            '2026_01_01_000002_create_goals_table'                      => fn() => Schema::hasTable('goals'),
            '2026_01_01_000003_create_milestones_table'                 => fn() => Schema::hasTable('milestones'),
            '2026_01_01_000004_create_goal_images_table'                => fn() => Schema::hasTable('goal_images'),
            '2026_01_01_000005_create_reminders_table'                  => fn() => Schema::hasTable('reminders'),
            '2026_01_01_000006_create_progress_logs_table'              => fn() => Schema::hasTable('progress_logs'),
            '2026_01_01_000007_add_fields_to_users_table'               => fn() => Schema::hasColumn('users', 'avatar'),
            '2026_02_02_000001_add_sort_order_to_goals_table'           => fn() => Schema::hasColumn('goals', 'sort_order'),
            '2026_02_02_100000_add_orbit_scale_to_goals_table'          => fn() => Schema::hasColumn('goals', 'orbit_scale'),
            '2026_02_02_150000_add_is_core_goal_to_goals_table'         => fn() => Schema::hasColumn('goals', 'is_core_goal'),
            '2026_02_02_160000_add_slogan_to_goals_table'               => fn() => Schema::hasColumn('goals', 'slogan'),
            '2026_02_02_200000_create_review_settings_table'            => fn() => Schema::hasTable('review_settings'),
            '2026_02_02_300000_create_theme_words_table'                => fn() => Schema::hasTable('theme_words'),
            '2026_02_02_300001_add_theme_words_effect_to_users'         => fn() => Schema::hasColumn('users', 'theme_words_effect'),
            '2026_02_03_000001_add_memo_image_soft_to_milestones'       => fn() => Schema::hasColumn('milestones', 'memo'),
            '2026_02_03_000001_add_start_value_to_goals_table'          => fn() => Schema::hasColumn('goals', 'start_value'),
            // change column types - dùng goals table tồn tại làm indicator
            '2026_02_03_000002_change_goal_values_to_decimal'           => fn() => Schema::hasTable('goals'),
            '2026_02_03_000002_create_milestone_todos_table'            => fn() => Schema::hasTable('milestone_todos'),
            '2026_02_03_000003_create_goal_checklists_table'            => fn() => Schema::hasTable('goal_checklists'),
            '2026_02_03_000004_update_reminders_type_and_frequency'     => fn() => Schema::hasColumn('reminders', 'weekly_days'),
            '2026_02_03_100000_add_progress_mode_to_goals_table'        => fn() => Schema::hasColumn('goals', 'progress_mode'),
            '2026_02_04_000001_add_date_range_to_reminders_table'       => fn() => Schema::hasColumn('reminders', 'start_date'),
            '2026_02_05_000001_add_specific_dates_to_reminders_table'   => fn() => Schema::hasColumn('reminders', 'specific_dates'),
            '2026_02_05_000002_create_goal_references_table'            => fn() => Schema::hasTable('goal_references'),
            '2026_02_07_000001_add_username_to_users_table'             => fn() => Schema::hasColumn('users', 'username'),
            '2026_02_18_000001_create_daily_outputs_table'              => fn() => Schema::hasTable('daily_outputs'),
            '2026_02_18_000002_create_output_rest_days_table'           => fn() => Schema::hasTable('output_rest_days'),
            '2026_02_19_000001_add_image_to_daily_outputs_table'        => fn() => Schema::hasColumn('daily_outputs', 'image_path'),
            // data migration - dùng daily_outputs table tồn tại làm indicator
            '2026_02_23_094838_migrate_daily_output_categories'         => fn() => Schema::hasTable('daily_outputs'),
            '2026_02_24_add_images_to_daily_outputs_table'              => fn() => Schema::hasColumn('daily_outputs', 'images'),
            '2026_02_27_000001_add_cover_image_position_to_goals_table' => fn() => Schema::hasColumn('goals', 'cover_image_position'),
        ];

        // Lấy danh sách migration records đã có
        $existing = DB::table('migrations')->pluck('migration')->flip()->all();

        $toInsert = [];
        foreach ($migrations as $name => $check) {
            // Đã có record → bỏ qua
            if (isset($existing[$name])) {
                continue;
            }
            // Nếu table/column tồn tại → migration đã được áp dụng → ghi lại với batch 0
            if ($check()) {
                $toInsert[] = ['migration' => $name, 'batch' => 0];
            }
        }

        if (!empty($toInsert)) {
            DB::table('migrations')->insert($toInsert);
        }
    }

    public function down(): void
    {
        // Xóa các record được insert bởi pre-flight này (batch 0)
        DB::table('migrations')->where('batch', 0)->delete();
    }
};

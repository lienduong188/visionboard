# v!t's 2026 VisionBoard - Development Notes
- Giao tiếp bằng tiếng Việt
- Khi commit code, KHÔNG thêm dòng `Co-Authored-By: Claude` vào commit message
- Khi làm xong task và t check ok rồi thì commit và push code lên github luôn
- Khi build lỗi thì hãy xóa commit trước đi, fix lỗi, và commit mới để github k bị bẩn
- fix xong thì tự giác build đi nhé
- nếu thêm tính năng mới nào đó thì tự động update file này và file readme.md
- **Số thập phân**: Tối đa 2 chữ số sau dấu phẩy, bỏ trailing zeros, có dấu phân cách hàng nghìn
  - VD: 150000.00 → "150,000", 2.50 → "2.5", 2.00 → "2", 2.55 → "2.55"
  - Frontend: dùng `formatNumber()`, `formatForInput()`, `parseFromInput()` từ `@/utils/formatNumber`
  - Backend: dùng `formatNumber()` từ `app/Helpers/helpers.php`
  - Input fields: dùng `type="text"` với `inputmode="decimal"` và `@blur` handler

## Project Overview
Personal vision board web application to track 2026 goals.

## Tech Stack
- **Backend:** Laravel 12
- **Frontend:** Vue 3 + Inertia.js + Tailwind CSS
- **Database:** MySQL
- **Auth:** Laravel Breeze

## Local Development
```bash
# Start Laragon (Apache + MySQL)
# Access: http://visionboard2026.test

# Build assets
npm run build

# Or dev mode with hot reload
npm run dev
```

## ⚠️ Quy tắc Deploy & Database (QUAN TRỌNG - đọc trước khi deploy)

### Database production là SQLite trên Sakura server
- File: `~/www/visionboard2026/database/database.sqlite`
- **KHÔNG BAO GIỜ** commit `database/restore.sql` vào git — dù chỉ "tạm thời"
  - File này được gitignore, nếu cần force-add thì phải xóa ngay sau khi dùng xong
  - Một khi đã commit vào git → mỗi lần deploy sẽ tự động wipe sạch data production
- **KHÔNG** thêm bước auto-run restore.sql vào `deploy.yml` — đã từng làm và gây mất data nhiều lần
- Deploy an toàn: script tự copy sqlite từ deployment cũ sang mới, data được preserve

### Khi cần restore data production
- Dùng workflow **"Restore Database (Manual)"** trên GitHub Actions (trigger thủ công)
- Workflow dùng backup mới nhất từ `storage/app/backups/` trên server (backup tự động hàng ngày 2AM)
- Server Sakura dùng **FreeBSD/tcsh** — script phải chạy qua `sh` (không dùng bash syntax)
- Không dùng `appleboy/ssh-action` cho script phức tạp — dùng raw SSH: `ssh ... "sh ~/script.sh"`

### Backup system
- Tự động backup hàng ngày lúc 2:00 AM (Asia/Tokyo), giữ 7 bản gần nhất
- Backup lưu tại `storage/app/backups/` trên server
- Có thể backup thủ công tại `/settings/backup`

## Production Deployment

### 🚀 GitHub Actions Auto-Deploy (Khuyên dùng)
Xem chi tiết trong [GITHUB_ACTIONS_SETUP.md](GITHUB_ACTIONS_SETUP.md)

**Setup một lần:**
1. Add GitHub Secrets (FTP, SSH credentials)
2. Upload code lần đầu lên server
3. Push workflow file lên GitHub

**Sử dụng:**
```bash
git push origin main
# → GitHub Actions tự động build & deploy! 🎉
```

### 📦 Manual Deploy
Xem chi tiết trong [DEPLOYMENT.md](DEPLOYMENT.md) và [UPLOAD_GUIDE.md](UPLOAD_GUIDE.md)

**Quick checklist:**
1. Upload code lên server tại `/home/username/www/visionboard2026/`
2. Tạo `.env` production (copy từ `.env.production.example`)
3. Set permissions: `chmod -R 775 storage bootstrap/cache`
4. Run migrations: `php artisan migrate --force`
5. Link storage: `php artisan storage:link`
6. Cache config: `php artisan config:cache && php artisan route:cache`
7. Setup cron cho reminders: `* * * * * cd /path && php artisan schedule:run`
8. Test: https://visionboard.duonglien.com/

**⚠️ Important:**
- `APP_URL=https://visionboard.duonglien.com`
- `SESSION_PATH=/`
- `ASSET_URL=https://visionboard.duonglien.com`

## Database
```bash
php artisan migrate
php artisan db:seed
```

## Key Files
- `app/Http/Controllers/GoalController.php` - Goals CRUD
- `app/Models/Goal.php` - Goal model with relationships
- `resources/js/Pages/Goals/Index.vue` - Main vision board (VisionBoard, Plan views)
- `resources/js/Layouts/AuthenticatedLayout.vue` - Layout với navigation (VisionBoard, Today, Plan, Output, Analytics)
- `resources/js/Components/GoalCard.vue` - Goal card component
- `resources/js/Components/UnifiedFloating.vue` - Unified floating animation cho cả Core Goals và Theme Words với collision detection
- `resources/js/Components/GoalEditModal.vue` - Tabbed modal để quản lý goal (5 tabs: Info, Milestones, Reminders, References, History)
- `resources/js/Components/GoalEditModal/InfoTab.vue` - Tab edit thông tin goal + checklist
- `resources/js/Components/GoalEditModal/MilestonesTab.vue` - Tab quản lý milestones với drag-drop
- `resources/js/Components/GoalEditModal/RemindersTab.vue` - Tab quản lý reminders
- `resources/js/Components/GoalEditModal/ReferencesTab.vue` - Tab quản lý tài liệu tham khảo (links, files, notes)
- `resources/js/Components/GoalEditModal/HistoryTab.vue` - Tab progress chart + logs
- `resources/js/utils/formatNumber.js` - Utility function format số (frontend)
- `app/Helpers/helpers.php` - Helper functions (formatNumber cho backend)

## Features Implemented
- [x] Authentication (Login/Register)
- [x] **VisionBoard View** - 3 Core Goals bay lơ lửng (floating) quanh title "Vision Board 2026" với physics animation (Default view)
- [x] **Plan View** - Todo list với milestones của Core Goals + danh sách các goals thường
- [x] **Plan View Filters** - Bộ lọc đa chiều cho Plan view:
  - **Thời gian**: Tất cả, 3 tháng tới, 6 tháng tới, Quá khứ
  - **Tiến độ**: Tất cả, Đang làm (In Progress), Chưa bắt đầu (Not Started), Hoàn thành (Completed)
  - **Ưu tiên**: Tất cả, Cao (High), Trung bình (Medium), Thấp (Low)
  - **Danh mục**: Filter theo category
  - **Active Filters Summary**: Hiển thị các filter đang áp dụng với nút xóa
- [x] **Core Goals System** - Đánh dấu tối đa 3 goals làm "trục trung tâm" (is_core_goal)
- [x] Navigation Links - VisionBoard, Today, Plan, Output, Analytics nằm trực tiếp trong navigation bar (không có Dashboard)
- [x] **Today View** - Xem tất cả việc cần làm hôm nay và sắp tới:
  - **Stats Cards**: Overdue, Due Today, This Week, Total
  - **3 Sections**: Overdue (quá hạn), Due Today (hôm nay), This Week (7 ngày tới)
  - **Items hiển thị**: Milestones (due_date), Sub-todos (end_date), Reminders (next_send_at)
  - **Filter tabs**: All | Milestones | Todos | Reminders
  - **Toggle completion**: Đánh dấu hoàn thành trực tiếp từ Today view
  - **Quick navigation**: Click goal name → đến trang goal details
- [x] Goal CRUD
- [x] **Goal Edit Modal với Tabs** - Click vào goal mở modal với 5 tabs:
  - **Info Tab**: Edit thông tin goal + checklist
  - **Milestones Tab**: Quản lý milestones, drag-drop, todos
  - **Reminders Tab**: Quản lý reminders với frequency settings
  - **References Tab**: Quản lý tài liệu tham khảo (links, files, text notes)
  - **History Tab**: Progress chart + logs CRUD
- [x] **Slogan (Câu dẫn đường)** - Mỗi goal có thể có một câu slogan truyền cảm hứng
- [x] **Thuyết minh mục tiêu** - Description theo công thức: Trạng thái + Hình ảnh + Hành động
- [x] **Category Tooltips** - Tooltips tiếng Việt cho các category buttons
- [x] **Decimal Values** - Target/Current value hỗ trợ số thập phân (hiển thị 2 chữ số, số tròn hiển thị nguyên)
- [x] **Default Dates** - Start Date: 2026/1/1, Target Date: 2026/12/31
- [x] **Decrease Goals** - Hỗ trợ mục tiêu giảm (VD: giảm mỡ từ 27% xuống 20%) với start_value
- [x] **Progress Mode** - Chọn cách tính tiến độ cho từng goal:
  - **Value**: Progress = Current Value / Target Value (cho mục tiêu đo lường được)
  - **Milestone**: Progress = Milestones hoàn thành / Tổng milestones (cho mục tiêu nhiều bước)
- [x] Progress tracking
- [x] Pin goals
- [x] Category filter
- [x] 8 default categories
- [x] Image upload for goals
- [x] Milestone management với toggle checkbox trực tiếp từ Plan view
- [x] Drag & Drop - Sắp xếp goals bằng kéo thả (trong Plan view)
- [x] Progress Chart - Biểu đồ tiến độ theo thời gian (Line Chart)
- [x] Toast Notifications - Thông báo kết quả thao tác (success/error)
- [x] Reminder System - Quản lý nhắc nhở cho từng goal (daily/weekly/monthly)
- [x] Email Reminders - Gửi email nhắc nhở tự động qua background job
- [x] Responsive VisionBoard - Orbit view hiển thị tốt trên mobile
- [x] Analytics Dashboard - Trang phân tích với nhiều biểu đồ
- [x] Category Comparison Charts - Bar Chart và Radar Chart so sánh tiến độ giữa các category
- [x] Completion Trend Chart - Biểu đồ xu hướng hoàn thành theo thời gian
- [x] Monthly Stats Chart - Biểu đồ goals hoàn thành theo tháng
- [x] Weekly/Monthly Review - Email báo cáo tổng kết định kỳ (cấu hình trong Settings)
- [x] **Export Goals** - Xuất danh sách goals:
  - **CSV**: File CSV với đầy đủ thông tin goals + milestones
  - **PDF**: File PDF đẹp với stats, core goals, và other goals
- [x] **Theme Words** - Từ ngữ chủ đề với 2 hiệu ứng:
  - **Orbit**: User tự tạo theme words, bay lơ lửng cùng Core Goals
  - **Waterfall**: 100 từ vựng tích cực từ hệ thống, rơi như thác nước (background)
- [x] **Floating Animation** - Core Goals và Theme Words bay lơ lửng với physics, có thể kéo thả, chồng chéo tự nhiên
- [x] **Goal Checklist** - Checklist đơn giản trong GoalEditModal (không ảnh hưởng progress)
- [x] **Milestone Enhancements:**
  - **Memo** - Ghi chú chi tiết riêng biệt với description, hỗ trợ **Markdown** (bold, italic, lists, line breaks)
  - **Image** - Upload ảnh cho mỗi milestone
  - **Soft Milestone** - Nhắc nhở nhẹ, không tính vào progress của goal
  - **Sub-todos** - Mỗi milestone có thể có nhiều todos với ngày bắt đầu/kết thúc
  - **Drag & Drop** - Kéo thả để sắp xếp thứ tự milestones
  - **Toggle Expand/Collapse** - Click vào title để mở/đóng chi tiết milestone
- [x] **Reminder Enhancements:**
  - **Loại nhắc nhở** - Progress (cập nhật tiến độ), Deadline (hạn chót), Custom (tùy chỉnh) với tooltips
  - **Tần suất:**
    - Hàng ngày
    - Hàng tuần (chọn các ngày trong tuần)
    - Hàng tháng (chọn ngày trong tháng)
    - **Ngày cụ thể** - Chọn nhiều ngày cụ thể trong năm (VD: 15/3, 20/5), tự động tắt khi hết ngày
  - **Active Period (Date Range)** - Chọn khoảng thời gian hoạt động cho reminder (từ ngày - đến ngày), tự động tắt khi hết hạn
- [x] **Progress History Management** - Quản lý lịch sử tiến độ trong trang Goal Details:
  - **Quick +1** - Nút tăng nhanh +1 cho mục tiêu đếm được (blogs, km, books...)
  - **Recalculate** - Tính lại progress dựa trên số logs (current_value = số logs)
  - **Add Log** - Thêm bản ghi tiến độ với ngày quá khứ (cumulative value)
  - **Edit Log** - Chỉnh sửa value, date, note của bản ghi
  - **Delete Log** - Xóa bản ghi tiến độ
  - **View All** - Xem tất cả logs hoặc 5 logs gần nhất
- [x] **Goal References** - Quản lý tài liệu tham khảo cho từng goal:
  - **Links** - Thêm URL tham khảo với title, copy nhanh
  - **Files** - Upload file đính kèm (max 10MB), download trực tiếp
  - **Notes** - Ghi chú text với khả năng xem/sửa/copy nhanh
  - Hiển thị theo nhóm: Links, Files, Notes
- [x] **Multi-theme System** - Hệ thống theme với 3 lựa chọn:
  - **Light** (☀️) - Theme sáng mặc định
  - **Dark** (🌙) - Theme tối
  - **Hope** (🌿) - Theme xanh Emerald/Mint, mang ý nghĩa hy vọng
  - Theme switcher dropdown trong navbar, lưu preference vào localStorage
- [x] **Daily Output Tracker** - Trang tracking output hàng ngày (`/tracking-output`):
  - **Tracking Period**: 17/2/2026 → 6/2/2027 (chu kỳ âm lịch ~355 ngày)
  - **Multiple outputs/day**: Mỗi ngày có thể ghi nhiều output
  - **Output Categories** (hardcoded): 💻 Coding, ✍️ Writing, 🎥 Video, 📚 Study, 🏃 Training, 🎨 Creative, 💼 Career, 🧘 Wellness, 🔧 Other
  - **Duration Presets**: 30', 60', 90', 120'
  - **Goal Linking**: Liên kết output với goal hiện có
  - **Rating**: Đánh giá 1-5 sao
  - **Status**: Planned → Done / Skipped
  - **Plan Ahead**: Lên plan cho ngày mai, đánh dấu done/skipped sau
  - **Streak System**: "Earn Your Rest" - 7 ngày liên tiếp = 1 rest day (max bank 3)
  - **Two Views**: List view (grouped by day) + Calendar heatmap (GitHub-style)
  - **Stats**: Streak, rest days, completion rate, avg time/day, total outputs, category distribution
  - **Category Filter**: Lọc outputs theo category

## Key Daily Output Tracker Components
- `app/Http/Controllers/DailyOutputController.php` - CRUD + streak + rest day
- `app/Models/DailyOutput.php` - Output model với categories constants
- `app/Models/OutputRestDay.php` - Rest day model
- `app/Services/StreakCalculator.php` - Tính streak, heatmap data
- `resources/js/Pages/TrackingOutput/Index.vue` - Trang chính
- `resources/js/Components/TrackingOutput/OutputStatsBar.vue` - Stats cards
- `resources/js/Components/TrackingOutput/DayOutputGroup.vue` - Card nhóm theo ngày
- `resources/js/Components/TrackingOutput/OutputItemCard.vue` - Item card
- `resources/js/Components/TrackingOutput/OutputFormModal.vue` - Modal add/edit
- `resources/js/Components/TrackingOutput/CalendarHeatmap.vue` - Calendar heatmap

## Key Theme Components
- `resources/js/Components/ThemeSwitcher.vue` - Dropdown chọn theme
- `resources/css/app.css` - CSS variables và styles cho theme Hope

## Key Chart Components
- `resources/js/Components/Charts/ProgressLineChart.vue` - Base chart component
- `resources/js/Components/Charts/GoalProgressChart.vue` - Chart cho từng goal
- `resources/js/Components/Charts/OverviewProgressChart.vue` - Chart tổng quan Dashboard
- `resources/js/Components/Charts/CategoryBarChart.vue` - Bar chart so sánh categories
- `resources/js/Components/Charts/CategoryRadarChart.vue` - Radar chart so sánh categories
- `resources/js/Components/Charts/CompletionTrendChart.vue` - Xu hướng hoàn thành
- `resources/js/Components/Charts/MonthlyStatsChart.vue` - Thống kê theo tháng

## Categories
1. 💰 Career & Finance
2. 🏃 Health & Fitness
3. ❤️ Relationships
4. 📚 Personal Growth
5. ✈️ Travel & Experiences
6. 🎨 Creativity & Hobbies
7. 🧘 Mindfulness & Spirituality
8. 🤝 Giving Back

## 2026 Goals Structure

### 3 Core Goals (Trục Trung Tâm) - hiển thị trong VisionBoard
1. 🏃‍♀️ **Full Marathon Tottori** (15/3)
2. 💼 **Thi đỗ FE + visa**
3. 🎨 **Sáng tạo đều đặn** (blog/vlog)

### Other Goals (Plan View)
- Body Transformation
- Content Creator Journey
- CSS 100 / PSM / đọc sách
- Trả nợ Credit Card

## Key Milestone & Checklist Components
- `app/Http/Controllers/MilestoneController.php` - Milestone CRUD + image upload + soft toggle
- `app/Http/Controllers/MilestoneTodoController.php` - Milestone sub-todos CRUD
- `app/Http/Controllers/GoalChecklistController.php` - Goal checklist CRUD
- `app/Models/Milestone.php` - Milestone model với memo, image_path, is_soft, todos relationship
- `app/Models/MilestoneTodo.php` - Sub-todo model cho milestones
- `app/Models/GoalChecklist.php` - Simple checklist model cho goals
- `resources/js/Components/GoalEditModal/MilestonesTab.vue` - UI quản lý milestones trong modal
- `resources/js/Components/GoalEditModal/InfoTab.vue` - UI checklist trong Info tab
- `resources/js/Pages/Goals/Show.vue` - Read-only view với link "Edit in Modal"

## Key Reminder Components
- `app/Http/Controllers/ReminderController.php` - Reminder CRUD
- `app/Jobs/SendGoalReminders.php` - Background job gửi reminders
- `app/Mail/GoalReminderMail.php` - Email template cho reminders
- `resources/views/emails/goal-reminder.blade.php` - Email view

## Key Progress Log Components
- `app/Http/Controllers/ProgressLogController.php` - Progress Log CRUD (add/edit/delete với ngày tùy chọn)
- `app/Models/ProgressLog.php` - ProgressLog model với logged_at, note
- `resources/js/Components/GoalEditModal/HistoryTab.vue` - UI quản lý progress history + chart trong modal

## Key Reference Components
- `app/Http/Controllers/GoalReferenceController.php` - Reference CRUD + file upload
- `app/Models/GoalReference.php` - GoalReference model với type (link/file/text), file_path, file_size
- `resources/js/Components/GoalEditModal/ReferencesTab.vue` - UI quản lý tài liệu tham khảo trong modal

## Key Theme Words Components
- `app/Http/Controllers/ThemeWordController.php` - Theme Words CRUD (cho Orbit effect)
- `app/Models/ThemeWord.php` - ThemeWord model
- `resources/js/Components/UnifiedFloating.vue` - Floating animation cho Core Goals + Theme Words (Orbit mode)
- `resources/js/Components/ThemeWordsWaterfall.vue` - Waterfall effect với 100 positive words từ system
- `resources/js/Components/ThemeWordsManager.vue` - Panel quản lý từ ngữ + toggle effect

## Key Analytics & Review Components
- `app/Http/Controllers/AnalyticsController.php` - Analytics dashboard controller
- `resources/js/Pages/Analytics/Index.vue` - Trang Analytics chính
- `app/Http/Controllers/ReviewSettingController.php` - Review settings CRUD
- `resources/js/Pages/Settings/Reviews.vue` - Trang cài đặt Weekly/Monthly review
- `app/Jobs/SendWeeklyReviews.php` - Job gửi weekly review email
- `app/Jobs/SendMonthlyReviews.php` - Job gửi monthly review email
- `app/Mail/WeeklyReviewMail.php` - Mailable cho weekly review
- `app/Mail/MonthlyReviewMail.php` - Mailable cho monthly review
- `resources/views/emails/weekly-review.blade.php` - Template email weekly
- `resources/views/emails/monthly-review.blade.php` - Template email monthly

## Key Today Components
- `app/Http/Controllers/TodayController.php` - Today view controller, query milestones/todos/reminders
- `resources/js/Pages/Today/Index.vue` - Trang Today chính với stats, filters, sections
- `resources/js/Components/TodayItemCard.vue` - Card hiển thị item (milestone/todo/reminder)
- Route: `/today`

## Key Export Components
- `app/Http/Controllers/GoalController.php` - Export methods (exportCsv, exportPdf)
- `resources/views/exports/goals-pdf.blade.php` - PDF template cho export
- Routes: `/goals/export/csv`, `/goals/export/pdf`

## TODO
- [x] Email notifications/reminders
- [x] Analytics nâng cao (biểu đồ xu hướng, so sánh categories)
- [x] Weekly/Monthly Review emails
- [x] Export Goals (CSV & PDF) - Xuất danh sách goals ra file CSV hoặc PDF
- [x] **Multi-theme System** - Hỗ trợ 3 themes: Light, Dark, Hope (Emerald/Mint)
- [x] **Daily Output Tracker** - Tracking output hàng ngày với streak system, calendar heatmap
- [ ] Import goals
- [ ] Push notifications
- [ ] Share goals publicly

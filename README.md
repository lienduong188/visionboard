# v!t's 2026 VisionBoard

Personal vision board web application to track 2026 goals.

## Tech Stack
- **Backend:** Laravel 12
- **Frontend:** Vue 3 + Inertia.js + Tailwind CSS
- **Database:** MySQL
- **Auth:** Laravel Breeze

## Features
- Authentication (Login/Register)
- **VisionBoard View (Default)** - 3 Core Goals float around "Vision Board 2026" title with physics-based animation
- **Today View** - See all tasks due today and upcoming (7 days):
  - Stats: Overdue, Due Today, This Week, Total
  - Sections grouped by timeframe with visual indicators
  - Filter by type: All, Milestones, Todos, Reminders
  - Toggle completion directly from Today view
- **Plan View** - Todo list showing Core Goals with expandable milestones + regular goals with drag & drop
- **Plan View Filters** - Multi-dimensional filters:
  - Time: All, Next 3 months, Next 6 months, Past
  - Status: All, In Progress, Not Started, Completed
  - Priority: All, High, Medium, Low
  - Category filter with active filters summary
- **Core Goals System** - Mark up to 3 goals as "Core Goals" (trục trung tâm) to display in VisionBoard
- **View Toggle** - Switch between VisionBoard and Plan views from the navigation bar
- **Goal Edit Popup** - Click any goal to open edit popup (Save/Delete buttons, ESC or X to close)
- **Slogan (Câu dẫn đường)** - Each goal can have an inspirational slogan
- **Goal Description Formula** - Describe goals with: Status + Image + Action format
- **Category Tooltips** - Vietnamese tooltips for category filter buttons
- Goal CRUD with categories
- Progress tracking (integer values)
- Pin goals to top
- Category filter
- Image upload for goals
- Milestone management with checkbox toggle directly from Plan view
- Drag & Drop - Reorder regular goals by dragging
- Progress Chart - Visual progress tracking over time
- **Toast Notifications** - Visual feedback for all actions (success/error)
- **Reminder System** - Set reminders for goals (daily/weekly/monthly/custom)
- **Email Reminders** - Automatic email notifications via background job
- **Responsive VisionBoard** - Mobile-friendly floating view with touch support
- **Analytics Dashboard** - Comprehensive analytics with multiple chart types
- **Category Comparison** - Bar Chart and Radar Chart comparing progress across categories
- **Completion Trend** - Track completion trends over 7/30/90 days
- **Monthly Stats** - Goals completed per month visualization
- **Weekly/Monthly Review** - Scheduled email reports with progress summaries
- **Default Dates** - Goals default to Start: 2026/1/1, Target: 2026/12/31
- **Theme Words** - Add inspirational keywords for 2026 with two display effects:
  - **Floating Effect** - Words float around with physics-based animation (bounce off walls, avoid center)
  - **Waterfall Effect** - Words fall from top like rain/waterfall with random speed and opacity
- **Collision System** - Goals and Theme Words automatically push each other away when getting close
- **Goal Checklist** - Simple checklist inside goal edit modal (does not affect progress)
- **Milestone Enhancements:**
  - **Memo** - Detailed notes separate from description, supports **Markdown** (bold, italic, lists, line breaks)
  - **Image Upload** - Attach images to milestones
  - **Soft Milestone** - Light reminders that don't count toward goal progress
  - **Sub-todos** - Each milestone can have multiple todos with start/end dates
  - **Drag & Drop** - Reorder milestones by dragging
  - **Toggle Expand/Collapse** - Click title to show/hide milestone details
- **Reminder Enhancements:**
  - **Reminder Types** - Progress, Deadline, Custom with visual tooltips
  - **Google Calendar-like Frequency:**
    - Daily
    - Weekly (select days of week)
    - Monthly (select day of month)
    - Custom
  - **Active Period** - Set date range for when reminder is active
- **Export Goals:**
  - **CSV Export** - Export all goals with milestones to CSV file
  - **PDF Export** - Beautiful PDF report with stats, core goals, and other goals
- **Daily Output Tracker** - Track daily outputs with streak system:
  - Tracking period: 17/2/2026 → 6/2/2027 (lunar calendar cycle, ~355 days)
  - Multiple outputs per day with categories, duration, rating, notes
  - Link outputs to existing goals
  - Plan ahead: schedule tomorrow's outputs, mark done/skipped later
  - **Streak System** - "Earn Your Rest": 7 consecutive days = 1 rest day (max bank 3)
  - **Two Views**: List view (grouped by day) + Calendar heatmap (GitHub-style)
  - Stats: streak, rest days, completion rate, avg time/day, category distribution

## Local Development
```bash
# Start Laragon (Apache + MySQL)
# Access: http://visionboard2026.test

# Install dependencies
composer install
npm install

# Build assets
npm run build

# Or dev mode with hot reload
npm run dev

# Database
php artisan migrate
php artisan db:seed
```

## Production Deployment

### 🚀 GitHub Actions Auto-Deploy (Recommended)
Automatically deploy to production on every push to `main` branch.

See [GITHUB_ACTIONS_SETUP.md](GITHUB_ACTIONS_SETUP.md) for setup instructions.

```bash
git push origin main
# → Auto-build, deploy, and cache! 🎉
```

### 📦 Manual Deployment
For manual upload via FTP/SSH, see:
- [DEPLOYMENT.md](DEPLOYMENT.md) - Detailed deployment guide
- [UPLOAD_GUIDE.md](UPLOAD_GUIDE.md) - FileZilla/SCP upload instructions

**Live URL:** https://visionboard.duonglien.com/

**Key Points:**
- Deployed as subdomain `visionboard.duonglien.com`
- Session path and cookies configured for subdomain
- .htaccess forwards all requests to `public/` folder

## Categories
1. Career & Finance
2. Health & Fitness
3. Relationships
4. Personal Growth
5. Travel & Experiences
6. Creativity & Hobbies
7. Mindfulness & Spirituality
8. Giving Back

## License
MIT

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
- **Plan View** - Todo list showing Core Goals with expandable milestones + regular goals with drag & drop
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

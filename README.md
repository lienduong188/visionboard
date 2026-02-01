# v!t's 2026 VisionBoard

Personal vision board web application to track 2026 goals.

## Tech Stack
- **Backend:** Laravel 12
- **Frontend:** Vue 3 + Inertia.js + Tailwind CSS
- **Database:** MySQL
- **Auth:** Laravel Breeze

## Features
- Authentication (Login/Register)
- Board View (Pinterest-like) & Dashboard View
- **Orbit View (Default)** - Goals orbit around "Vision Board 2026" title with auto-rotate animation
- **View Toggle in Navigation** - Switch between Orbit, Board, and Dashboard views from the navigation bar
- Goal CRUD with categories
- Progress tracking
- Pin goals to top
- Category filter
- Image upload for goals
- Milestone management
- Drag & Drop - Reorder goals by dragging
- Progress Chart - Visual progress tracking over time
- **Orbit Scale** - Adjust goal card size (1-5) to prioritize important goals

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

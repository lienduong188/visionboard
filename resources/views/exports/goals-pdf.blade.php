<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Vision Board 2026 - Goals Export</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 2px solid #6366f1;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 24px;
            color: #6366f1;
            margin-bottom: 5px;
        }
        .header .subtitle {
            color: #666;
            font-size: 12px;
        }
        .stats {
            width: 100%;
            margin-bottom: 25px;
            background: #f8fafc;
            padding: 15px;
        }
        .stats table {
            width: 100%;
        }
        .stats td {
            text-align: center;
            padding: 10px;
            width: 20%;
        }
        .stat-value {
            font-size: 20px;
            font-weight: bold;
            color: #6366f1;
        }
        .stat-value.green { color: #22c55e; }
        .stat-value.blue { color: #3b82f6; }
        .stat-value.gray { color: #6b7280; }
        .stat-label {
            font-size: 10px;
            color: #666;
            margin-top: 3px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 12px;
            padding-bottom: 5px;
            border-bottom: 1px solid #e5e7eb;
        }
        .goal {
            background: #fff;
            border: 1px solid #e5e7eb;
            padding: 12px;
            margin-bottom: 10px;
            page-break-inside: avoid;
        }
        .goal-header table {
            width: 100%;
            margin-bottom: 8px;
        }
        .goal-title-cell {
            width: 70%;
        }
        .goal-progress-cell {
            width: 30%;
            text-align: right;
            vertical-align: top;
        }
        .goal-title {
            font-size: 13px;
            font-weight: bold;
            color: #1f2937;
        }
        .goal-category {
            font-size: 10px;
            color: #6b7280;
            margin-top: 2px;
        }
        .goal-slogan {
            font-style: italic;
            color: #6366f1;
            font-size: 10px;
            margin-top: 3px;
        }
        .progress-badge {
            display: inline-block;
            padding: 4px 10px;
            font-weight: bold;
            font-size: 12px;
        }
        .progress-badge.completed {
            background: #dcfce7;
            color: #16a34a;
        }
        .progress-badge.in-progress {
            background: #dbeafe;
            color: #2563eb;
        }
        .progress-badge.not-started {
            background: #f3f4f6;
            color: #6b7280;
        }
        .goal-meta {
            font-size: 10px;
            color: #6b7280;
            margin-top: 5px;
        }
        .goal-description {
            font-size: 10px;
            color: #4b5563;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px dashed #e5e7eb;
        }
        .milestones {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px dashed #e5e7eb;
        }
        .milestones-title {
            font-size: 10px;
            font-weight: bold;
            color: #4b5563;
            margin-bottom: 5px;
        }
        .milestone {
            font-size: 10px;
            padding: 3px 0;
            padding-left: 15px;
        }
        .milestone.completed {
            color: #22c55e;
            text-decoration: line-through;
        }
        .milestone.pending {
            color: #6b7280;
        }
        .core-badge {
            display: inline-block;
            background: #fef3c7;
            color: #d97706;
            font-size: 9px;
            padding: 2px 6px;
            margin-left: 8px;
        }
        .priority-high { color: #dc2626; }
        .priority-medium { color: #f59e0b; }
        .priority-low { color: #22c55e; }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Vision Board 2026</h1>
        <div class="subtitle">{{ $user->name }} - Exported on {{ $exportDate }}</div>
    </div>

    <div class="stats">
        <table>
            <tr>
                <td>
                    <div class="stat-value">{{ $stats['total'] }}</div>
                    <div class="stat-label">Total Goals</div>
                </td>
                <td>
                    <div class="stat-value green">{{ $stats['completed'] }}</div>
                    <div class="stat-label">Completed</div>
                </td>
                <td>
                    <div class="stat-value blue">{{ $stats['in_progress'] }}</div>
                    <div class="stat-label">In Progress</div>
                </td>
                <td>
                    <div class="stat-value gray">{{ $stats['not_started'] }}</div>
                    <div class="stat-label">Not Started</div>
                </td>
                <td>
                    <div class="stat-value">{{ $stats['overall_progress'] }}%</div>
                    <div class="stat-label">Overall Progress</div>
                </td>
            </tr>
        </table>
    </div>

    @if($coreGoals->count() > 0)
    <div class="section">
        <div class="section-title">Core Goals - Truc Trung Tam</div>
        @foreach($coreGoals as $goal)
        <div class="goal">
            <div class="goal-header">
                <table>
                    <tr>
                        <td class="goal-title-cell">
                            <div class="goal-title">
                                {{ $goal->title }}
                                <span class="core-badge">CORE</span>
                            </div>
                            <div class="goal-category">{{ $goal->category?->name ?? 'No Category' }}</div>
                            @if($goal->slogan)
                            <div class="goal-slogan">"{{ $goal->slogan }}"</div>
                            @endif
                        </td>
                        <td class="goal-progress-cell">
                            <span class="progress-badge {{ $goal->status === 'completed' ? 'completed' : ($goal->status === 'in_progress' ? 'in-progress' : 'not-started') }}">
                                {{ $goal->progress }}%
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="goal-meta">
                <span class="priority-{{ $goal->priority }}">{{ ucfirst($goal->priority) }} Priority</span>
                @if($goal->target_value)
                | Target: {{ formatNumber($goal->current_value ?? 0) }}/{{ formatNumber($goal->target_value) }} {{ $goal->unit }}
                @endif
                @if($goal->target_date)
                | Due: {{ $goal->target_date->format('M d, Y') }}
                @endif
            </div>
            @if($goal->description)
            <div class="goal-description">{{ $goal->description }}</div>
            @endif
            @if($goal->milestones && $goal->milestones->count() > 0)
            <div class="milestones">
                <div class="milestones-title">Milestones ({{ $goal->milestones->where('is_completed', true)->count() }}/{{ $goal->milestones->count() }})</div>
                @foreach($goal->milestones as $milestone)
                <div class="milestone {{ $milestone->is_completed ? 'completed' : 'pending' }}">
                    {{ $milestone->is_completed ? '[x]' : '[ ]' }} {{ $milestone->title }}
                    @if($milestone->due_date)
                    ({{ \Carbon\Carbon::parse($milestone->due_date)->format('M d') }})
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    @if($regularGoals->count() > 0)
    <div class="section">
        <div class="section-title">Other Goals</div>
        @foreach($regularGoals as $goal)
        <div class="goal">
            <div class="goal-header">
                <table>
                    <tr>
                        <td class="goal-title-cell">
                            <div class="goal-title">{{ $goal->title }}</div>
                            <div class="goal-category">{{ $goal->category?->name ?? 'No Category' }}</div>
                            @if($goal->slogan)
                            <div class="goal-slogan">"{{ $goal->slogan }}"</div>
                            @endif
                        </td>
                        <td class="goal-progress-cell">
                            <span class="progress-badge {{ $goal->status === 'completed' ? 'completed' : ($goal->status === 'in_progress' ? 'in-progress' : 'not-started') }}">
                                {{ $goal->progress }}%
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="goal-meta">
                <span class="priority-{{ $goal->priority }}">{{ ucfirst($goal->priority) }} Priority</span>
                @if($goal->target_value)
                | Target: {{ formatNumber($goal->current_value ?? 0) }}/{{ formatNumber($goal->target_value) }} {{ $goal->unit }}
                @endif
                @if($goal->target_date)
                | Due: {{ $goal->target_date->format('M d, Y') }}
                @endif
            </div>
            @if($goal->description)
            <div class="goal-description">{{ $goal->description }}</div>
            @endif
            @if($goal->milestones && $goal->milestones->count() > 0)
            <div class="milestones">
                <div class="milestones-title">Milestones ({{ $goal->milestones->where('is_completed', true)->count() }}/{{ $goal->milestones->count() }})</div>
                @foreach($goal->milestones as $milestone)
                <div class="milestone {{ $milestone->is_completed ? 'completed' : 'pending' }}">
                    {{ $milestone->is_completed ? '[x]' : '[ ]' }} {{ $milestone->title }}
                    @if($milestone->due_date)
                    ({{ \Carbon\Carbon::parse($milestone->due_date)->format('M d') }})
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    <div class="footer">
        Vision Board 2026 - Generated by v!t's Vision Board App
    </div>
</body>
</html>

<?php

namespace Database\Seeders;

use App\Enums\DeadlineType;
use App\Enums\DependencyType;
use App\Enums\DurationSource;
use App\Enums\EventPriority;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\EventVisibility;
use App\Enums\MilestonePriority;
use App\Enums\MilestoneStatus;
use App\Enums\ProgressSource;
use App\Models\Event;
use App\Models\EventDependency;
use App\Models\EventReminder;
use App\Models\Milestone;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class PlannerSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'bipin.paneru.9@gmail.com')->firstOrFail();

        // ── Tags — every possible colour, used for filter testing ─────────────
        $tags = collect([
            ['name' => 'health',   'color' => '#22c55e'],
            ['name' => 'career',   'color' => '#3b82f6'],
            ['name' => 'finance',  'color' => '#f59e0b'],
            ['name' => 'learning', 'color' => '#a855f7'],
            ['name' => 'social',   'color' => '#ec4899'],
            ['name' => 'travel',   'color' => '#06b6d4'],
            ['name' => 'home',     'color' => '#f97316'],
            ['name' => 'urgent',   'color' => '#ef4444'],
            ['name' => 'research', 'color' => '#64748b'],
        ])->map(fn ($attrs) => Tag::factory()->create([...$attrs, 'user_id' => $user->id]));

        $t = $tags->keyBy('name');

        // ─────────────────────────────────────────────────────────────────────
        // MILESTONE 1 — Active, Critical, Soft deadline, Derived dates
        //   Tests: active state, critical badge, progress bar, event mix
        // ─────────────────────────────────────────────────────────────────────
        $msLaunch = Milestone::factory()->create([
            'user_id' => $user->id,
            'title' => 'Launch Freelance Business',
            'description' => 'Everything needed to go from zero to first paying client — portfolio, legal, outreach, contracts.',
            'status' => MilestoneStatus::Active,
            'priority' => MilestonePriority::Critical,
            'color' => '#3b82f6',
            'start_at' => now()->subWeeks(3),
            'end_at' => now()->addMonths(2),
            'duration_source' => DurationSource::Derived,
            'deadline_type' => DeadlineType::Soft,
            'progress_source' => ProgressSource::Derived,
            'visibility' => 'private',
        ]);
        $msLaunch->tags()->attach([$t['career']->id, $t['finance']->id]);

        // Events: all statuses, all types, snoozed, with/without description
        $eLaunch1 = Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => $msLaunch->id,
            'title' => 'Define service offerings & pricing tiers',
            'description' => 'Decide on hourly vs project-based, set starter/pro/enterprise tiers.',
            'type' => EventType::Task,
            'status' => EventStatus::Completed,
            'priority' => EventPriority::High,
            'start_at' => now()->subWeeks(3),
            'end_at' => now()->subWeeks(3)->addHours(3),
            'visibility' => EventVisibility::Private,
        ]);

        $eLaunch2 = Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => $msLaunch->id,
            'title' => 'Build portfolio website',
            'description' => 'Showcase 3 case studies, services page, contact form with Cal.com embed.',
            'type' => EventType::Task,
            'status' => EventStatus::InProgress,
            'priority' => EventPriority::Critical,
            'start_at' => now()->subDays(4),
            'end_at' => now()->addDays(5),
            'color' => '#6366f1',
            'visibility' => EventVisibility::Private,
        ]);

        $eLaunch3 = Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => $msLaunch->id,
            'title' => 'Register business & open bank account',
            'type' => EventType::Task,
            'status' => EventStatus::Upcoming,
            'priority' => EventPriority::High,
            'start_at' => now()->addDays(3),
            'end_at' => now()->addDays(3)->addHours(2),
            'location' => 'City Business Registration Office',
            'visibility' => EventVisibility::Private,
        ]);

        $eLaunch4 = Event::factory()->snoozed()->create([
            'user_id' => $user->id,
            'milestone_id' => $msLaunch->id,
            'title' => 'Draft client contract template',
            'description' => 'Scope of work, IP ownership, payment terms, kill-fee clause.',
            'type' => EventType::Task,
            'status' => EventStatus::Upcoming,
            'priority' => EventPriority::Medium,
            'start_at' => now()->addDays(6),
            'end_at' => now()->addDays(6)->addHours(2),
            'snoozed_until' => now()->addHours(4),
            'snooze_count' => 2,
            'visibility' => EventVisibility::Private,
        ]);

        $eLaunch5 = Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => $msLaunch->id,
            'title' => 'Cold outreach — 30 potential clients',
            'type' => EventType::Task,
            'status' => EventStatus::Upcoming,
            'priority' => EventPriority::Medium,
            'start_at' => now()->addWeeks(1),
            'end_at' => now()->addWeeks(1)->addHours(1),
            'visibility' => EventVisibility::Private,
        ]);

        $eLaunch6 = Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => $msLaunch->id,
            'title' => 'First client discovery call',
            'type' => EventType::Event,
            'status' => EventStatus::Upcoming,
            'priority' => EventPriority::High,
            'start_at' => now()->addWeeks(2),
            'end_at' => now()->addWeeks(2)->addHours(1),
            'location' => 'Google Meet',
            'visibility' => EventVisibility::Shared,
        ]);

        $eLaunch7 = Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => $msLaunch->id,
            'title' => 'Kick-off strategy call (cancelled)',
            'type' => EventType::Event,
            'status' => EventStatus::Cancelled,
            'priority' => EventPriority::Medium,
            'start_at' => now()->subDays(1),
            'end_at' => now()->subDays(1)->addHours(1),
            'visibility' => EventVisibility::Private,
        ]);

        // All-day milestone marker
        $eLaunch8 = Event::factory()->allDay()->create([
            'user_id' => $user->id,
            'milestone_id' => $msLaunch->id,
            'title' => 'Official business launch day',
            'type' => EventType::MilestoneMarker,
            'status' => EventStatus::Upcoming,
            'priority' => EventPriority::Critical,
            'start_at' => now()->addMonths(2)->startOfDay(),
            'is_all_day' => true,
            'visibility' => EventVisibility::Shared,
        ]);

        $eLaunch1->tags()->attach([$t['career']->id, $t['finance']->id]);
        $eLaunch2->tags()->attach([$t['career']->id]);
        $eLaunch3->tags()->attach([$t['finance']->id]);
        $eLaunch4->tags()->attach([$t['career']->id, $t['urgent']->id]);
        $eLaunch5->tags()->attach([$t['career']->id, $t['social']->id]);
        $eLaunch6->tags()->attach([$t['career']->id, $t['social']->id]);

        // Reminders on the discovery call
        EventReminder::create(['event_id' => $eLaunch6->id, 'offset_minutes' => 60,  'channels' => ['email']]);
        EventReminder::create(['event_id' => $eLaunch6->id, 'offset_minutes' => 15,  'channels' => ['push']]);

        // Dependency: outreach must complete before discovery call
        EventDependency::create([
            'event_id' => $eLaunch6->id,
            'depends_on_event_id' => $eLaunch5->id,
            'type' => DependencyType::Blocking,
        ]);

        // ─────────────────────────────────────────────────────────────────────
        // MILESTONE 2 — Active, High, Hard deadline, Manual dates, manual progress
        //   Tests: hard-deadline badge, locked end-date, progress override
        // ─────────────────────────────────────────────────────────────────────
        $msCert = Milestone::factory()->hardDeadline()->create([
            'user_id' => $user->id,
            'title' => 'AWS Solutions Architect Certification',
            'description' => 'Pass SAA-C03 before the Pearson VUE voucher expires.',
            'status' => MilestoneStatus::Active,
            'priority' => MilestonePriority::High,
            'color' => '#f59e0b',
            'start_at' => now()->subMonths(1),
            'end_at' => now()->addMonths(1),
            'duration_source' => DurationSource::Manual,
            'deadline_type' => DeadlineType::Hard,
            'progress_source' => ProgressSource::Manual,
            'progress_override' => 42,
            'visibility' => 'private',
        ]);
        $msCert->tags()->attach([$t['learning']->id, $t['career']->id]);

        $eCert1 = Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => $msCert->id,
            'title' => 'Complete Cloud Practitioner prerequisites',
            'type' => EventType::Task,
            'status' => EventStatus::Completed,
            'priority' => EventPriority::Medium,
            'start_at' => now()->subMonths(1),
            'end_at' => now()->subWeeks(3),
            'visibility' => EventVisibility::Private,
        ]);

        $eCert2 = Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => $msCert->id,
            'title' => 'Stephane Maarek SAA-C03 course (all sections)',
            'description' => 'Target: 2 sections per day. Focus on VPC, IAM, S3.',
            'type' => EventType::Task,
            'status' => EventStatus::InProgress,
            'priority' => EventPriority::High,
            'start_at' => now()->subWeeks(2),
            'end_at' => now()->addWeeks(2),
            'visibility' => EventVisibility::Private,
        ]);

        $eCert3 = Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => $msCert->id,
            'title' => 'Take 5 Tutorials Dojo practice exams',
            'type' => EventType::Task,
            'status' => EventStatus::Upcoming,
            'priority' => EventPriority::High,
            'start_at' => now()->addWeeks(2),
            'end_at' => now()->addWeeks(3),
            'visibility' => EventVisibility::Private,
        ]);

        // Skipped event
        $eCert4 = Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => $msCert->id,
            'title' => 'AWS re:Invent study group (skipped)',
            'type' => EventType::Event,
            'status' => EventStatus::Skipped,
            'priority' => EventPriority::Low,
            'start_at' => now()->subWeeks(1),
            'end_at' => now()->subWeeks(1)->addHours(2),
            'visibility' => EventVisibility::Private,
        ]);

        $eCert5 = Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => $msCert->id,
            'title' => 'Exam — AWS SAA-C03',
            'type' => EventType::Event,
            'status' => EventStatus::Upcoming,
            'priority' => EventPriority::Critical,
            'start_at' => now()->addMonths(1)->subDays(2),
            'end_at' => now()->addMonths(1)->subDays(2)->addHours(3),
            'location' => 'Pearson VUE Testing Center, Kathmandu',
            'visibility' => EventVisibility::Private,
        ]);

        $eCert1->tags()->attach([$t['learning']->id]);
        $eCert2->tags()->attach([$t['learning']->id, $t['research']->id]);
        $eCert3->tags()->attach([$t['learning']->id]);
        $eCert5->tags()->attach([$t['career']->id, $t['urgent']->id]);

        EventReminder::create(['event_id' => $eCert5->id, 'offset_minutes' => 1440, 'channels' => ['email']]);
        EventReminder::create(['event_id' => $eCert5->id, 'offset_minutes' => 60,   'channels' => ['push']]);

        // Practice exams blocked by course completion
        EventDependency::create([
            'event_id' => $eCert3->id,
            'depends_on_event_id' => $eCert2->id,
            'type' => DependencyType::Blocking,
        ]);
        // Exam informational dependency on practice exams
        EventDependency::create([
            'event_id' => $eCert5->id,
            'depends_on_event_id' => $eCert3->id,
            'type' => DependencyType::Informational,
        ]);

        // ─────────────────────────────────────────────────────────────────────
        // MILESTONE 3 — Active, Medium, overdue (end_at in the past)
        //   Tests: overdue state badge, past events still showing
        // ─────────────────────────────────────────────────────────────────────
        $msFitness = Milestone::factory()->overdue()->create([
            'user_id' => $user->id,
            'title' => 'Run a 5K Under 25 Minutes',
            'description' => 'Couch-to-5K training block, culminating in a race.',
            'status' => MilestoneStatus::Active,
            'priority' => MilestonePriority::Medium,
            'color' => '#ec4899',
            'start_at' => now()->subMonths(3),
            'end_at' => now()->subWeeks(1),
            'visibility' => 'private',
        ]);
        $msFitness->tags()->attach([$t['health']->id]);

        Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => $msFitness->id,
            'title' => 'Weeks 1–4: Base-building runs (3×/week)',
            'type' => EventType::Task,
            'status' => EventStatus::Completed,
            'priority' => EventPriority::Medium,
            'start_at' => now()->subMonths(3),
            'end_at' => now()->subMonths(2),
        ])->tags()->attach([$t['health']->id]);

        Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => $msFitness->id,
            'title' => 'Weeks 5–8: Tempo intervals',
            'type' => EventType::Task,
            'status' => EventStatus::Completed,
            'priority' => EventPriority::Medium,
            'start_at' => now()->subMonths(2),
            'end_at' => now()->subWeeks(4),
        ])->tags()->attach([$t['health']->id]);

        Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => $msFitness->id,
            'title' => 'Local parkrun 5K race',
            'type' => EventType::Event,
            'status' => EventStatus::Upcoming,
            'priority' => EventPriority::High,
            'start_at' => now()->addWeeks(1),
            'end_at' => now()->addWeeks(1)->addHours(2),
            'location' => 'City Park Riverside Trail',
        ])->tags()->attach([$t['health']->id, $t['social']->id]);

        // ─────────────────────────────────────────────────────────────────────
        // MILESTONE 4 — Completed, all events completed
        //   Tests: completed milestone tab, completed event list
        // ─────────────────────────────────────────────────────────────────────
        $msMove = Milestone::factory()->completed()->create([
            'user_id' => $user->id,
            'title' => 'Move to New Apartment',
            'description' => 'Full relocation from old flat to Lazimpat.',
            'status' => MilestoneStatus::Completed,
            'priority' => MilestonePriority::High,
            'color' => '#22c55e',
            'start_at' => now()->subMonths(3),
            'end_at' => now()->subWeeks(3),
            'visibility' => 'private',
        ]);

        foreach ([
            ['Pack all boxes and label them by room', EventPriority::High,     now()->subMonths(3),          now()->subMonths(3)->addDays(3)],
            ['Hire removal van',                      EventPriority::High,     now()->subMonths(3)->addDays(1), now()->subMonths(3)->addDays(2)],
            ['Deep-clean old apartment',              EventPriority::Medium,   now()->subMonths(2)->subWeeks(2), now()->subMonths(2)->subWeeks(2)->addHours(5)],
            ['Moving day',                            EventPriority::Critical, now()->subMonths(2)->subWeeks(1), now()->subMonths(2)->subWeeks(1)->addHours(8)],
            ['Unpack & set up furniture',             EventPriority::Medium,   now()->subMonths(2),          now()->subMonths(2)->addDays(3)],
            ['Transfer utilities & broadband',        EventPriority::Medium,   now()->subWeeks(4),           now()->subWeeks(4)->addDays(1)],
        ] as [$title, $priority, $start, $end]) {
            Event::factory()->create([
                'user_id' => $user->id,
                'milestone_id' => $msMove->id,
                'title' => $title,
                'type' => EventType::Task,
                'status' => EventStatus::Completed,
                'priority' => $priority,
                'start_at' => $start,
                'end_at' => $end,
            ])->tags()->attach([$t['home']->id]);
        }

        // ─────────────────────────────────────────────────────────────────────
        // MILESTONE 5 — Paused, future start
        //   Tests: paused badge, future milestone, empty-ish event list
        // ─────────────────────────────────────────────────────────────────────
        $msPodcast = Milestone::factory()->create([
            'user_id' => $user->id,
            'title' => 'Start a Tech Podcast',
            'description' => 'Solo show covering Laravel, indie-hacking, and productivity.',
            'status' => MilestoneStatus::Paused,
            'priority' => MilestonePriority::Low,
            'color' => '#a855f7',
            'start_at' => now()->addMonths(3),
            'end_at' => now()->addMonths(7),
            'visibility' => 'private',
        ]);
        $msPodcast->tags()->attach([$t['career']->id, $t['social']->id]);

        Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => $msPodcast->id,
            'title' => 'Research podcast hosting platforms',
            'type' => EventType::Task,
            'status' => EventStatus::Draft,
            'priority' => EventPriority::Low,
            'start_at' => now()->addMonths(3),
            'end_at' => now()->addMonths(3)->addDays(3),
        ])->tags()->attach([$t['research']->id]);

        Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => $msPodcast->id,
            'title' => 'Record pilot episode',
            'type' => EventType::Task,
            'status' => EventStatus::Draft,
            'priority' => EventPriority::Medium,
            'start_at' => now()->addMonths(4),
            'end_at' => now()->addMonths(4)->addDays(7),
        ])->tags()->attach([$t['learning']->id]);

        // ─────────────────────────────────────────────────────────────────────
        // MILESTONE 6 — Cancelled
        //   Tests: cancelled state in tab list
        // ─────────────────────────────────────────────────────────────────────
        $msCancelled = Milestone::factory()->create([
            'user_id' => $user->id,
            'title' => 'Migrate Blog to Ghost CMS',
            'description' => 'Abandoned — decided to keep WordPress.',
            'status' => MilestoneStatus::Cancelled,
            'priority' => MilestonePriority::Low,
            'color' => '#94a3b8',
            'start_at' => now()->subMonths(2),
            'end_at' => now()->subMonths(1),
            'visibility' => 'private',
        ]);

        Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => $msCancelled->id,
            'title' => 'Export WordPress posts to markdown',
            'type' => EventType::Task,
            'status' => EventStatus::Cancelled,
            'priority' => EventPriority::Low,
            'start_at' => now()->subMonths(2),
            'end_at' => now()->subMonths(2)->addDays(2),
        ]);

        // ─────────────────────────────────────────────────────────────────────
        // MILESTONE 7 — Active, manual progress override, shared visibility
        //   Tests: progress_override field, shared visibility badge
        // ─────────────────────────────────────────────────────────────────────
        $msEmergency = Milestone::factory()->create([
            'user_id' => $user->id,
            'title' => 'Build 6-Month Emergency Fund',
            'description' => 'Save £15,000 to cover 6 months of core expenses.',
            'status' => MilestoneStatus::Active,
            'priority' => MilestonePriority::High,
            'color' => '#10b981',
            'start_at' => now()->subMonths(6),
            'end_at' => now()->addMonths(6),
            'progress_source' => ProgressSource::Manual,
            'progress_override' => 65,
            'visibility' => 'private',
        ]);
        $msEmergency->tags()->attach([$t['finance']->id]);

        Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => $msEmergency->id,
            'title' => 'Set up automatic monthly transfer £1,000 → savings',
            'type' => EventType::Task,
            'status' => EventStatus::Completed,
            'priority' => EventPriority::High,
            'start_at' => now()->subMonths(6),
            'end_at' => now()->subMonths(6)->addDays(1),
        ])->tags()->attach([$t['finance']->id]);

        Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => $msEmergency->id,
            'title' => 'Review & cut subscriptions',
            'type' => EventType::Task,
            'status' => EventStatus::Completed,
            'priority' => EventPriority::Medium,
            'start_at' => now()->subMonths(5),
            'end_at' => now()->subMonths(5)->addHours(2),
        ])->tags()->attach([$t['finance']->id]);

        $eSave3 = Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => $msEmergency->id,
            'title' => 'Quarterly savings review',
            'type' => EventType::Event,
            'status' => EventStatus::Upcoming,
            'priority' => EventPriority::Medium,
            'start_at' => now()->addWeeks(2),
            'end_at' => now()->addWeeks(2)->addHours(1),
            'is_all_day' => false,
        ])->tags()->attach([$t['finance']->id]);

        // ─────────────────────────────────────────────────────────────────────
        // BACKLOG — No milestone, every status + type combo
        // ─────────────────────────────────────────────────────────────────────

        // Upcoming task — no tags (tests empty-tag display)
        Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => null,
            'title' => 'Read "The Lean Startup"',
            'type' => EventType::Task,
            'status' => EventStatus::Upcoming,
            'priority' => EventPriority::Low,
            'start_at' => now()->addWeeks(2),
            'end_at' => now()->addMonths(1),
            'visibility' => EventVisibility::Private,
        ]);

        // Draft task
        Event::factory()->draft()->create([
            'user_id' => $user->id,
            'milestone_id' => null,
            'title' => 'Plan 2-week trip to Japan',
            'description' => 'Tokyo → Kyoto → Osaka. Budget ¥500,000.',
            'type' => EventType::Task,
            'status' => EventStatus::Draft,
            'priority' => EventPriority::Medium,
            'start_at' => now()->addMonths(8),
            'end_at' => now()->addMonths(8)->addDays(14),
            'visibility' => EventVisibility::Private,
        ])->tags()->attach([$t['travel']->id]);

        // Snoozed — high snooze count
        Event::factory()->snoozed()->create([
            'user_id' => $user->id,
            'milestone_id' => null,
            'title' => 'Organise home office & cable management',
            'type' => EventType::Task,
            'status' => EventStatus::Upcoming,
            'priority' => EventPriority::Low,
            'start_at' => now()->addDays(3),
            'end_at' => now()->addDays(3)->addHours(4),
            'snoozed_until' => now()->addHours(6),
            'snooze_count' => 5,
            'visibility' => EventVisibility::Private,
        ])->tags()->attach([$t['home']->id]);

        // In-progress event (not a task) — tests type+status combo
        Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => null,
            'title' => 'Online Laravel 12 deep-dive workshop',
            'description' => 'Live stream on YouTube every Saturday 10–12.',
            'type' => EventType::Event,
            'status' => EventStatus::InProgress,
            'priority' => EventPriority::High,
            'start_at' => now()->subDays(2),
            'end_at' => now()->addWeeks(3),
            'visibility' => EventVisibility::Shared,
        ])->tags()->attach([$t['learning']->id, $t['career']->id]);

        // Cancelled backlog item
        Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => null,
            'title' => 'Sign up for local Toastmasters chapter',
            'type' => EventType::Task,
            'status' => EventStatus::Cancelled,
            'priority' => EventPriority::Low,
            'start_at' => now()->subWeeks(1),
            'end_at' => now()->subWeeks(1)->addDays(1),
            'visibility' => EventVisibility::Private,
        ])->tags()->attach([$t['social']->id]);

        // Skipped backlog item
        Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => null,
            'title' => 'Submit talk proposal to Laracon',
            'type' => EventType::Task,
            'status' => EventStatus::Skipped,
            'priority' => EventPriority::Medium,
            'start_at' => now()->subDays(5),
            'end_at' => now()->subDays(5)->addHours(1),
            'visibility' => EventVisibility::Private,
        ])->tags()->attach([$t['career']->id]);

        // All-day event in backlog
        Event::factory()->allDay()->create([
            'user_id' => $user->id,
            'milestone_id' => null,
            'title' => 'No-screen rest day',
            'type' => EventType::Event,
            'status' => EventStatus::Upcoming,
            'priority' => EventPriority::Low,
            'is_all_day' => true,
            'start_at' => now()->addDays(10)->startOfDay(),
            'visibility' => EventVisibility::Private,
        ])->tags()->attach([$t['health']->id]);

        // Custom-coloured event
        Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => null,
            'title' => 'Annual GP check-up',
            'type' => EventType::Event,
            'status' => EventStatus::Upcoming,
            'priority' => EventPriority::Medium,
            'color' => '#22c55e',
            'start_at' => now()->addWeeks(3),
            'end_at' => now()->addWeeks(3)->addHours(1),
            'location' => 'Dr. Patel, City Medical Centre',
            'visibility' => EventVisibility::Private,
        ])->tags()->attach([$t['health']->id]);

        // Very long title edge case
        Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => null,
            'title' => 'Research and compare all available project management and life-planning tools including Notion, Linear, Todoist, Things 3, and Obsidian for personal workflow optimization',
            'type' => EventType::Task,
            'status' => EventStatus::Draft,
            'priority' => EventPriority::Low,
            'start_at' => now()->addMonths(1),
            'end_at' => now()->addMonths(1)->addWeeks(2),
            'visibility' => EventVisibility::Private,
        ])->tags()->attach([$t['research']->id, $t['learning']->id]);

        // ─────────────────────────────────────────────────────────────────────
        // HIERARCHY EDGE CASES
        // Each block tests a distinct scenario for the collapsible sub-task UI
        // ─────────────────────────────────────────────────────────────────────

        // ── Edge case 1: 1 parent → 1 child (singular label) ─────────────────
        $hParent1 = Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => $msLaunch->id,
            'title' => 'Prepare client onboarding kit',
            'type' => EventType::Task,
            'status' => EventStatus::Upcoming,
            'priority' => EventPriority::Medium,
            'start_at' => now()->addWeeks(3),
            'end_at' => now()->addWeeks(3)->addDays(2),
        ]);
        $hParent1->tags()->attach([$t['career']->id]);

        Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => $msLaunch->id,
            'parent_event_id' => $hParent1->id,
            'title' => 'Write welcome email template',
            'type' => EventType::Task,
            'status' => EventStatus::Upcoming,
            'priority' => EventPriority::Medium,
            'start_at' => now()->addWeeks(3),
            'end_at' => now()->addWeeks(3)->addHours(2),
        ])->tags()->attach([$t['career']->id]);

        // ── Edge case 2: parent + 5 mixed-status children (expand/collapse) ──
        $hParent2 = Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => $msCert->id,
            'title' => 'Build home lab for AWS practice',
            'description' => 'Set up EC2, VPC, S3 and IAM locally via LocalStack.',
            'type' => EventType::Task,
            'status' => EventStatus::InProgress,
            'priority' => EventPriority::High,
            'start_at' => now()->subDays(5),
            'end_at' => now()->addWeeks(1),
        ]);
        $hParent2->tags()->attach([$t['learning']->id, $t['research']->id]);

        foreach ([
            ['Install LocalStack via Docker',   EventStatus::Completed, EventPriority::High,   now()->subDays(5),  now()->subDays(5)->addHours(1)],
            ['Configure IAM users & roles',     EventStatus::Completed, EventPriority::High,   now()->subDays(4),  now()->subDays(4)->addHours(2)],
            ['Create and test VPC subnets',     EventStatus::InProgress, EventPriority::High,  now()->subDays(2),  now()->addDays(1)],
            ['Set up S3 buckets with policies', EventStatus::Upcoming,  EventPriority::Medium, now()->addDays(2),  now()->addDays(3)],
            ['Run end-to-end smoke test',       EventStatus::Upcoming,  EventPriority::Medium, now()->addWeeks(1), now()->addWeeks(1)->addHours(3)],
        ] as [$title, $status, $priority, $start, $end]) {
            Event::factory()->create([
                'user_id' => $user->id,
                'milestone_id' => $msCert->id,
                'parent_event_id' => $hParent2->id,
                'title' => $title,
                'type' => EventType::Task,
                'status' => $status,
                'priority' => $priority,
                'start_at' => $start,
                'end_at' => $end,
            ])->tags()->attach([$t['learning']->id]);
        }

        // ── Edge case 3: parent completed, children still in mixed states ─────
        //    Tests that child status is independent of parent status
        $hParent3 = Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => $msEmergency->id,
            'title' => 'Negotiate salary raise',
            'type' => EventType::Task,
            'status' => EventStatus::Completed,
            'priority' => EventPriority::Critical,
            'start_at' => now()->subWeeks(2),
            'end_at' => now()->subDays(3),
        ]);
        $hParent3->tags()->attach([$t['career']->id, $t['finance']->id]);

        Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => $msEmergency->id,
            'parent_event_id' => $hParent3->id,
            'title' => 'Research market rate for senior dev role',
            'type' => EventType::Task,
            'status' => EventStatus::Completed,
            'priority' => EventPriority::High,
            'start_at' => now()->subWeeks(2),
            'end_at' => now()->subWeeks(2)->addDays(2),
        ])->tags()->attach([$t['research']->id]);

        Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => $msEmergency->id,
            'parent_event_id' => $hParent3->id,
            'title' => 'Prepare negotiation talking points',
            'type' => EventType::Task,
            'status' => EventStatus::Upcoming,     // parent done but this child still pending
            'priority' => EventPriority::Medium,
            'start_at' => now()->addDays(1),
            'end_at' => now()->addDays(1)->addHours(2),
        ]);

        Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => $msEmergency->id,
            'parent_event_id' => $hParent3->id,
            'title' => 'Follow-up email after meeting',
            'type' => EventType::Task,
            'status' => EventStatus::Skipped,      // deliberately skipped
            'priority' => EventPriority::Low,
            'start_at' => now()->subDays(1),
            'end_at' => now()->subDays(1)->addHours(1),
        ]);

        // ── Edge case 4: child with snoozed status ────────────────────────────
        $hParent4 = Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => $msLaunch->id,
            'title' => 'Set up bookkeeping system',
            'type' => EventType::Task,
            'status' => EventStatus::InProgress,
            'priority' => EventPriority::High,
            'start_at' => now()->subDays(1),
            'end_at' => now()->addDays(4),
        ]);

        Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => $msLaunch->id,
            'parent_event_id' => $hParent4->id,
            'title' => 'Choose accounting software (FreeAgent vs Wave)',
            'type' => EventType::Task,
            'status' => EventStatus::Upcoming,
            'priority' => EventPriority::Medium,
            'start_at' => now()->addDays(1),
            'end_at' => now()->addDays(1)->addHours(1),
        ]);

        // Snoozed child
        Event::factory()->snoozed()->create([
            'user_id' => $user->id,
            'milestone_id' => $msLaunch->id,
            'parent_event_id' => $hParent4->id,
            'title' => 'Import first batch of receipts',
            'type' => EventType::Task,
            'status' => EventStatus::Upcoming,
            'priority' => EventPriority::Low,
            'start_at' => now()->addDays(2),
            'end_at' => now()->addDays(2)->addHours(1),
            'snoozed_until' => now()->addHours(8),
            'snooze_count' => 1,
        ]);

        // ── Edge case 5: child with very long title + no dates ────────────────
        //    Tests truncation & graceful handling of null start_at in children
        $hParent5 = Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => null,     // backlog parent
            'title' => 'Overhaul personal knowledge base',
            'type' => EventType::Task,
            'status' => EventStatus::InProgress,
            'priority' => EventPriority::Medium,
            'start_at' => now()->subDays(3),
            'end_at' => now()->addWeeks(2),
        ]);
        $hParent5->tags()->attach([$t['learning']->id, $t['research']->id]);

        // Child with very long title
        Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => null,
            'parent_event_id' => $hParent5->id,
            'title' => 'Migrate all book notes, course summaries, and research papers from scattered Notion pages into a unified Obsidian vault with proper MOC structure',
            'type' => EventType::Task,
            'status' => EventStatus::Upcoming,
            'priority' => EventPriority::Medium,
            'start_at' => now()->addDays(1),
            'end_at' => now()->addDays(5),
        ]);

        // Child with no dates at all
        Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => null,
            'parent_event_id' => $hParent5->id,
            'title' => 'Design tagging taxonomy (no deadline)',
            'type' => EventType::Task,
            'status' => EventStatus::Draft,
            'priority' => EventPriority::Low,
            'start_at' => null,
            'end_at' => null,
        ])->tags()->attach([$t['research']->id]);

        // Child with different tags from parent
        Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => null,
            'parent_event_id' => $hParent5->id,
            'title' => 'Export Roam Research graph backup',
            'type' => EventType::Task,
            'status' => EventStatus::Completed,
            'priority' => EventPriority::Low,
            'start_at' => now()->subDays(3),
            'end_at' => now()->subDays(3)->addHours(1),
        ])->tags()->attach([$t['home']->id, $t['urgent']->id]);

        // ── Edge case 6: all children completed (parent still in-progress) ────
        $hParent6 = Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => null,     // backlog
            'title' => 'Tax return prep',
            'type' => EventType::Task,
            'status' => EventStatus::InProgress,
            'priority' => EventPriority::Critical,
            'start_at' => now()->subWeeks(1),
            'end_at' => now()->addWeeks(1),
        ]);
        $hParent6->tags()->attach([$t['finance']->id, $t['urgent']->id]);

        foreach ([
            ['Gather all payslips and P60', now()->subWeeks(1),             now()->subWeeks(1)->addHours(2)],
            ['Download bank statements',    now()->subDays(6),              now()->subDays(6)->addHours(1)],
            ['Log business expenses',       now()->subDays(4),              now()->subDays(4)->addHours(3)],
            ['Complete self-assessment form', now()->subDays(2),            now()->subDays(2)->addHours(2)],
        ] as [$title, $start, $end]) {
            Event::factory()->create([
                'user_id' => $user->id,
                'milestone_id' => null,
                'parent_event_id' => $hParent6->id,
                'title' => $title,
                'type' => EventType::Task,
                'status' => EventStatus::Completed,
                'priority' => EventPriority::High,
                'start_at' => $start,
                'end_at' => $end,
            ])->tags()->attach([$t['finance']->id]);
        }

        // ── Edge case 7: cancelled/skipped children ───────────────────────────
        $hParent7 = Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => $msFitness->id,
            'title' => 'Recovery & injury prevention week',
            'type' => EventType::Task,
            'status' => EventStatus::Upcoming,
            'priority' => EventPriority::Medium,
            'start_at' => now()->addWeeks(2),
            'end_at' => now()->addWeeks(3),
        ]);
        $hParent7->tags()->attach([$t['health']->id]);

        Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => $msFitness->id,
            'parent_event_id' => $hParent7->id,
            'title' => 'Book sports physio session',
            'type' => EventType::Task,
            'status' => EventStatus::Upcoming,
            'priority' => EventPriority::High,
            'start_at' => now()->addWeeks(2),
            'end_at' => now()->addWeeks(2)->addHours(1),
        ]);

        Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => $msFitness->id,
            'parent_event_id' => $hParent7->id,
            'title' => 'Foam rolling session (cancelled — injury)',
            'type' => EventType::Event,
            'status' => EventStatus::Cancelled,
            'priority' => EventPriority::Low,
            'start_at' => now()->addWeeks(2)->addDays(1),
            'end_at' => now()->addWeeks(2)->addDays(1)->addMinutes(30),
        ]);

        Event::factory()->create([
            'user_id' => $user->id,
            'milestone_id' => $msFitness->id,
            'parent_event_id' => $hParent7->id,
            'title' => 'Ice bath (skipped — too cold)',
            'type' => EventType::Event,
            'status' => EventStatus::Skipped,
            'priority' => EventPriority::Low,
            'start_at' => now()->addWeeks(2)->addDays(2),
            'end_at' => now()->addWeeks(2)->addDays(2)->addMinutes(20),
        ]);
    }
}

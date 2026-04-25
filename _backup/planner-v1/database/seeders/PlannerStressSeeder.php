<?php

namespace Database\Seeders;

use App\Enums\DeadlineType;
use App\Enums\EventPriority;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\EventVisibility;
use App\Enums\MilestonePriority;
use App\Enums\MilestoneStatus;
use App\Enums\ProgressSource;
use App\Models\Event;
use App\Models\Milestone;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Stress-test seeder — generates ~100 records per scenario to expose
 * scaling, layout, and performance issues in the Planner UI.
 *
 * Scenarios:
 *   1.  25 × Active / Critical / Soft deadline  (8–15 events each)
 *   2.  25 × Active / High    / Hard deadline   (5–12 events, manual progress)
 *   3.  25 × Overdue (Active, end_at in past)   (4–10 events)
 *   4.  25 × Completed                          (3–10 completed events)
 *   5.  12 × Paused  (future start)             (1–3 draft events)
 *   6.  13 × Cancelled                          (1–5 cancelled events)
 *   7.  10 × Active with ZERO events            (0% progress edge case)
 *   8.   1 × Dense milestone with 60 events     (scroll / perf edge case)
 *   9.  15 × Active with exactly 1 event each   (sparse display edge case)
 *  10. 100 × Backlog events (no milestone)       (all status/type combos)
 */
class PlannerStressSeeder extends Seeder
{
    private array $tagIds = [];

    private array $allStatuses = [];

    private array $allTypes = [];

    private array $allPriorities = [];

    private array $allVisibilities = [];

    private array $msPriorities = [];

    public function run(): void
    {
        $user = User::where('email', 'bipin.paneru.9@gmail.com')->firstOrFail();

        // Re-use existing tags created by PlannerSeeder, or create them fresh.
        // firstOrCreate makes this idempotent across multiple seeder runs.
        $tagDefs = [
            ['name' => 'health',   'color' => '#22c55e'],
            ['name' => 'career',   'color' => '#3b82f6'],
            ['name' => 'finance',  'color' => '#f59e0b'],
            ['name' => 'learning', 'color' => '#a855f7'],
            ['name' => 'social',   'color' => '#ec4899'],
            ['name' => 'travel',   'color' => '#06b6d4'],
            ['name' => 'home',     'color' => '#f97316'],
            ['name' => 'urgent',   'color' => '#ef4444'],
            ['name' => 'research', 'color' => '#64748b'],
        ];

        $tags = collect($tagDefs)->map(fn ($attrs) => Tag::firstOrCreate(
            ['user_id' => $user->id, 'name' => $attrs['name']],
            ['color' => $attrs['color']],
        ));

        $this->tagIds = $tags->pluck('id')->toArray();
        $this->allStatuses = EventStatus::cases();
        $this->allTypes = EventType::cases();
        $this->allPriorities = EventPriority::cases();
        $this->allVisibilities = EventVisibility::cases();
        $this->msPriorities = MilestonePriority::cases();

        $userId = $user->id;

        // ─────────────────────────────────────────────────────────────────────
        // SCENARIO 1: 25 × Active / Critical / Soft deadline
        //   Events: mix of all active statuses (8–15 per milestone)
        // ─────────────────────────────────────────────────────────────────────
        $this->command->info('[Stress] Scenario 1: Active/Critical/Soft — 25 milestones');
        for ($i = 0; $i < 25; $i++) {
            $start = now()->subWeeks(rand(1, 8));
            $end = now()->addWeeks(rand(2, 16));

            $milestone = Milestone::factory()->create([
                'user_id' => $userId,
                'status' => MilestoneStatus::Active,
                'priority' => MilestonePriority::Critical,
                'deadline_type' => DeadlineType::Soft,
                'progress_source' => ProgressSource::Derived,
                'start_at' => $start,
                'end_at' => $end,
                'color' => fake()->hexColor(),
            ]);

            $this->attachRandomTags($milestone, rand(1, 3));
            $this->createEventsForMilestone($milestone, $userId, rand(8, 15), [
                EventStatus::Completed,
                EventStatus::InProgress,
                EventStatus::Upcoming,
                EventStatus::Draft,
            ]);
        }

        // ─────────────────────────────────────────────────────────────────────
        // SCENARIO 2: 25 × Active / High / Hard deadline / manual progress
        //   Events: completed, upcoming, in_progress (5–12 per milestone)
        // ─────────────────────────────────────────────────────────────────────
        $this->command->info('[Stress] Scenario 2: Active/High/Hard — 25 milestones');
        for ($i = 0; $i < 25; $i++) {
            $milestone = Milestone::factory()->hardDeadline()->create([
                'user_id' => $userId,
                'status' => MilestoneStatus::Active,
                'priority' => MilestonePriority::High,
                'progress_source' => ProgressSource::Manual,
                'progress_override' => rand(5, 95),
                'start_at' => now()->subWeeks(rand(2, 6)),
                'end_at' => now()->addWeeks(rand(1, 8)),
                'color' => fake()->hexColor(),
            ]);

            $this->attachRandomTags($milestone, rand(1, 3));
            $this->createEventsForMilestone($milestone, $userId, rand(5, 12), [
                EventStatus::Completed,
                EventStatus::Upcoming,
                EventStatus::InProgress,
            ]);
        }

        // ─────────────────────────────────────────────────────────────────────
        // SCENARIO 3: 25 × Overdue (Active, end_at in the past)
        //   Tests: overdue badge, expired timelines, mixed completion
        // ─────────────────────────────────────────────────────────────────────
        $this->command->info('[Stress] Scenario 3: Overdue/Active — 25 milestones');
        for ($i = 0; $i < 25; $i++) {
            $milestone = Milestone::factory()->overdue()->create([
                'user_id' => $userId,
                'status' => MilestoneStatus::Active,
                'priority' => fake()->randomElement($this->msPriorities),
                'start_at' => now()->subMonths(rand(3, 12)),
                'end_at' => now()->subDays(rand(1, 90)),
                'color' => fake()->hexColor(),
            ]);

            $this->attachRandomTags($milestone, rand(1, 2));
            $this->createEventsForMilestone($milestone, $userId, rand(4, 10), [
                EventStatus::Completed,
                EventStatus::Upcoming,
                EventStatus::Cancelled,
                EventStatus::Skipped,
            ]);
        }

        // ─────────────────────────────────────────────────────────────────────
        // SCENARIO 4: 25 × Completed milestones — all events completed
        //   Tests: completed tab rendering, 100% progress bar
        // ─────────────────────────────────────────────────────────────────────
        $this->command->info('[Stress] Scenario 4: Completed — 25 milestones');
        for ($i = 0; $i < 25; $i++) {
            $milestone = Milestone::factory()->completed()->create([
                'user_id' => $userId,
                'priority' => fake()->randomElement($this->msPriorities),
                'start_at' => now()->subMonths(rand(3, 18)),
                'end_at' => now()->subDays(rand(7, 90)),
                'color' => fake()->hexColor(),
            ]);

            $this->attachRandomTags($milestone, rand(1, 3));

            $eventCount = rand(3, 10);
            for ($j = 0; $j < $eventCount; $j++) {
                $start = now()->subMonths(rand(1, 6));

                Event::factory()->completed()->create([
                    'user_id' => $userId,
                    'milestone_id' => $milestone->id,
                    'type' => fake()->randomElement([EventType::Task, EventType::Event]),
                    'start_at' => $start,
                    'end_at' => $start->copy()->addHours(rand(1, 8)),
                ]);
            }
        }

        // ─────────────────────────────────────────────────────────────────────
        // SCENARIO 5: 12 × Paused milestones — future start, sparse events
        //   Tests: paused badge, future milestones, 1–3 draft events
        // ─────────────────────────────────────────────────────────────────────
        $this->command->info('[Stress] Scenario 5: Paused — 12 milestones');
        for ($i = 0; $i < 12; $i++) {
            $milestone = Milestone::factory()->create([
                'user_id' => $userId,
                'status' => MilestoneStatus::Paused,
                'priority' => fake()->randomElement($this->msPriorities),
                'start_at' => now()->addMonths(rand(1, 6)),
                'end_at' => now()->addMonths(rand(7, 18)),
                'color' => fake()->hexColor(),
            ]);

            $this->attachRandomTags($milestone, rand(1, 2));

            $eventCount = rand(1, 3);
            for ($j = 0; $j < $eventCount; $j++) {
                Event::factory()->draft()->create([
                    'user_id' => $userId,
                    'milestone_id' => $milestone->id,
                    'start_at' => now()->addMonths(rand(1, 6)),
                    'end_at' => now()->addMonths(rand(7, 8)),
                ]);
            }
        }

        // ─────────────────────────────────────────────────────────────────────
        // SCENARIO 6: 13 × Cancelled milestones
        //   Tests: cancelled tab, cancelled events within
        // ─────────────────────────────────────────────────────────────────────
        $this->command->info('[Stress] Scenario 6: Cancelled — 13 milestones');
        for ($i = 0; $i < 13; $i++) {
            $milestone = Milestone::factory()->create([
                'user_id' => $userId,
                'status' => MilestoneStatus::Cancelled,
                'priority' => fake()->randomElement($this->msPriorities),
                'start_at' => now()->subMonths(rand(1, 6)),
                'end_at' => now()->subDays(rand(1, 90)),
                'color' => rand(0, 1) ? fake()->hexColor() : null,
            ]);

            if (rand(0, 1)) {
                $this->attachRandomTags($milestone, rand(1, 2));
            }

            $eventCount = rand(1, 5);
            for ($j = 0; $j < $eventCount; $j++) {
                $start = now()->subMonths(rand(1, 3));

                Event::factory()->create([
                    'user_id' => $userId,
                    'milestone_id' => $milestone->id,
                    'status' => EventStatus::Cancelled,
                    'start_at' => $start,
                    'end_at' => $start->copy()->addHours(rand(1, 4)),
                ]);
            }
        }

        // ─────────────────────────────────────────────────────────────────────
        // SCENARIO 7: 10 × Active milestones with ZERO events
        //   Tests: 0% progress bar, empty event list state
        // ─────────────────────────────────────────────────────────────────────
        $this->command->info('[Stress] Scenario 7: Zero-event milestones — 10');
        for ($i = 0; $i < 10; $i++) {
            Milestone::factory()->create([
                'user_id' => $userId,
                'status' => MilestoneStatus::Active,
                'priority' => fake()->randomElement($this->msPriorities),
                'start_at' => now()->subWeeks(rand(1, 4)),
                'end_at' => now()->addMonths(rand(1, 6)),
            ]);
        }

        // ─────────────────────────────────────────────────────────────────────
        // SCENARIO 8: 1 × Dense milestone with 60 events
        //   Tests: scroll performance, pagination, all status/type combos
        // ─────────────────────────────────────────────────────────────────────
        $this->command->info('[Stress] Scenario 8: Dense 60-event milestone');
        $denseMilestone = Milestone::factory()->create([
            'user_id' => $userId,
            'title' => '[Stress] Dense — 60 events in one milestone',
            'description' => 'Stress-test milestone: renders a very large number of events to expose scroll, grouping, and pagination edge cases.',
            'status' => MilestoneStatus::Active,
            'priority' => MilestonePriority::Critical,
            'start_at' => now()->subMonths(6),
            'end_at' => now()->addMonths(6),
            'color' => '#6366f1',
        ]);

        for ($j = 0; $j < 60; $j++) {
            $status = fake()->randomElement($this->allStatuses);
            $type = fake()->randomElement($this->allTypes);
            $start = fake()->dateTimeBetween('-6 months', '+6 months');
            $isSnoozed = $status === EventStatus::Upcoming && rand(0, 4) === 0;

            $event = Event::factory()->create([
                'user_id' => $userId,
                'milestone_id' => $denseMilestone->id,
                'status' => $status,
                'type' => $type,
                'priority' => fake()->randomElement($this->allPriorities),
                'start_at' => $start,
                'end_at' => (clone $start)->modify('+'.rand(1, 8).' hours'),
                'snoozed_until' => $isSnoozed ? now()->addHours(rand(1, 8)) : null,
                'snooze_count' => $isSnoozed ? rand(1, 5) : 0,
                'color' => rand(0, 4) === 0 ? fake()->hexColor() : null,
                'location' => rand(0, 3) === 0 ? fake()->city() : null,
            ]);

            if (rand(0, 2) !== 0) {
                $event->tags()->attach(
                    fake()->randomElements($this->tagIds, rand(1, 2))
                );
            }
        }

        // ─────────────────────────────────────────────────────────────────────
        // SCENARIO 9: 15 × Active milestones with exactly 1 event each
        //   Tests: singular event rendering, sparse progress bar
        // ─────────────────────────────────────────────────────────────────────
        $this->command->info('[Stress] Scenario 9: Single-event milestones — 15');
        for ($i = 0; $i < 15; $i++) {
            $milestone = Milestone::factory()->create([
                'user_id' => $userId,
                'status' => MilestoneStatus::Active,
                'priority' => fake()->randomElement($this->msPriorities),
                'start_at' => now()->subWeeks(rand(0, 4)),
                'end_at' => now()->addMonths(rand(1, 6)),
            ]);

            Event::factory()->create([
                'user_id' => $userId,
                'milestone_id' => $milestone->id,
                'status' => fake()->randomElement([EventStatus::Upcoming, EventStatus::Completed]),
                'start_at' => now()->addDays(rand(1, 30)),
                'end_at' => now()->addDays(rand(1, 30))->addHours(rand(1, 4)),
            ]);
        }

        // ─────────────────────────────────────────────────────────────────────
        // SCENARIO 10: 100 × Backlog events (milestone_id = null)
        //   Tests: backlog list at scale, all status/type/priority combos,
        //          snoozed events, all-day events, long titles, coloured items
        // ─────────────────────────────────────────────────────────────────────
        $this->command->info('[Stress] Scenario 10: Backlog — 100 events');

        // Long-title edge case (first of the 100)
        Event::factory()->create([
            'user_id' => $userId,
            'milestone_id' => null,
            'title' => 'Research and compare all available project management, productivity, and life-planning tools — Notion, Linear, Todoist, Things 3, Obsidian, Logseq — for long-term personal workflow optimisation across multiple life domains',
            'type' => EventType::Task,
            'status' => EventStatus::Draft,
            'priority' => EventPriority::Low,
            'start_at' => now()->addMonths(1),
            'end_at' => now()->addMonths(1)->addWeeks(2),
            'visibility' => EventVisibility::Private,
        ]);

        // No-tag edge case
        Event::factory()->create([
            'user_id' => $userId,
            'milestone_id' => null,
            'title' => 'Untagged task — tests empty-tag display in list',
            'type' => EventType::Task,
            'status' => EventStatus::Upcoming,
            'priority' => EventPriority::Medium,
            'start_at' => now()->addDays(5),
            'end_at' => now()->addDays(5)->addHours(2),
            'visibility' => EventVisibility::Private,
        ]);

        // Remaining 98 random backlog events
        for ($i = 0; $i < 98; $i++) {
            $status = fake()->randomElement($this->allStatuses);
            $type = fake()->randomElement($this->allTypes);
            $isAllDay = rand(0, 9) === 0;
            $isSnoozed = $status === EventStatus::Upcoming && rand(0, 5) === 0;
            $start = fake()->dateTimeBetween('-3 months', '+6 months');

            $event = Event::factory()->create([
                'user_id' => $userId,
                'milestone_id' => null,
                'status' => $status,
                'type' => $type,
                'priority' => fake()->randomElement($this->allPriorities),
                'visibility' => fake()->randomElement($this->allVisibilities),
                'is_all_day' => $isAllDay,
                'start_at' => $start,
                'end_at' => $isAllDay ? null : (clone $start)->modify('+'.rand(1, 8).' hours'),
                'snoozed_until' => $isSnoozed ? now()->addHours(rand(1, 12)) : null,
                'snooze_count' => $isSnoozed ? rand(1, 7) : 0,
                'color' => rand(0, 3) === 0 ? fake()->hexColor() : null,
                'location' => rand(0, 2) === 0 ? fake()->city() : null,
            ]);

            // 70% chance of tags
            if (rand(0, 9) < 7) {
                $event->tags()->attach(
                    fake()->randomElements($this->tagIds, rand(1, 3))
                );
            }
        }

        // ─────────────────────────────────────────────────────────────────────
        // SCENARIO 11: Ignorable-priority coverage
        //   - 8 × Active milestones with Ignorable priority + nested events
        //   - 40 × backlog events with Ignorable priority across all statuses
        //   Tests: lowest-priority badge, sort order, filter chip rendering
        // ─────────────────────────────────────────────────────────────────────
        $this->command->info('[Stress] Scenario 11: Ignorable priority — 8 milestones + 40 backlog events');
        for ($i = 0; $i < 8; $i++) {
            $milestone = Milestone::factory()->create([
                'user_id' => $userId,
                'status' => MilestoneStatus::Active,
                'priority' => MilestonePriority::Ignorable,
                'start_at' => now()->subWeeks(rand(0, 4)),
                'end_at' => now()->addMonths(rand(2, 12)),
                'color' => fake()->hexColor(),
            ]);

            $this->attachRandomTags($milestone, rand(0, 2));

            $eventCount = rand(3, 8);
            for ($j = 0; $j < $eventCount; $j++) {
                $start = fake()->dateTimeBetween('-1 month', '+6 months');
                $event = Event::factory()->create([
                    'user_id' => $userId,
                    'milestone_id' => $milestone->id,
                    'status' => fake()->randomElement([
                        EventStatus::Draft,
                        EventStatus::Upcoming,
                        EventStatus::InProgress,
                        EventStatus::Completed,
                    ]),
                    'type' => fake()->randomElement($this->allTypes),
                    'priority' => EventPriority::Ignorable,
                    'start_at' => $start,
                    'end_at' => (clone $start)->modify('+'.rand(1, 6).' hours'),
                ]);
                if (rand(0, 1)) {
                    $event->tags()->attach(fake()->randomElements($this->tagIds, rand(1, 2)));
                }
            }
        }

        for ($i = 0; $i < 40; $i++) {
            $status = fake()->randomElement($this->allStatuses);
            $start = fake()->dateTimeBetween('-3 months', '+6 months');
            $event = Event::factory()->create([
                'user_id' => $userId,
                'milestone_id' => null,
                'status' => $status,
                'type' => fake()->randomElement($this->allTypes),
                'priority' => EventPriority::Ignorable,
                'start_at' => $start,
                'end_at' => (clone $start)->modify('+'.rand(1, 8).' hours'),
                'visibility' => fake()->randomElement($this->allVisibilities),
            ]);
            if (rand(0, 1)) {
                $event->tags()->attach(fake()->randomElements($this->tagIds, rand(1, 2)));
            }
        }

        // ─────────────────────────────────────────────────────────────────────
        // SCENARIO 12: All-priorities-balanced sample
        //   25 backlog events, exactly 5 per priority level (incl. Ignorable)
        //   Tests: priority distribution, group-by-priority bucketing
        // ─────────────────────────────────────────────────────────────────────
        $this->command->info('[Stress] Scenario 12: Balanced priority sample — 25 backlog events');
        foreach ($this->allPriorities as $priority) {
            for ($k = 0; $k < 5; $k++) {
                $start = fake()->dateTimeBetween('-2 months', '+4 months');
                Event::factory()->create([
                    'user_id' => $userId,
                    'milestone_id' => null,
                    'status' => fake()->randomElement([
                        EventStatus::Draft,
                        EventStatus::Upcoming,
                        EventStatus::InProgress,
                    ]),
                    'type' => fake()->randomElement([EventType::Task, EventType::Event]),
                    'priority' => $priority,
                    'start_at' => $start,
                    'end_at' => (clone $start)->modify('+'.rand(1, 4).' hours'),
                ]);
            }
        }

        $this->command->info('[Stress] Complete — additive run finished. Re-run to stack more data.');
    }

    /**
     * Create a batch of events for a milestone, sampling from the given status pool.
     *
     * @param  array<EventStatus>  $statuses
     */
    private function createEventsForMilestone(
        Milestone $milestone,
        string $userId,
        int $count,
        array $statuses,
    ): void {
        $types = [EventType::Task, EventType::Task, EventType::Task, EventType::Event, EventType::MilestoneMarker];

        for ($j = 0; $j < $count; $j++) {
            $status = fake()->randomElement($statuses);
            $type = fake()->randomElement($types);
            $start = fake()->dateTimeBetween('-3 months', '+6 months');
            $isSnoozed = $status === EventStatus::Upcoming && rand(0, 5) === 0;

            $event = Event::factory()->create([
                'user_id' => $userId,
                'milestone_id' => $milestone->id,
                'status' => $status,
                'type' => $type,
                'priority' => fake()->randomElement($this->allPriorities),
                'start_at' => $start,
                'end_at' => (clone $start)->modify('+'.rand(1, 8).' hours'),
                'snoozed_until' => $isSnoozed ? now()->addHours(rand(1, 8)) : null,
                'snooze_count' => $isSnoozed ? rand(1, 5) : 0,
                'color' => rand(0, 4) === 0 ? fake()->hexColor() : null,
                'location' => rand(0, 3) === 0 ? fake()->city() : null,
            ]);

            if (rand(0, 2) !== 0) {
                $event->tags()->attach(
                    fake()->randomElements($this->tagIds, rand(1, 2))
                );
            }
        }
    }

    /**
     * @param  Milestone|Event  $model
     */
    private function attachRandomTags(mixed $model, int $count): void
    {
        $model->tags()->attach(
            fake()->randomElements($this->tagIds, $count)
        );
    }
}

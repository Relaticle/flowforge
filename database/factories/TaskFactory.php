<?php

namespace Relaticle\Flowforge\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Relaticle\Flowforge\Services\DecimalPosition;
use Relaticle\Flowforge\Tests\Fixtures\Task;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        $isCompleted = $this->faker->boolean(20); // 20% chance of being completed

        return [
            'title' => $this->faker->randomElement([
                $this->generateBugFixTitle(),
                $this->generateFeatureTitle(),
                $this->generateMaintenanceTitle(),
                $this->generateResearchTitle(),
            ]),
            'description' => $this->faker->optional(0.8)->paragraphs(2, true),
            'status' => $this->faker->randomElement(['todo', 'in_progress', 'completed']),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
            'order_position' => $this->generatePosition(),
            'project_id' => $this->faker->optional(0.8)->randomElement([1, 2, 3, 4, 5]),
            'assigned_to' => $this->faker->optional(0.7)->randomElement([1, 2, 3, 4, 5, 6]),
            'created_by' => $this->faker->optional(0.9)->randomElement([1, 2, 3, 4, 5, 6]),
            'estimated_hours' => $this->faker->optional(0.6)->numberBetween(1, 80),
            'actual_hours' => $this->faker->optional(0.3)->numberBetween(1, 120),
            'labels' => $this->faker->optional(0.5)->randomElements([
                'frontend', 'backend', 'database', 'api', 'ui/ux', 'testing',
                'security', 'performance', 'documentation', 'bug', 'enhancement',
            ], $this->faker->numberBetween(1, 3)),
            'due_date' => $this->faker->optional(0.4)->dateTimeBetween('now', '+3 months'),
            'completed_at' => $isCompleted ? $this->faker->dateTimeBetween('-1 month', 'now') : null,
        ];
    }

    public function todo(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'todo',
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high',
        ]);
    }

    public function mediumPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'medium',
        ]);
    }

    public function lowPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'low',
        ]);
    }

    public function withStatus(string $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }

    public function withPriority(string $priority): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => $priority,
        ]);
    }

    public function withPosition(string $position): static
    {
        return $this->state(fn (array $attributes) => [
            'order_position' => $position,
        ]);
    }

    private function generateBugFixTitle(): string
    {
        $bugs = [
            'Fix memory leak in user session management',
            'Resolve database connection timeout issues',
            'Fix inconsistent validation error messages',
            'Correct timezone display issues',
            'Fix broken pagination on search results',
            'Resolve API authentication edge cases',
            'Fix race condition in file upload',
            'Correct SQL injection vulnerability in reports',
            'Fix infinite loop in background jobs',
            'Resolve CORS issues with external APIs',
        ];

        return $this->faker->randomElement($bugs);
    }

    private function generateFeatureTitle(): string
    {
        $features = [
            'Implement real-time notifications system',
            'Add advanced search filters',
            'Build user dashboard analytics',
            'Create automated backup system',
            'Implement two-factor authentication',
            'Add bulk operations for data management',
            'Build RESTful API endpoints',
            'Create admin panel for user management',
            'Implement file sharing functionality',
            'Add integration with third-party services',
        ];

        return $this->faker->randomElement($features);
    }

    private function generateMaintenanceTitle(): string
    {
        $maintenance = [
            'Update dependencies to latest versions',
            'Optimize database query performance',
            'Refactor legacy authentication code',
            'Clean up unused CSS and JavaScript',
            'Update documentation and README',
            'Migrate from jQuery to vanilla JS',
            'Consolidate duplicate utility functions',
            'Update test suite coverage',
            'Optimize image compression pipeline',
            'Refactor monolithic controller classes',
        ];

        return $this->faker->randomElement($maintenance);
    }

    private function generateResearchTitle(): string
    {
        $research = [
            'Research best practices for microservices',
            'Evaluate modern frontend frameworks',
            'Investigate new database technologies',
            'Research security best practices',
            'Evaluate CI/CD pipeline improvements',
            'Research performance monitoring tools',
            'Investigate container orchestration options',
            'Research accessibility compliance standards',
            'Evaluate new testing methodologies',
            'Research scalability architecture patterns',
        ];

        return $this->faker->randomElement($research);
    }

    private function generatePosition(): string
    {
        // Generate positions in a realistic range using decimal positions
        static $position = null;

        if ($position === null) {
            $position = DecimalPosition::forEmptyColumn();
        } else {
            $position = DecimalPosition::after($position);
        }

        return $position;
    }
}

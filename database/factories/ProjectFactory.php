<?php

namespace Relaticle\Flowforge\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Relaticle\Flowforge\Tests\Fixtures\Project;
use Relaticle\Flowforge\Tests\Fixtures\User;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-6 months', '+1 month');
        $dueDate = $this->faker->dateTimeBetween($startDate, '+1 year');

        return [
            'name' => $this->faker->randomElement([
                $this->generateProductName(),
                $this->generateProjectName(),
                $this->generateFeatureName(),
            ]),
            'description' => $this->faker->paragraphs(3, true),
            'status' => $this->faker->randomElement(['active', 'on_hold', 'completed', 'archived']),
            'owner_id' => User::factory(),
            'start_date' => $startDate,
            'due_date' => $dueDate,
            'budget' => $this->faker->optional(0.7)->randomFloat(2, 1000, 500000),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    public function onHold(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'on_hold',
        ]);
    }

    public function withOwner(User $owner): static
    {
        return $this->state(fn (array $attributes) => [
            'owner_id' => $owner->id,
        ]);
    }

    private function generateProductName(): string
    {
        $products = [
            'E-Commerce Platform Redesign',
            'Customer Analytics Dashboard',
            'Mobile App Development',
            'API Gateway Implementation',
            'User Authentication System',
            'Payment Processing Integration',
            'Content Management System',
            'Real-time Chat Application',
            'Document Management Portal',
            'Business Intelligence Suite',
        ];

        return $this->faker->randomElement($products);
    }

    private function generateProjectName(): string
    {
        $adjectives = ['Advanced', 'Modern', 'Scalable', 'Innovative', 'Efficient', 'Smart'];
        $nouns = ['Platform', 'System', 'Solution', 'Framework', 'Tool', 'Service'];
        $purposes = ['Management', 'Analytics', 'Monitoring', 'Processing', 'Integration', 'Optimization'];

        return $this->faker->randomElement($adjectives) . ' ' .
               $this->faker->randomElement($nouns) . ' for ' .
               $this->faker->randomElement($purposes);
    }

    private function generateFeatureName(): string
    {
        $features = [
            'Multi-tenant Architecture Migration',
            'Real-time Notification System',
            'Advanced Search Implementation',
            'Performance Optimization Initiative',
            'Security Enhancement Project',
            'Third-party Integration Expansion',
            'Database Scaling Strategy',
            'UI/UX Modernization',
            'Automated Testing Framework',
            'DevOps Pipeline Improvement',
        ];

        return $this->faker->randomElement($features);
    }
}

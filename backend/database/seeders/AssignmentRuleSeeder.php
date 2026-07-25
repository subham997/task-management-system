<?php

namespace Database\Seeders;

use App\Models\AssignmentRule;
use App\Models\User;
use Illuminate\Database\Seeder;

class AssignmentRuleSeeder extends Seeder
{
    public function run(): void
    {
        $creator = User::query()->first() ?? User::factory()->create();

        AssignmentRule::factory()->count(2)->create([
            'created_by' => $creator->id,
        ]);
    }
}

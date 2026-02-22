<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@crm.local'],
            [
                'name' => 'CRM Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        $salesUsers = collect([
            ['name' => 'Alex Johnson', 'email' => 'alex@crm.local'],
            ['name' => 'Maria Chen', 'email' => 'maria@crm.local'],
            ['name' => 'Ravi Kumar', 'email' => 'ravi@crm.local'],
        ])->map(function (array $user) {
            return User::query()->updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make('password'),
                    'role' => 'sales_executive',
                ]
            );
        });

        if (Lead::query()->count() > 0) {
            return;
        }

        $statuses = ['new', 'contacted', 'qualified', 'interested', 'converted', 'lost'];
        $sources = ['Website', 'LinkedIn', 'Referral', 'Cold Call', 'Email Campaign'];

        for ($i = 1; $i <= 40; $i++) {
            $assignedUser = $salesUsers->random();
            $createdAt = Carbon::now()->subDays(random_int(0, 330));

            Lead::query()->create([
                'name' => "Lead {$i}",
                'email' => "lead{$i}@example.com",
                'phone' => '+1-555-01' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'source' => $sources[array_rand($sources)],
                'status' => $statuses[array_rand($statuses)],
                'assigned_user_id' => $assignedUser->id,
                'notes' => 'Sample seeded lead for CRM dashboard/testing.',
                'follow_up_date' => Carbon::now()->addDays(random_int(-7, 14))->toDateString(),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }
}

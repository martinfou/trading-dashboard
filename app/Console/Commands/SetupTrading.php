<?php
namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SetupTrading extends Command
{
    protected $signature = 'trading:setup {email?} {password?}';
    protected $description = 'Setup trading dashboard with admin user and demo data';

    public function handle()
    {
        $email = $this->argument('email') ?? 'martinfou@gmail.com';
        $password = $this->argument('password') ?? 'ght1cdkc';
        
        // Create or update user
        $user = User::updateOrCreate(
            ['email' => $email],
            ['name' => 'Martin Fournier', 'password' => Hash::make($password), 'role' => 'admin']
        );
        
        $this->info("✅ User: $email / $password");
        
        // Seed demo trades
        $this->call('db:seed', ['--class' => 'TradeSeeder']);
        
        $this->info("✅ Trading Dashboard ready!");
        $this->warn("   Login at /trading");
    }
}

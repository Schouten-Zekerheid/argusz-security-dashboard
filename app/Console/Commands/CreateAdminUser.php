<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class CreateAdminUser extends Command
{
    protected $signature = 'argusz:create-admin
        {--name= : The administrator name}
        {--email= : The administrator email address}
        {--password= : The administrator password (omit to be prompted securely)}';

    protected $description = 'Create an administrator account with local password login';

    public function handle(): int
    {
        if (! Role::where('name', 'management')->exists()) {
            $this->error('The "management" role does not exist. Run `php artisan db:seed` first.');

            return self::FAILURE;
        }

        $name = $this->option('name') ?: $this->ask('Name');
        $email = $this->option('email') ?: $this->ask('Email');
        $password = $this->option('password') ?: $this->secret('Password');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:12'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $user->syncRoles(['management']);

        $this->info("Administrator {$user->email} created.");

        return self::SUCCESS;
    }
}

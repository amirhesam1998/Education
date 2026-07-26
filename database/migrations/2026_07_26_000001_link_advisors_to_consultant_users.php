<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('advisors', 'user_id')) {
            Schema::table('advisors', function (Blueprint $table): void {
                $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            });
        }

        $consultants = DB::table('users')
            ->join('model_has_roles', function ($join): void {
                $join->on('model_has_roles.model_id', '=', 'users.id')
                    ->where('model_has_roles.model_type', User::class);
            })
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', User::ROLE_CONSULTANT)
            ->select('users.id', 'users.name', 'users.phone', 'users.status')
            ->get();

        foreach ($consultants as $user) {
            if (DB::table('advisors')->where('user_id', $user->id)->exists()) {
                continue;
            }

            $match = DB::table('advisors')
                ->whereNull('user_id')
                ->where(function ($query) use ($user): void {
                    $query->where('name', $user->name);

                    if ($user->phone) {
                        $query->orWhere('phone', $user->phone);
                    }
                })
                ->first();

            if ($match) {
                DB::table('advisors')
                    ->where('id', $match->id)
                    ->update([
                        'user_id' => $user->id,
                        'name' => $user->name,
                        'phone' => $user->phone,
                        'status' => $user->status,
                        'updated_at' => now(),
                    ]);

                continue;
            }

            DB::table('advisors')->insert([
                'user_id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'status' => $user->status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('advisors', function (Blueprint $table): void {
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('advisors', 'user_id')) {
            Schema::table('advisors', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('user_id');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->makeUsersPhoneLoginReady();
        $this->addSlotDurationColumn();
        $this->addFieldSelectionItemColumns();
    }

    public function down(): void
    {
        // Intentionally non-destructive: rollback must not remove columns or
        // mutate login/token data that may have been created after migration.
    }

    private function addSlotDurationColumn(): void
    {
        if (! Schema::hasTable('reservation_slots') || Schema::hasColumn('reservation_slots', 'duration_minutes')) {
            return;
        }

        Schema::table('reservation_slots', function (Blueprint $table): void {
            $table->unsignedSmallInteger('duration_minutes')->default(15)->after('end_time');
        });
    }

    private function addFieldSelectionItemColumns(): void
    {
        if (! Schema::hasTable('field_selection_items')) {
            return;
        }

        Schema::table('field_selection_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('field_selection_items', 'university_type')) {
                $table->string('university_type')->nullable()->after('city');
            }

            if (! Schema::hasColumn('field_selection_items', 'university_description')) {
                $table->text('university_description')->nullable()->after('university_type');
            }
        });
    }

    private function makeUsersPhoneLoginReady(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasColumn('users', 'email') && $this->isMysql()) {
            DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NULL');
        }

        if ($this->indexExists('users', 'users_phone_index')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropIndex('users_phone_index');
            });
        }

        if (Schema::hasColumn('users', 'phone')
            && ! $this->indexExists('users', 'users_phone_unique')
            && ! $this->hasDuplicatePhones()) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unique('phone', 'users_phone_unique');
            });
        }
    }

    private function hasDuplicatePhones(): bool
    {
        return DB::table('users')
            ->select('phone')
            ->whereNotNull('phone')
            ->groupBy('phone')
            ->havingRaw('COUNT(*) > 1')
            ->exists();
    }

    private function indexExists(string $table, string $index): bool
    {
        try {
            return Schema::hasIndex($table, $index);
        } catch (Throwable) {
            return collect(Schema::getIndexes($table))->contains(fn (array $schemaIndex): bool => ($schemaIndex['name'] ?? null) === $index);
        }
    }

    private function isMysql(): bool
    {
        return DB::getDriverName() === 'mysql';
    }
};

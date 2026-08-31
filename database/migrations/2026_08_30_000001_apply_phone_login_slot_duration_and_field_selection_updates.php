<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->backfillUniqueUserPhones();
        $this->makeUsersPhoneLoginReady();
        $this->renamePasswordResetTokenColumn();
        $this->addSlotDurationColumn();
        $this->addFieldSelectionItemColumns();

        DB::table('settings')
            ->whereIn('key', ['reservation_duration_minutes', 'release_slot_after_payment_rejection'])
            ->delete();
    }

    public function down(): void
    {
        if (Schema::hasTable('field_selection_items')) {
            Schema::table('field_selection_items', function (Blueprint $table): void {
                if (Schema::hasColumn('field_selection_items', 'university_description')) {
                    $table->dropColumn('university_description');
                }

                if (Schema::hasColumn('field_selection_items', 'university_type')) {
                    $table->dropColumn('university_type');
                }
            });
        }

        if (Schema::hasTable('reservation_slots') && Schema::hasColumn('reservation_slots', 'duration_minutes')) {
            Schema::table('reservation_slots', function (Blueprint $table): void {
                $table->dropColumn('duration_minutes');
            });
        }

        if (Schema::hasTable('password_reset_tokens')
            && Schema::hasColumn('password_reset_tokens', 'phone')
            && ! Schema::hasColumn('password_reset_tokens', 'email')) {
            DB::table('password_reset_tokens')->delete();

            if ($this->isMysql()) {
                DB::statement('ALTER TABLE password_reset_tokens DROP PRIMARY KEY');
                DB::statement('ALTER TABLE password_reset_tokens CHANGE phone email VARCHAR(255) NOT NULL');
                DB::statement('ALTER TABLE password_reset_tokens ADD PRIMARY KEY (email)');
            } else {
                Schema::table('password_reset_tokens', function (Blueprint $table): void {
                    $table->renameColumn('phone', 'email');
                });
            }
        }

        if (Schema::hasTable('users')) {
            if ($this->indexExists('users', 'users_phone_unique')) {
                Schema::table('users', function (Blueprint $table): void {
                    $table->dropUnique('users_phone_unique');
                });
            }

            if (Schema::hasColumn('users', 'phone') && ! $this->indexExists('users', 'users_phone_index')) {
                Schema::table('users', function (Blueprint $table): void {
                    $table->index('phone', 'users_phone_index');
                });
            }
        }
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

    private function backfillUniqueUserPhones(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'phone')) {
            return;
        }

        $used = [];

        DB::table('users')
            ->select(['id', 'phone'])
            ->orderBy('id')
            ->get()
            ->each(function (object $user) use (&$used): void {
                $phone = trim((string) $user->phone);

                if ($phone !== '' && mb_strlen($phone) <= 30 && ! isset($used[$phone])) {
                    $used[$phone] = true;

                    return;
                }

                $phone = $this->legacyPhoneFor((int) $user->id, $used);
                DB::table('users')->where('id', $user->id)->update([
                    'phone' => $phone,
                    'updated_at' => now(),
                ]);
                $used[$phone] = true;
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

        if (Schema::hasColumn('users', 'phone') && $this->isMysql()) {
            DB::statement('ALTER TABLE users MODIFY phone VARCHAR(30) NOT NULL');
        }

        if ($this->indexExists('users', 'users_phone_index')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropIndex('users_phone_index');
            });
        }

        if (Schema::hasColumn('users', 'phone') && ! $this->indexExists('users', 'users_phone_unique')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unique('phone', 'users_phone_unique');
            });
        }
    }

    private function renamePasswordResetTokenColumn(): void
    {
        if (! Schema::hasTable('password_reset_tokens')
            || ! Schema::hasColumn('password_reset_tokens', 'email')
            || Schema::hasColumn('password_reset_tokens', 'phone')) {
            return;
        }

        DB::table('password_reset_tokens')->delete();

        if ($this->isMysql()) {
            DB::statement('ALTER TABLE password_reset_tokens DROP PRIMARY KEY');
            DB::statement('ALTER TABLE password_reset_tokens CHANGE email phone VARCHAR(30) NOT NULL');
            DB::statement('ALTER TABLE password_reset_tokens ADD PRIMARY KEY (phone)');

            return;
        }

        Schema::table('password_reset_tokens', function (Blueprint $table): void {
            $table->renameColumn('email', 'phone');
        });
    }

    private function legacyPhoneFor(int $id, array $used): string
    {
        $offset = 0;

        do {
            $candidate = '09'.str_pad((string) ($id + $offset), 9, '0', STR_PAD_LEFT);
            $offset++;
        } while (isset($used[$candidate]));

        return $candidate;
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

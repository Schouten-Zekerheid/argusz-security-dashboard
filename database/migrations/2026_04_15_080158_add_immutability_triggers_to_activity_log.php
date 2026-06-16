<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::unprepared("
            CREATE TRIGGER prevent_activity_log_delete
            BEFORE DELETE ON activity_log
            FOR EACH ROW
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Audit log records cannot be deleted';
        ");

        DB::unprepared("
            CREATE TRIGGER prevent_activity_log_update
            BEFORE UPDATE ON activity_log
            FOR EACH ROW
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Audit log records cannot be modified';
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS prevent_activity_log_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS prevent_activity_log_update');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->text('progress_done')->nullable()->after('status');
            $table->text('progress_next')->nullable()->after('progress_done');
            $table->text('blocker_note')->nullable()->after('progress_next');
            $table->timestamp('completed_at')->nullable()->after('blocker_note');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['progress_done', 'progress_next', 'blocker_note', 'completed_at']);
        });
    }
};

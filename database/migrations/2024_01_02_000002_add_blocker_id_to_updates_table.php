<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('updates', function (Blueprint $table) {
            $table->foreignId('blocker_id')->nullable()->after('status')->constrained('blockers')->nullOnDelete();
        });

        $updates = DB::table('updates')->get();

        foreach ($updates as $update) {
            $content = json_decode($update->content, true);
            $blockerText = trim($content['blocker'] ?? '');

            if ($blockerText === '') {
                continue;
            }

            $user = DB::table('users')->where('id', $update->user_id)->first();

            if (! $user) {
                continue;
            }

            $blockerId = DB::table('blockers')->where('team_id', $user->team_id)->where('label', $blockerText)->value('id');

            if (! $blockerId) {
                $blockerId = DB::table('blockers')->insertGetId([
                    'team_id' => $user->team_id,
                    'label' => $blockerText,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('updates')->where('id', $update->id)->update(['blocker_id' => $blockerId]);
        }
    }

    public function down(): void
    {
        Schema::table('updates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('blocker_id');
        });
    }
};

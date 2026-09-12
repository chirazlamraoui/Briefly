<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_user', function (Blueprint $table) {
            $table->boolean('is_team_lead')->default(false)->after('user_id');
        });

        DB::table('team_user')
            ->join('users', 'users.id', '=', 'team_user.user_id')
            ->where('users.role', 'TEAM_LEAD')
            ->whereColumn('users.team_id', 'team_user.team_id')
            ->update(['team_user.is_team_lead' => true]);

        DB::table('team_user')
            ->join('users', 'users.id', '=', 'team_user.user_id')
            ->where('users.role', 'TEAM_LEAD')
            ->whereNull('users.team_id')
            ->update(['team_user.is_team_lead' => true]);
    }

    public function down(): void
    {
        Schema::table('team_user', function (Blueprint $table) {
            $table->dropColumn('is_team_lead');
        });
    }
};

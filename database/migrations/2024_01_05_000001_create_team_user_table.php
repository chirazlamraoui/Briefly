<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_user', function (Blueprint $table) {
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['team_id', 'user_id']);
        });

        DB::table('team_user')->insert(
            DB::table('users')
                ->whereNotNull('team_id')
                ->get(['team_id', 'id as user_id'])
                ->map(fn ($row) => [
                    'team_id' => $row->team_id,
                    'user_id' => $row->user_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                ->all()
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('team_user');
    }
};

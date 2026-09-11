<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('updates');
        Schema::dropIfExists('briefs');
        Schema::dropIfExists('blockers');
    }

    public function down(): void
    {
        // Legacy tables removed intentionally. Restore from git history if needed.
    }
};

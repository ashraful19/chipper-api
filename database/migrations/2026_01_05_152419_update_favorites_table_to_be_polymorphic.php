<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('favorites', function (Blueprint $table) {
            $table->morphs('favoritable');
        });

        DB::table('favorites')
            ->whereNotNull('post_id')
            ->update([
                'favoritable_id' => DB::raw('post_id'),
                'favoritable_type' => \App\Models\Post::class,
            ]);

        Schema::table('favorites', function (Blueprint $table) {
            $table->dropColumn('post_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('favorites', function (Blueprint $table) {
            $table->unsignedBigInteger('post_id')->nullable()->after('id');
        });

        DB::table('favorites')
            ->whereNotNull('favoritable_id')
            ->where('favoritable_type', \App\Models\Post::class)
            ->update([
                'post_id' => DB::raw('favoritable_id'),
            ]);

        Schema::table('favorites', function (Blueprint $table) {
            $table->dropMorphs('favoritable');
        });
    }
};

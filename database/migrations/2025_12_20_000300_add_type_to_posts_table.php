<?php

use App\Blog\Enums\PostType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('type')
                ->default(PostType::ARTICLE->value)
                ->after('slug');

            $table->index(['type', 'posted_at'], 'posts_type_posted_at_index');
        });

        DB::table('posts')->update(['type' => PostType::ARTICLE->value]);
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_type_posted_at_index');
            $table->dropColumn('type');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('team', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('team_member', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('team')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_admin')->default(false);
            $table->timestamps();
        });

        Schema::create('shift_category', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color');

            $table->foreignId('owner_id')->nullable()->constrained('team')->onDelete('cascade');

            $table->timestamps();
        });

        Schema::create('shifts', function (Blueprint $table) {
            $table->id();

            $table->dateTime('start_time');
            $table->dateTime('end_time');

            $table->string('description')->nullable();

            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('shift_category_id')->constrained('shift_category')->onDelete('cascade');

            // Make it possible to link a shift to another connected shift WORK A -> WORK B -> WORK C (No breaks between)
            // This allows to change the category of a shift without affecting making weird time breaks between connected shifts.
            $table->foreignId('connected_shift_id')->nullable()->constrained('shifts')->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('shift_category');
        Schema::dropIfExists('team_member');
        Schema::dropIfExists('team');
    }
};

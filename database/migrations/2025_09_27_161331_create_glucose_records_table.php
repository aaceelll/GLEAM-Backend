<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('glucose_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('glucose_level', 5, 2); // mg/dL
            $table->enum('measurement_type', ['puasa', 'setelah_makan', 'sewaktu']);
            $table->text('notes')->nullable();
            $table->timestamp('measured_at');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('glucose_records');
    }
};

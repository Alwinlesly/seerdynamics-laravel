<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_id')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('client_id');
            $table->date('starting_date');
            $table->date('ending_date');
            $table->date('actual_starting_date')->nullable();
            $table->date('actual_ending_date')->nullable();
            $table->string('status')->default('Open');
            $table->string('project_type')->nullable();
            $table->decimal('project_value', 15, 2)->nullable();
            $table->string('project_currency', 10)->nullable();
            $table->decimal('total_hours', 10, 2)->nullable();
            $table->string('contract_copy')->nullable();
            $table->text('services_offered')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_visible_to_customer')->default(true);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            
            $table->foreign('client_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('projects');
    }
};

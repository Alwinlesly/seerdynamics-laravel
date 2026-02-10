<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('issue_type')->nullable();
            $table->string('service')->nullable();
            $table->string('priority')->default('Medium');
            $table->date('issue_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status')->default('Open');
            $table->text('additional_mail')->nullable();
            $table->string('attachment')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tasks');
    }
};

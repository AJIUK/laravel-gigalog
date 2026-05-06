<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $gigalogTable;

    public function __construct()
    {
        $this->gigalogTable = config('gigalog.gigalog_table', 'gigalogs');
    }

    public function up(): void
    {
        Schema::create($this->gigalogTable, function (Blueprint $table) {
            $table->id();

            $table->string('class_name')->index();
            $table->string('group')->nullable()->index();
            $table->string('version');

            $table->morphs('subject');
            $table->nullableMorphs('causer');

            $table->json('data')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->gigalogTable);
    }
};

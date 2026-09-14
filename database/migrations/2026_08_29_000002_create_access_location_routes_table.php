<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_location_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('access_location_id')->constrained()->cascadeOnDelete();
            // A key from App\Modules\AccessLocation\Support\LockableRoutes::catalog() -
            // the middleware expands it to the actual URI patterns it locks.
            $table->string('route_key');
            $table->timestamps();

            $table->unique(['access_location_id', 'route_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_location_routes');
    }
};

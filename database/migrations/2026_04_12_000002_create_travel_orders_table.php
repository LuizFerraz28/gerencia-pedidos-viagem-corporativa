<?php

use App\Domain\TravelOrder\ValueObjects\TravelOrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('requester_name');
            $table->string('destination');
            $table->date('departure_date');
            $table->date('return_date');
            $table->enum('status', array_column(TravelOrderStatus::cases(), 'value'))
                  ->default(TravelOrderStatus::Requested->value);
            $table->timestamps();

            $table->index('status');
            $table->index('destination');
            $table->index(['departure_date', 'return_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_orders');
    }
};

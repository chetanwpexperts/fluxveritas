<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedInteger('seats')->nullable()->after('billing_period');
            $table->unsignedInteger('unit_price')->nullable()->after('seats');     // paise per user per month
            $table->unsignedInteger('subtotal')->nullable()->after('unit_price');  // paise, before GST
            $table->unsignedInteger('tax_amount')->default(0)->after('subtotal');  // paise
            $table->timestamp('period_start')->nullable()->after('paid_at');
            $table->timestamp('period_end')->nullable()->after('period_start');
            $table->string('receipt_number')->nullable()->unique()->after('period_end');
            $table->string('failure_reason')->nullable()->after('receipt_number');
            // status: created | paid | failed | refunded
            $table->string('razorpay_refund_id')->nullable()->after('razorpay_signature');
            $table->string('refund_status')->nullable()->after('razorpay_refund_id');  // pending | processed | failed
            $table->unsignedInteger('refund_amount')->nullable()->after('refund_status');
            $table->text('refund_reason')->nullable()->after('refund_amount');
            $table->timestamp('refunded_at')->nullable()->after('refund_reason');
            $table->unsignedBigInteger('refunded_by')->nullable()->after('refunded_at');

            $table->index(['organization_id', 'status']);
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->string('billing_period')->nullable()->after('billing_status');   // monthly | yearly
            $table->unsignedInteger('seats')->nullable()->after('billing_period');
            // Set when the owner asks to move to Free at the end of the paid period.
            $table->timestamp('downgrade_scheduled_at')->nullable()->after('seats');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'status']);
            $table->dropUnique(['receipt_number']);
            $table->dropColumn([
                'seats', 'unit_price', 'subtotal', 'tax_amount', 'period_start', 'period_end',
                'receipt_number', 'failure_reason', 'razorpay_refund_id', 'refund_status',
                'refund_amount', 'refund_reason', 'refunded_at', 'refunded_by',
            ]);
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['billing_period', 'seats', 'downgrade_scheduled_at']);
        });
    }
};

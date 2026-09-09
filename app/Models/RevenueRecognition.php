<?php

namespace App\Models;

/** Shared rules for operational revenue (dashboard, tax and profit reports). */
final class RevenueRecognition {
    public static function paymentJoin(): string {
        // Refund amounts are cumulative order snapshots, not additive payment attempts.
        // A pending refund does not erase a collected payment before money is returned.
        return "JOIN (
            SELECT order_id, MAX(refunded_amount) AS refunded_amount,
                MIN(paid_at) AS paid_at
            FROM payments
            WHERE payment_state IN ('paid', 'partially_refunded', 'refunded')
               OR (payment_state = 'refund_pending' AND payment_status IN (1, 2))
            GROUP BY order_id
        ) p ON p.order_id = o.id";
    }

    public static function dateExpression(): string {
        // Completing an already delivered order must not move revenue to another month.
        // created_at is only a fallback for legacy delivered orders without timestamps.
        return 'GREATEST(COALESCE(o.delivered_at, o.completed_at, o.created_at), '
            . 'COALESCE(p.paid_at, o.delivered_at, o.completed_at, o.created_at))';
    }
}

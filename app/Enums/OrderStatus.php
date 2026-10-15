<?php

namespace App\Enums;

/**
 * Unified status constants for Sales, Refunds, and Pre-orders.
 *
 * Audit Item #43 — Standardize statuses across all order-related models
 * so that status tracker UI can render a consistent pipeline.
 */
class OrderStatus
{
    // ── Sale Statuses ────────────────────────────────────────
    const SALE_PENDING           = 'pending';
    const SALE_COMPLETED         = 'completed';
    const SALE_CANCELLED         = 'cancelled';
    const SALE_REFUND_REQUESTED  = 'refund_requested';
    const SALE_REFUNDED          = 'refunded';
    const SALE_EXPIRED           = 'expired';

    // ── Refund Statuses ──────────────────────────────────────
    const REFUND_PENDING   = 'pending';
    const REFUND_APPROVED  = 'approved';
    const REFUND_ONGOING   = 'ongoing';    // New: refund is being processed
    const REFUND_REJECTED  = 'rejected';
    const REFUND_CANCELLED = 'cancelled';  // New: customer cancelled the request

    // ── Pre-order Statuses ───────────────────────────────────
    const PREORDER_PENDING   = 'pending';
    const PREORDER_APPROVED  = 'approved';
    const PREORDER_ONGOING   = 'ongoing';   // New: item is being sourced/ordered
    const PREORDER_ARRIVED   = 'arrived';
    const PREORDER_COMPLETED = 'completed';
    const PREORDER_REJECTED  = 'rejected';
    const PREORDER_CANCELLED = 'cancelled';
    const PREORDER_EXPIRED   = 'expired';

    // ── Refund Methods ───────────────────────────────────────
    const METHOD_DIGITAL = 'digital';
    const METHOD_ON_SITE = 'on_site';

    /**
     * All valid sale statuses for validation rules.
     */
    public static function saleStatuses(): array
    {
        return [
            self::SALE_PENDING,
            self::SALE_COMPLETED,
            self::SALE_CANCELLED,
            self::SALE_REFUND_REQUESTED,
            self::SALE_REFUNDED,
            self::SALE_EXPIRED,
        ];
    }

    /**
     * All valid refund statuses for validation rules.
     */
    public static function refundStatuses(): array
    {
        return [
            self::REFUND_PENDING,
            self::REFUND_APPROVED,
            self::REFUND_ONGOING,
            self::REFUND_REJECTED,
            self::REFUND_CANCELLED,
        ];
    }

    /**
     * All valid pre-order statuses for validation rules.
     */
    public static function preorderStatuses(): array
    {
        return [
            self::PREORDER_PENDING,
            self::PREORDER_APPROVED,
            self::PREORDER_ONGOING,
            self::PREORDER_ARRIVED,
            self::PREORDER_COMPLETED,
            self::PREORDER_REJECTED,
            self::PREORDER_CANCELLED,
            self::PREORDER_EXPIRED,
        ];
    }

    /**
     * All valid refund methods for validation rules.
     */
    public static function refundMethods(): array
    {
        return [
            self::METHOD_DIGITAL,
            self::METHOD_ON_SITE,
        ];
    }

    /**
     * Human-readable label for a status.
     */
    public static function label(string $status): string
    {
        return match ($status) {
            'pending'          => 'Pending',
            'completed'        => 'Completed',
            'cancelled'        => 'Cancelled',
            'refund_requested' => 'Refund Requested',
            'refunded'         => 'Refunded',
            'approved'         => 'Approved',
            'ongoing'          => 'Ongoing',
            'rejected'         => 'Rejected',
            'arrived'          => 'Arrived',
            'expired'          => 'Expired',
            default            => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    /**
     * CSS color class for status badges.
     */
    public static function color(string $status): string
    {
        return match ($status) {
            'pending'          => 'yellow',
            'approved', 'completed', 'arrived' => 'green',
            'ongoing'          => 'blue',
            'refund_requested' => 'orange',
            'rejected', 'cancelled' => 'red',
            'refunded'         => 'purple',
            'expired'          => 'gray',
            default            => 'gray',
        };
    }
}

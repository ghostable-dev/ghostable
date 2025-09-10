<?php

namespace App\Billing\Entities;

use App\Account\Models\User;
use App\Core\Helpers\ArrayExtractor;
use App\Organization\Models\Organization;
use Spatie\LaravelData\Data;
use Stripe\StripeClient;

class StripePayload extends Data
{
    public ?string $type = null;

    public array $object = [];

    public function __construct(
        public array $data
    ) {
        $this->type = $data['type'] ?? null;
        $this->object = $data['data']['object'] ?? [];
    }

    public function organizationFromStripeId(): ?Organization
    {
        if (isset($this->object['customer'])) {
            return Organization::where('stripe_id', $this->object['customer'])->first();
        }

        return null;
    }

    public function causedByUser(): ?User
    {
        if (isset($this->object['metadata']['platform_user_id'])) {
            return User::find($this->object['metadata']['platform_user_id']);
        }

        return null;
    }

    // @codeCoverageIgnoreStart
    public function lineItems(): array
    {
        $stripe = new StripeClient(config('cashier.secret'));

        $details = $stripe->checkout->sessions->retrieve($this->object['id'], [
            'expand' => ['line_items'],
        ]);

        return $details->line_items->data;
    }
    // @codeCoverageIgnoreEnd

    public function debugData(): array
    {
        if (is_null($this->type) || empty($this->object)) {
            return [];
        }

        $details = match ($this->type) {
            'checkout.session.completed' => $this->checkoutSessionCompletedDebugFields(),
            'customer.subscription.created' => $this->customerSubscriptionCreatedDebugFields(),
            'customer.subscription.deleted' => $this->customerSubscriptionDeletedDebugFields(),
            default => is_array($this->data) ? $this->data : [],
        };

        return array_merge(
            ['type' => $this->type],
            ArrayExtractor::extract($this->object, $details)
        );
    }

    protected function checkoutSessionCompletedDebugFields(): array
    {
        return [
            'id' => 'id',
            'status' => 'status',
            'created' => 'created',
            'invoice' => 'invoice',
            'currency' => 'currency',
            'customer' => 'customer',
            'metadata' => 'metadata',
            'expires_at' => 'expires_at',
            'amount_total' => 'amount_total',
            'payment_intent' => 'payment_intent',
            'payment_status' => 'payment_status',
            'amount_subtotal' => 'amount_subtotal',
            'payment_method_configuration_id' => ['payment_method_configuration_details', 'id'],
        ];
    }

    protected function customerSubscriptionCreatedDebugFields(): array
    {
        return [
            'id' => 'id',
            'application' => 'application',
            'application_fee_percent' => 'application_fee_percent',
            'automatic_tax' => 'automatic_tax',
            'billing_cycle_anchor' => 'billing_cycle_anchor',
            'billing_cycle_anchor_config' => 'billing_cycle_anchor_config',
            'billing_thresholds' => 'billing_thresholds',
            'cancel_at' => 'cancel_at',
            'cancel_at_period_end' => 'cancel_at_period_end',
            'canceled_at' => 'canceled_at',
            'cancellation_details' => 'cancellation_details',
            'collection_method' => 'collection_method',
            'created' => 'created',
            'currency' => 'currency',
            'current_period_end' => 'current_period_end',
            'current_period_start' => 'current_period_start',
            'days_until_due' => 'days_until_due',
            'default_payment_method' => 'default_payment_method',
            'default_source' => 'default_source',
            'default_tax_rates' => 'default_tax_rates',
            'description' => 'description',
            'discount' => 'discount',
            'discounts' => 'discounts',
            'ended_at' => 'ended_at',
            'invoice_settings' => 'invoice_settings',
            'items' => 'items',
            'latest_invoice' => 'latest_invoice',
            'livemode' => 'livemode',
            'metadata' => 'metadata',
            'next_pending_invoice_item_invoice' => 'next_pending_invoice_item_invoice',
            'on_behalf_of' => 'on_behalf_of',
            'pause_collection' => 'pause_collection',
            'payment_settings' => 'payment_settings',
            'pending_invoice_item_interval' => 'pending_invoice_item_interval',
            'pending_setup_intent' => 'pending_setup_intent',
            'pending_update' => 'pending_update',
            'plan' => 'plan',
            'quantity' => 'quantity',
            'schedule' => 'schedule',
            'start_date' => 'start_date',
            'status' => 'status',
            'test_clock' => 'test_clock',
            'transfer_data' => 'transfer_data',
            'trial_end' => 'trial_end',
            'trial_settings' => 'trial_settings',
            'trial_start' => 'trial_start',
        ];
    }

    protected function customerSubscriptionDeletedDebugFields(): array
    {
        return [
            'id' => 'id',
            'object' => 'object',
            'application' => 'application',
            'application_fee_percent' => 'application_fee_percent',
            'automatic_tax' => 'automatic_tax',
            'billing_cycle_anchor' => 'billing_cycle_anchor',
            'billing_cycle_anchor_config' => 'billing_cycle_anchor_config',
            'billing_thresholds' => 'billing_thresholds',
            'cancel_at' => 'cancel_at',
            'cancel_at_period_end' => 'cancel_at_period_end',
            'canceled_at' => 'canceled_at',
            'cancellation_details' => 'cancellation_details',
            'collection_method' => 'collection_method',
            'created' => 'created',
            'currency' => 'currency',
            'current_period_end' => 'current_period_end',
            'current_period_start' => 'current_period_start',
            'days_until_due' => 'days_until_due',
            'default_payment_method' => 'default_payment_method',
            'default_source' => 'default_source',
            'default_tax_rates' => 'default_tax_rates',
            'description' => 'description',
            'discount' => 'discount',
            'discounts' => 'discounts',
            'ended_at' => 'ended_at',
            'invoice_settings' => 'invoice_settings',
            'items' => 'items',
            'latest_invoice' => 'latest_invoice',
            'livemode' => 'livemode',
            'metadata' => 'metadata',
            'next_pending_invoice_item_invoice' => 'next_pending_invoice_item_invoice',
            'on_behalf_of' => 'on_behalf_of',
            'pause_collection' => 'pause_collection',
            'payment_settings' => 'payment_settings',
            'pending_invoice_item_interval' => 'pending_invoice_item_interval',
            'pending_setup_intent' => 'pending_setup_intent',
            'pending_update' => 'pending_update',
            'plan' => 'plan',
            'quantity' => 'quantity',
            'schedule' => 'schedule',
            'start_date' => 'start_date',
            'status' => 'status',
            'test_clock' => 'test_clock',
            'transfer_data' => 'transfer_data',
            'trial_end' => 'trial_end',
            'trial_settings' => 'trial_settings',
            'trial_start' => 'trial_start',
        ];
    }
}

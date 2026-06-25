<?php

return [

  // ── General ──────────────────────────────────────────────────────
  'larapay'                => 'Larapay',
  'secure_payment'         => 'Secure Payment',
  'payment_gateway_tester' => 'Payment Gateway Tester',
  'for_dev_only'           => 'For development use only.',
  'sandbox_mode'           => 'Sandbox',
  'live_mode'              => 'Live',
  'close'                  => 'Close',
  'cancel'                 => 'Cancel',
  'submit'                 => 'Submit',
  'refresh'                => 'Refresh',
  'loading'                => 'Loading…',
  'unknown_error'          => 'Unknown error',
  'network_error'          => 'Network error',
  'no_data'                => 'No response data stored',
  'db_updated'             => '✓ DB updated',
  'lang_switch'            => 'العربية',

  // ── Stats ─────────────────────────────────────────────────────────
  'gateways'               => 'Gateways',
  'transactions'           => 'Transactions',
  'default_currency'       => 'Default Currency',
  'successful'             => 'Successful',

  // ── Billing form ──────────────────────────────────────────────────
  'billing_details'        => 'Billing Details',
  'full_name'              => 'Full Name',
  'email'                  => 'Email',
  'phone'                  => 'Phone',
  'address'                => 'Address',
  'city'                   => 'City',
  'country_code'           => 'Country Code',

  // ── Order form ────────────────────────────────────────────────────
  'order_details'          => 'Order Details',
  'amount'                 => 'Amount',
  'currency'               => 'Currency',
  'description'            => 'Description',
  'order_form_hint'        => 'These details are sent to every gateway when you click a test button below.',

  // ── Gateway card ──────────────────────────────────────────────────
  'missing_credentials'    => 'Missing credentials in .env',
  'configured'             => 'Configured',
  'not_configured'         => 'Not configured',

  // ── Transactions table ────────────────────────────────────────────
  'no_transactions'        => 'No transactions yet. Run a test payment above.',
  'gateway'                => 'Gateway',
  'type'                   => 'Type',
  'reference'              => 'Reference',
  'status'                 => 'Status',
  'date'                   => 'Date',
  'actions'                => 'Actions',
  'refunded'               => 'refunded',
  'showing'                => 'Showing',
  'of'                     => 'of',
  'view_response'          => 'Click to view stored gateway response',
  'check_live'             => 'Check live status from gateway',
  'refund_tx'              => 'Refund this transaction',

  // ── Action buttons ────────────────────────────────────────────────
  'check'                  => 'Check',
  'refund'                 => 'Refund',

  // ── Status labels ─────────────────────────────────────────────────
  'status_success'         => 'Success',
  'status_pending'         => 'Pending',
  'status_cancelled'       => 'Cancelled',

  // ── Check modal ───────────────────────────────────────────────────
  'check_tx_status'        => 'Check Transaction Status',
  'querying_gateway'       => 'Querying gateway…',
  'raw_response'           => 'Raw Response',
  'recheck'                => 'Re-check',
  'gateway_status'         => 'Gateway Status',

  // ── Refund modal ──────────────────────────────────────────────────
  'refund_transaction'     => 'Refund Transaction',
  'original_amount'        => 'Original Amount',
  'refund_amount'          => 'Refund Amount',
  'refund_hint'            => 'Full amount = full refund. Less = partial refund.',
  'set_full_amount'        => 'Set Full Amount',
  'submit_refund'          => 'Submit Refund',
  'processing'             => 'Processing…',

  // ── Response viewer modal ─────────────────────────────────────────
  'stored_response'        => 'Stored Gateway Response',
  'transaction_hash'       => 'Transaction #:id',

  // ── Callback routes ───────────────────────────────────────────────
  'callback_routes'        => 'Callback Routes',
  'method'                 => 'Method',
  'url'                    => 'URL',
  'purpose'                => 'Purpose',
  'client_callback_desc'   => 'Customer redirect',
  'server_callback_desc'   => 'Webhook',

  // ── Payment pages ─────────────────────────────────────────────────
  'pay_securely'           => 'Pay :amount :currency securely',
  'redirecting'            => 'Redirecting to Secure Payment',
  'redirect_wait'          => 'Please wait while we redirect you to the payment page.',
  'do_not_close'           => 'Do not close or refresh this page.',
  'pci_notice'             => 'Secured by :gateway — PCI DSS Level 1',
  'test_cards'             => 'Test Cards',
  'test_mode'              => 'Test Mode',
  'back_home'              => 'Back to Home',

  // ── Result page ───────────────────────────────────────────────────
  'payment_successful'     => 'Payment Successful',
  'payment_failed'         => 'Payment Failed',
  'payment_pending'        => 'Payment Pending',
  'payment_processed'      => 'Your payment has been processed successfully.',
  'card_declined'          => 'Your card was declined. Please try a different card.',
  'payment_processing'     => 'Your payment is being processed. We\'ll update you shortly.',
  'transaction_id'         => 'Transaction ID',
  'order_id'               => 'Order ID',

  // ── Error messages ────────────────────────────────────────────────
  'error_missing_mid'      => 'Kashier MID or API key is not configured.',
  'error_missing_creds'    => ':gateway credentials are not configured.',
  'error_amount_required'  => 'Amount and currency are required.',
  'error_no_refrance'      => 'No gateway reference found for this transaction.',

];

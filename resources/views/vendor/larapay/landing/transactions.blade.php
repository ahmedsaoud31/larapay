{{-- Transactions table --}}
<div class="card mb-4">
  <div class="card-header d-flex align-items-center justify-content-between">
    <h3 class="card-title mb-0"><i class="ti ti-receipt-2 me-2"></i>{{ __('larapay::larapay.transactions') }}</h3>
    <a href="{{ route('larapay.landing') }}" class="btn btn-sm btn-outline-secondary">
      <i class="ti ti-refresh me-1"></i>{{ __('larapay::larapay.refresh') }}
    </a>
  </div>

  @if($transactions->isEmpty())
  <div class="card-body text-center text-muted py-5">
    <i class="ti ti-inbox" style="font-size:3rem;opacity:.3"></i>
    <p class="mt-2 mb-0">{{ __('larapay::larapay.no_transactions') }}</p>
  </div>
  @else
  <div class="table-responsive">
    <table class="table table-vcenter card-table">
      <thead>
        <tr>
          <th>#</th>
          <th>{{ __('larapay::larapay.gateway') }}</th>
          <th>{{ __('larapay::larapay.type') }}</th>
          <th>{{ __('larapay::larapay.reference') }}</th>
          <th>{{ __('larapay::larapay.amount') }}</th>
          <th>{{ __('larapay::larapay.status') }}</th>
          <th>{{ __('larapay::larapay.date') }}</th>
          <th>{{ __('larapay::larapay.actions') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach($transactions as $tx)
        @php
          $canRefund = $tx->status === 'success'
                    && in_array($tx->gateway, ['paytabs','payfort','kashier'])
                    && $tx->type !== 'refund';
          $canCheck  = !empty($tx->refrance)
                    && in_array($tx->gateway, ['paytabs','payfort','kashier','paymob']);
        @endphp
        <tr id="tx-row-{{ $tx->id }}">
          {{-- Inline JSON store — read by JS without HTML-encoding issues --}}
          <script type="application/json" id="tx-response-{{ $tx->id }}">{!! $tx->response ?? 'null' !!}</script>

          <td class="text-muted small">{{ $tx->id }}</td>

          <td><span class="badge bg-blue-lt text-capitalize">{{ $tx->gateway }}</span></td>

          <td class="text-capitalize small">{{ $tx->type }}</td>

          <td>
            <code class="small" title="{{ $tx->refrance }}">
              {{ $tx->refrance ? (mb_strlen($tx->refrance) > 22 ? mb_substr($tx->refrance, 0, 22).'…' : $tx->refrance) : '—' }}
            </code>
            @if($tx->uid)
              <div class="text-muted" style="font-size:.65rem">{{ $tx->uid }}</div>
            @endif
          </td>

          <td class="fw-semibold">
            {{ number_format($tx->amount, 2) }}
            <span class="text-muted small">{{ $tx->currency }}</span>
            @if(($tx->refunded_total ?? 0) > 0)
              <div class="text-danger" style="font-size:.7rem">
                -{{ number_format($tx->refunded_total, 2) }} {{ __('larapay::larapay.refunded') }}
              </div>
            @endif
          </td>

          {{-- Clickable status badge — opens the Response Viewer modal --}}
          <td>
            <button type="button"
                    class="badge badge-status-{{ $tx->status }} badge-status-clickable btn-show-response"
                    id="tx-status-{{ $tx->id }}"
                    data-tx-id="{{ $tx->id }}"
                    data-tx-gateway="{{ $tx->gateway }}"
                    data-tx-ref="{{ $tx->refrance }}"
                    data-tx-amount="{{ $tx->amount }} {{ $tx->currency }}"
                    data-tx-status="{{ $tx->status }}"
                    title="{{ __('larapay::larapay.view_response') }}">
              @php
                $statusLabels = [
                  'success'   => __('larapay::larapay.status_success'),
                  'pending'   => __('larapay::larapay.status_pending'),
                  'cancelled' => __('larapay::larapay.status_cancelled'),
                ];
              @endphp
              {{ $statusLabels[$tx->status] ?? ucfirst($tx->status) }}
              <i class="ti ti-eye ms-1" style="font-size:.7rem"></i>
            </button>
          </td>

          <td class="text-muted small text-nowrap">
            {{ $tx->created_at->format('d M Y') }}<br>
            <span style="font-size:.65rem">{{ $tx->created_at->format('H:i') }}</span>
          </td>

          <td>
            <div class="d-flex gap-1 flex-wrap">
              @if($canCheck)
              <button class="btn btn-sm btn-outline-primary btn-check-status"
                      data-tx-id="{{ $tx->id }}"
                      data-tx-ref="{{ $tx->refrance }}"
                      data-tx-gateway="{{ $tx->gateway }}"
                      data-tx-amount="{{ $tx->amount }}"
                      data-tx-currency="{{ $tx->currency }}"
                      title="{{ __('larapay::larapay.check_live') }}">
                <i class="ti ti-search me-1"></i>{{ __('larapay::larapay.check') }}
              </button>
              @endif

              @if($canRefund)
              <button class="btn btn-sm btn-outline-danger btn-refund"
                      data-tx-id="{{ $tx->id }}"
                      data-tx-ref="{{ $tx->refrance }}"
                      data-tx-amount="{{ $tx->amount }}"
                      data-tx-currency="{{ $tx->currency }}"
                      data-tx-gateway="{{ $tx->gateway }}"
                      title="{{ __('larapay::larapay.refund_tx') }}">
                <i class="ti ti-rotate-clockwise-2 me-1"></i>{{ __('larapay::larapay.refund') }}
              </button>
              @endif

              @if(!$canCheck && !$canRefund)
                <span class="text-muted small">—</span>
              @endif
            </div>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  @if($transactions->hasPages())
  <div class="card-footer d-flex align-items-center justify-content-between">
    <p class="m-0 text-muted small">
      {{ __('larapay::larapay.showing') }} {{ $transactions->firstItem() }}–{{ $transactions->lastItem() }}
      {{ __('larapay::larapay.of') }} {{ $transactions->total() }}
    </p>
    {{ $transactions->links('pagination::bootstrap-5') }}
  </div>
  @endif
  @endif
</div>

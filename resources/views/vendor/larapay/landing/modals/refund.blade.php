{{-- REFUND MODAL --}}
<div class="modal modal-blur fade" id="refundModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ti ti-rotate-clockwise-2 me-2"></i>{{ __('larapay::larapay.refund_transaction') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="refundTxId">
        <div class="row g-2 mb-3 small">
          <div class="col-4 text-muted">{{ __('larapay::larapay.gateway') }}</div>
          <div class="col-8 fw-semibold" id="refundGateway">—</div>
          <div class="col-4 text-muted">{{ __('larapay::larapay.reference') }}</div>
          <div class="col-8"><code id="refundRef">—</code></div>
          <div class="col-4 text-muted">{{ __('larapay::larapay.original_amount') }}</div>
          <div class="col-8 fw-semibold" id="refundOriginal">—</div>
        </div>
        <hr class="my-3">
        <div id="refundInputSection">
          <label class="section-title mb-1">{{ __('larapay::larapay.refund_amount') }}</label>
          <div class="input-group mb-2">
            <input type="number" class="form-control" id="refundAmount" min="0.01" step="0.01" placeholder="0.00">
            <span class="input-group-text fw-bold" id="refundCurrencyBadge">—</span>
          </div>
          <div class="form-text mb-3">{{ __('larapay::larapay.refund_hint') }}</div>
          <button type="button" class="btn btn-sm btn-outline-secondary" id="btnFullRefund">
            <i class="ti ti-arrow-back-up me-1"></i>{{ __('larapay::larapay.set_full_amount') }}
          </button>
        </div>
        <div id="refundResult" class="d-none">
          <div class="alert mb-2" id="refundResultAlert">
            <i id="refundResultIcon"></i><span id="refundResultMsg"></span>
          </div>
          <div id="refundRawSection" class="d-none">
            <div class="section-title mb-1">{{ __('larapay::larapay.raw_response') }}</div>
            <pre id="refundRaw" class="small p-3"
                 style="max-height:200px;overflow-y:auto;background:#1e1e2e;color:#cdd6f4;border-radius:6px;line-height:1.6;margin:0"></pre>
          </div>
        </div>
        <div id="refundLoading" class="d-none d-flex align-items-center gap-2 py-2">
          <div class="spinner-border spinner-border-sm text-danger"></div>
          <span class="text-muted">{{ __('larapay::larapay.processing') }}</span>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" id="refundCloseBtn" data-bs-dismiss="modal">
          {{ __('larapay::larapay.cancel') }}
        </button>
        <button type="button" class="btn btn-danger" id="refundSubmitBtn">
          <i class="ti ti-rotate-clockwise-2 me-1"></i>{{ __('larapay::larapay.submit_refund') }}
        </button>
      </div>
    </div>
  </div>
</div>

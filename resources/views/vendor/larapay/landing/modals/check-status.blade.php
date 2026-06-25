{{-- CHECK STATUS MODAL --}}
<div class="modal modal-blur fade" id="checkModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ti ti-search me-2"></i>{{ __('larapay::larapay.check_tx_status') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-2 mb-3 small">
          <div class="col-4 text-muted">{{ __('larapay::larapay.gateway') }}</div>
          <div class="col-8 fw-semibold" id="ckGateway">—</div>
          <div class="col-4 text-muted">{{ __('larapay::larapay.reference') }}</div>
          <div class="col-8"><code id="ckRef">—</code></div>
          <div class="col-4 text-muted">{{ __('larapay::larapay.amount') }}</div>
          <div class="col-8 fw-semibold" id="ckAmount">—</div>
        </div>
        <hr class="my-3">
        <div id="ckLoading" class="d-flex align-items-center gap-2 py-2">
          <div class="spinner-border spinner-border-sm text-primary"></div>
          <span class="text-muted">{{ __('larapay::larapay.querying_gateway') }}</span>
        </div>
        <div id="ckError" class="alert alert-danger d-none">
          <i class="ti ti-alert-circle me-2"></i><span id="ckErrorMsg"></span>
        </div>
        <div id="ckResult" class="d-none">
          <div class="d-flex align-items-center gap-3 mb-3">
            <div class="avatar avatar-md" id="ckStatusIcon"></div>
            <span class="badge fs-6 px-3 py-2" id="ckStatusBadge">—</span>
            <span class="ms-auto small" id="ckUpdatedNote"></span>
          </div>
          <div class="accordion">
            <div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed py-2 small" type="button"
                        data-bs-toggle="collapse" data-bs-target="#ckRawCollapse">
                  <i class="ti ti-code me-2"></i>{{ __('larapay::larapay.raw_response') }}
                </button>
              </h2>
              <div id="ckRawCollapse" class="accordion-collapse collapse">
                <div class="accordion-body p-0">
                  <pre id="ckRaw" class="m-0 p-3 small"
                       style="max-height:280px;overflow-y:auto;background:#1e1e2e;color:#cdd6f4;border-radius:0 0 6px 6px;line-height:1.6"></pre>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary me-auto" data-bs-dismiss="modal">
          {{ __('larapay::larapay.close') }}
        </button>
        <button type="button" class="btn btn-primary d-none" id="ckRetryBtn">
          <i class="ti ti-refresh me-1"></i>{{ __('larapay::larapay.recheck') }}
        </button>
      </div>
    </div>
  </div>
</div>

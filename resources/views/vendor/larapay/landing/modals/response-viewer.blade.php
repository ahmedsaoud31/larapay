{{-- RESPONSE VIEWER MODAL --}}
<div class="modal modal-blur fade" id="responseModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-0"><i class="ti ti-database me-2"></i>{{ __('larapay::larapay.stored_response') }}</h5>
          <div class="text-muted small mt-1" id="rspMeta"></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        {{-- Summary bar --}}
        <div class="px-3 pt-3 pb-2 border-bottom d-flex align-items-center gap-3 flex-wrap">
          <span class="badge" id="rspStatusBadge"></span>
          <span class="text-muted small" id="rspGateway"></span>
          <span class="fw-semibold small" id="rspAmount"></span>
          <code class="small text-muted ms-auto" id="rspRef"></code>
        </div>
        {{-- Pretty JSON --}}
        <pre id="rspRaw" class="m-0 p-4 small"
             style="max-height:460px;overflow-y:auto;background:#1e1e2e;color:#cdd6f4;line-height:1.7;border-radius:0 0 8px 8px"></pre>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          {{ __('larapay::larapay.close') }}
        </button>
      </div>
    </div>
  </div>
</div>

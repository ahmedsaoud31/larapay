/**
 * Larapay Landing Page — JavaScript
 *
 * Organized into focused ES6 classes:
 *   JsonHighlighter  — syntax-highlights a parsed JSON value into HTML
 *   ApiClient        — thin fetch wrapper that handles CSRF + JSON
 *   ResponseViewer   — "Stored Gateway Response" modal
 *   CheckStatus      — "Check Transaction Status" modal + AJAX call
 *   RefundManager    — "Refund Transaction" modal + AJAX call
 *   GatewayForm      — gateway card test-pay form dispatcher
 *   CurrencySync     — keeps the currency display badge in sync
 *   LarapayLanding   — bootstraps everything on window.load
 */

/* ─────────────────────────────────────────────────────────────────────────
   Utility helpers
─────────────────────────────────────────────────────────────────────────── */
const Q = document.querySelector.bind(document);
const Qa = document.querySelectorAll.bind(document);
const show     = (id)    => Q(id) && Q(id).classList.remove('d-none');
const hide     = (id)    => Q(id) && Q(id).classList.add('d-none');
const setText  = (id, v) => Q(id) && (Q(id).textContent = v);
const trigger  = (id)    => Q(id) && Q(id).click();


/* ─────────────────────────────────────────────────────────────────────────
   JsonHighlighter
─────────────────────────────────────────────────────────────────────────── */
class JsonHighlighter {
  /** Colors (Catppuccin Mocha palette) */
  static COLORS = {
    key:     '#89dceb',  // cyan
    string:  '#a6e3a1',  // green
    number:  '#f9e2af',  // yellow
    boolean: '#fab387',  // orange
    null:    '#cba6f7',  // purple
    error:   '#f38ba8',  // red
    empty:   '#6c7086',  // muted
  };

  /**
   * Render data as syntax-highlighted HTML.
   * @param {*} data — anything: string, object, array, null
   * @returns {string} HTML string safe to assign to .innerHTML
   */
  static render(data) {
    // Normalize: try to parse strings as JSON
    if (typeof data === 'string' && data.trim()) {
      try { data = JSON.parse(data); } catch (_) { /* keep as-is */ }
    }

    if (data === null || data === undefined) {
      return `<span style="color:${this.COLORS.null}">null</span>`;
    }

    let json;
    try {
      json = JSON.stringify(data, null, 2);
    } catch (_) {
      return `<span style="color:${this.COLORS.error}">Could not serialize response</span>`;
    }

    if (!json || ['null', '{}', '[]'].includes(json)) {
      return `<span style="color:${this.COLORS.empty};font-style:italic">No response data stored</span>`;
    }

    // Escape HTML entities first
    json = json
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');

    // Single-pass colorizer — regex matches: quoted strings (+colon = key), bool/null, numbers
    return json.replace(
      /("(?:\\u[0-9a-fA-F]{4}|\\[^u]|[^\\"])*")(\s*:)?|(\btrue\b|\bfalse\b|\bnull\b)|(-?\d+(?:\.\d+)?(?:[eE][+\-]?\d+)?)/g,
      (match, strToken, colonAfter, boolNull, num) => {
        if (strToken !== undefined) {
          return colonAfter !== undefined
            ? `<span style="color:${this.COLORS.key}">${strToken}</span>${colonAfter}`
            : `<span style="color:${this.COLORS.string}">${strToken}</span>`;
        }
        if (boolNull !== undefined) {
          const c = boolNull === 'null' ? this.COLORS.null : this.COLORS.boolean;
          return `<span style="color:${c}">${boolNull}</span>`;
        }
        if (num !== undefined) {
          return `<span style="color:${this.COLORS.number}">${num}</span>`;
        }
        return match;
      }
    );
  }

  /** Convenience: render into an element by ID */
  static renderInto(id, data) {
    const target = Q(id);
    if (target) target.innerHTML = this.render(data);
  }
}


/* ─────────────────────────────────────────────────────────────────────────
   ApiClient
─────────────────────────────────────────────────────────────────────────── */
class ApiClient {
  constructor(csrf) {
    this.csrf = csrf;
  }

  post(url, body) {
    return fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': this.csrf,
        'Accept': 'application/json',
      },
      body: JSON.stringify(body),
    }).then(r => r.json());
  }
}


/* ─────────────────────────────────────────────────────────────────────────
   ResponseViewer — reads stored response from inline <script> tag
─────────────────────────────────────────────────────────────────────────── */
class ResponseViewer {
  static STATUS_MAP = {
    success:   { cls: 'badge-status-success',  label: 'Success'   },
    pending:   { cls: 'badge-status-pending',   label: 'Pending'   },
    cancelled: { cls: 'badge-status-cancelled', label: 'Cancelled' },
  };

  constructor() {
    document.addEventListener('click', e => this._onClick(e));
  }

  _onClick(e) {
    const btn = e.target.closest('.btn-show-response');
    if (!btn) return;

    const txId    = btn.getAttribute('data-tx-id');
    const status  = btn.getAttribute('data-tx-status');
    const gateway = btn.getAttribute('data-tx-gateway');
    const ref     = btn.getAttribute('data-tx-ref');
    const amount  = btn.getAttribute('data-tx-amount');

    // Read raw JSON from the <script type="application/json"> tag
    const scriptEl = document.getElementById(`tx-response-${txId}`);
    const rawJson  = scriptEl ? scriptEl.textContent.trim() : null;

    // Populate summary bar
    const sm = ResponseViewer.STATUS_MAP[status] || { cls: 'badge bg-secondary', label: status };
    const badge = Q('#rspStatusBadge');
    badge.className   = `badge ${sm.cls}`;
    badge.textContent = sm.label;

    setText('#rspMeta',    `Transaction #${txId}`);
    setText('#rspGateway', gateway ? gateway.charAt(0).toUpperCase() + gateway.slice(1) : '—');
    setText('#rspAmount',  amount || '—');
    const refEl = Q('#rspRef');
    if (refEl) refEl.textContent = ref || '—';

    // Render highlighted JSON
    JsonHighlighter.renderInto('#rspRaw', rawJson);

    trigger('#responseTrigger');
  }

  /** Update the inline JSON store after a Check Status call returns fresh data */
  static updateStore(txId, freshData) {
    const scriptStore = Q(`#tx-response-${txId}`);
    if (scriptStore && freshData) {
      scriptStore.textContent = JSON.stringify(freshData, null, 2);
    }
  }
}


/* ─────────────────────────────────────────────────────────────────────────
   CheckStatus
─────────────────────────────────────────────────────────────────────────── */
class CheckStatus {
  static STATUS_MAP = {
    success:   { label: 'Success',   cls: 'bg-success text-white', icon: 'ti-circle-check', bg: 'bg-success-lt' },
    pending:   { label: 'Pending',   cls: 'bg-warning text-dark',  icon: 'ti-clock',        bg: 'bg-warning-lt' },
    cancelled: { label: 'Cancelled', cls: 'bg-danger text-white',  icon: 'ti-circle-x',     bg: 'bg-danger-lt'  },
  };

  constructor(api) {
    this.api        = api;
    this.currentId  = null;

    document.addEventListener('click', e => this._onClick(e));
    Q('#ckRetryBtn')?.addEventListener('click', () => this._retry());
  }

  _onClick(e) {
    const btn = e.target.closest('.btn-check-status');
    if (!btn) return;

    this.currentId = btn.getAttribute('data-tx-id');
    setText('#ckGateway', btn.getAttribute('data-tx-gateway'));
    const refEl = Q('#ckRef');
    if (refEl) refEl.textContent = btn.getAttribute('data-tx-ref') || '—';
    setText('#ckAmount',
      `${parseFloat(btn.getAttribute('data-tx-amount')).toFixed(2)} ${btn.getAttribute('data-tx-currency')}`
    );

    this._resetModal();
    trigger('#checkTrigger');
    this._doCheck(this.currentId);
  }

  _retry() {
    if (!this.currentId) return;
    this._resetModal();
    this._doCheck(this.currentId);
  }

  _resetModal() {
    show('#ckLoading');
    hide('#ckError');
    hide('#ckResult');
    Q('#ckRetryBtn')?.classList.add('d-none');
  }

  _doCheck(txId) {
    this.api.post(window.LarapayConfig.checkUrl, { transaction_id: txId })
      .then(data => this._onSuccess(data, txId))
      .catch(err => this._onError(err));
  }

  _onSuccess(data, txId) {
    hide('#ckLoading');
    Q('#ckRetryBtn')?.classList.remove('d-none');

    if (!data.success) {
      show('#ckError');
      setText('#ckErrorMsg', data.error || 'Unknown error');
      return;
    }

    show('#ckResult');
    const s = CheckStatus.STATUS_MAP[data.db_status] || CheckStatus.STATUS_MAP['pending'];

    const badge = Q('#ckStatusBadge');
    if (badge) { badge.className = `badge fs-6 px-3 py-2 ${s.cls}`; badge.textContent = s.label; }

    const icon = Q('#ckStatusIcon');
    if (icon) { icon.className = `avatar avatar-md ${s.bg}`; icon.innerHTML = `<i class="ti ${s.icon}" style="font-size:1.4rem"></i>`; }

    const note = Q('#ckUpdatedNote');
    if (note) { note.textContent = data.updated ? '✓ DB updated' : ''; note.className = data.updated ? 'ms-auto small text-success' : 'ms-auto small text-muted'; }

    JsonHighlighter.renderInto('#ckRaw', data.raw);

    // Update row badge + inline store
    const rowBadge = Q(`#tx-status-${txId}`);
    if (rowBadge) {
      rowBadge.className   = `badge badge-status-${data.db_status} badge-status-clickable btn-show-response`;
      rowBadge.innerHTML   = `${s.label} <i class="ti ti-eye ms-1" style="font-size:.7rem"></i>`;
      rowBadge.setAttribute('data-tx-status', data.db_status);
      ResponseViewer.updateStore(txId, data.raw);
    }
  }

  _onError(err) {
    hide('#ckLoading');
    show('#ckError');
    const t = window.LarapayI18n || {};
    setText('#ckErrorMsg', `${t.network_error || 'Network error'}: ${err.message}`);
    Q('#ckRetryBtn')?.classList.remove('d-none');
  }
}


/* ─────────────────────────────────────────────────────────────────────────
   RefundManager
─────────────────────────────────────────────────────────────────────────── */
class RefundManager {
  constructor(api) {
    this.api       = api;
    this.currentId = null;
    this.maxAmount = 0;

    document.addEventListener('click', e => this._onOpenClick(e));
    Q('#btnFullRefund')?.addEventListener('click', () => this._setFullAmount());
    Q('#refundSubmitBtn')?.addEventListener('click', () => this._submit());
  }

  _onOpenClick(e) {
    const btn = e.target.closest('.btn-refund');
    if (!btn) return;

    this.currentId = btn.getAttribute('data-tx-id');
    this.maxAmount = parseFloat(btn.getAttribute('data-tx-amount'));
    const cur = btn.getAttribute('data-tx-currency');

    Q('#refundTxId').value = this.currentId;
    setText('#refundGateway', btn.getAttribute('data-tx-gateway'));
    const refEl = Q('#refundRef');
    if (refEl) refEl.textContent = btn.getAttribute('data-tx-ref') || '—';
    setText('#refundOriginal', `${this.maxAmount.toFixed(2)} ${cur}`);

    const amtEl = Q('#refundAmount');
    if (amtEl) { amtEl.value = this.maxAmount.toFixed(2); amtEl.max = this.maxAmount; }
    setText('#refundCurrencyBadge', cur);

    hide('#refundResult'); hide('#refundLoading'); show('#refundInputSection');
    Q('#refundSubmitBtn').disabled = false;
    setText('#refundCloseBtn', 'Cancel');

    trigger('#refundTrigger');
  }

  _setFullAmount() {
    const amtEl = Q('#refundAmount');
    if (amtEl) amtEl.value = this.maxAmount.toFixed(2);
  }

  _submit() {
    const amtEl = Q('#refundAmount');
    const amount = parseFloat(amtEl?.value);

    if (!amount || amount <= 0 || amount > this.maxAmount) {
      amtEl?.classList.add('is-invalid');
      return;
    }
    amtEl?.classList.remove('is-invalid');

    hide('#refundInputSection'); hide('#refundResult'); show('#refundLoading');
    Q('#refundSubmitBtn').disabled = true;

    this.api.post(window.LarapayConfig.refundUrl, { transaction_id: this.currentId, amount })
      .then(data => this._onSuccess(data))
      .catch(err  => this._onError(err));
  }

  _onSuccess(data) {
    hide('#refundLoading'); show('#refundResult');
    const alertEl = Q('#refundResultAlert');

    if (data.success) {
      if (alertEl) alertEl.className = 'alert alert-success mb-2';
      const iconEl = Q('#refundResultIcon');
      if (iconEl) iconEl.className = 'ti ti-circle-check me-2';
      setText('#refundResultMsg', data.message);
      JsonHighlighter.renderInto('#refundRaw', data.raw);
      show('#refundRawSection');
      setText('#refundCloseBtn', 'Close');
    } else {
      if (alertEl) alertEl.className = 'alert alert-danger mb-2';
      const iconEl = Q('#refundResultIcon');
      if (iconEl) iconEl.className = 'ti ti-alert-circle me-2';
      setText('#refundResultMsg', data.error || 'Unknown error');
      hide('#refundRawSection'); show('#refundInputSection');
      Q('#refundSubmitBtn').disabled = false;
    }
  }

  _onError(err) {
    hide('#refundLoading'); show('#refundResult');
    const alertEl = Q('#refundResultAlert');
    if (alertEl) alertEl.className = 'alert alert-danger mb-2';
    const iconEl = Q('#refundResultIcon');
    if (iconEl) iconEl.className = 'ti ti-alert-circle me-2';
    setText('#refundResultMsg', `${(window.LarapayI18n || {}).network_error || 'Network error'}: ${err.message}`);
    show('#refundInputSection');
    Q('#refundSubmitBtn').disabled = false;
  }
}


/* ─────────────────────────────────────────────────────────────────────────
   GatewayForm
─────────────────────────────────────────────────────────────────────────── */
class GatewayForm {
  static submit(gateway, action) {
    const gwField = Q('#fieldGateway');
    const actField = Q('#fieldAction');
    if (gwField)  gwField.value  = gateway;
    if (actField) actField.value = action || 'default';
    setTimeout(() => Q('#testPayForm')?.submit(), 20);
  }
}

// Expose globally for inline onclick attributes on gateway buttons
window.submitGateway = (gateway, action) => GatewayForm.submit(gateway, action);


/* ─────────────────────────────────────────────────────────────────────────
   CurrencySync
─────────────────────────────────────────────────────────────────────────── */
class CurrencySync {
  constructor() {
    Q('#cartCurrency')?.addEventListener('input', function() {
      this.value = this.value.toUpperCase();
      setText('#currencyDisplay', this.value || '—');
    });
  }
}


/* ─────────────────────────────────────────────────────────────────────────
   LarapayLanding — entry point
─────────────────────────────────────────────────────────────────────────── */
class LarapayLanding {
  constructor(config) {
    // config = { csrf, checkUrl, refundUrl, locale, i18n }
    window.LarapayConfig = config;
    // Merge i18n into the StatusMap used by CheckStatus and ResponseViewer
    if (config.i18n) {
      CheckStatus.STATUS_MAP.success.label   = config.i18n.success   || CheckStatus.STATUS_MAP.success.label;
      CheckStatus.STATUS_MAP.pending.label   = config.i18n.pending   || CheckStatus.STATUS_MAP.pending.label;
      CheckStatus.STATUS_MAP.cancelled.label = config.i18n.cancelled || CheckStatus.STATUS_MAP.cancelled.label;
      ResponseViewer.STATUS_MAP.success.label   = config.i18n.success   || ResponseViewer.STATUS_MAP.success.label;
      ResponseViewer.STATUS_MAP.pending.label   = config.i18n.pending   || ResponseViewer.STATUS_MAP.pending.label;
      ResponseViewer.STATUS_MAP.cancelled.label = config.i18n.cancelled || ResponseViewer.STATUS_MAP.cancelled.label;
      // Store i18n globally for use in error messages
      window.LarapayI18n = config.i18n;
    }
    this.api = new ApiClient(config.csrf);
  }

  boot() {
    new CurrencySync();
    new ResponseViewer();
    new CheckStatus(this.api);
    new RefundManager(this.api);
  }
}

// Auto-boot on window.load (guarantees Bootstrap is available)
window.addEventListener('load', () => {
  const cfg = window.LarapayBootstrap; // set inline in blade
  if (!cfg) { console.warn('Larapay: window.LarapayBootstrap config not found.'); return; }
  new LarapayLanding(cfg).boot();
});

{{-- Callback routes --}}
<div class="card mb-4">
  <div class="card-header">
    <h3 class="card-title mb-0">
      <i class="ti ti-webhook me-2 text-purple"></i>{{ __('larapay::larapay.callback_routes') }}
    </h3>
  </div>
  <div class="table-responsive">
    <table class="table table-vcenter card-table">
      <thead>
        <tr>
          <th>{{ __('larapay::larapay.gateway') }}</th>
          <th>{{ __('larapay::larapay.method') }}</th>
          <th>{{ __('larapay::larapay.url') }}</th>
          <th>{{ __('larapay::larapay.purpose') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach($gateways as $gw)
        <tr>
          <td><span class="badge bg-blue-lt text-capitalize">{{ $gw['label'] }}</span></td>
          <td><span class="badge bg-muted-lt text-muted">ANY</span></td>
          <td><code>/larapay/{{ $gw['key'] }}/client-callback</code></td>
          <td class="text-muted small">{{ __('larapay::larapay.client_callback_desc') }}</td>
        </tr>
        <tr>
          <td><span class="badge bg-blue-lt text-capitalize">{{ $gw['label'] }}</span></td>
          <td><span class="badge bg-muted-lt text-muted">ANY</span></td>
          <td><code>/larapay/{{ $gw['key'] }}/server-callback</code></td>
          <td class="text-muted small">{{ __('larapay::larapay.server_callback_desc') }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

@php
use Illuminate\Support\Str;
@endphp

@extends('layouts.contentNavbarLayout')

@section('title', __('News Management'))

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
])
@endsection

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <h5 class="card-title mb-0">{{ __('News Management') }}</h5>
          <small class="text-muted">{{ __('Manage your news articles') }}</small>
        </div>
        <div class="d-flex gap-2 align-items-center">
          <div class="country-selector">
            <select class="form-select form-select-sm" style="min-width: 150px;"
                    onchange="window.location.href='{{ route('dashboard.news.index') }}?country=' + this.value">
              <option value="1" {{ $currentCountry == '1' ? 'selected' : '' }}>الأردن</option>
              <option value="2" {{ $currentCountry == '2' ? 'selected' : '' }}>السعودية</option>
              <option value="3" {{ $currentCountry == '3' ? 'selected' : '' }}>مصر</option>
              <option value="4" {{ $currentCountry == '4' ? 'selected' : '' }}>فلسطين</option>
            </select>
          </div>
          <a href="{{ route('dashboard.news.create', ['country' => $currentCountry]) }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i>{{ __('Add News') }}
          </a>
        </div>
      </div>

      <div class="card-body">
        @if(session('success'))
          <div class="alert alert-success alert-dismissible mb-3" role="alert">
            <div class="d-flex gap-2 align-items-center">
              <i class="ti ti-check"></i>
              {{ session('success') }}
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif

        @if(session('error'))
          <div class="alert alert-danger alert-dismissible mb-3" role="alert">
            <div class="d-flex gap-2 align-items-center">
              <i class="ti ti-alert-triangle"></i>
              {{ session('error') }}
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif

        <div class="table-responsive">
          <table class="table table-hover border-top" id="newsTable">
            <thead>
              <tr>
                <th width="80">{{ __('Image') }}</th>
                <th>{{ __('Title') }}</th>
                <th>{{ __('Category') }}</th>
                <th width="100">{{ __('Status') }}</th>
                <th width="100">{{ __('Featured') }}</th>
                <th width="80">{{ __('Views') }}</th>
                <th width="150">{{ __('Created At') }}</th>
                <th width="120">{{ __('Actions') }}</th>
              </tr>
            </thead>
            <tbody class="table-border-bottom-0">
              @forelse($news as $item)
                <tr>
                  <td>
                    <div class="avatar">
                      <img src="{{ Storage::url($item->image) }}"
                           alt="{{ $item->title }}"
                           class="rounded"
                           onerror="this.src='{{ asset('assets/img/illustrations/default_news_image.jpg') }}'">
                    </div>
                  </td>
                  <td>
                    <div class="d-flex flex-column">
                      <span class="fw-semibold">{{ Str::limit($item->title, 50) }}</span>
                      <small class="text-muted">{{ Str::limit($item->meta_description, 60) }}</small>
                    </div>
                  </td>
                  <td>
                    <span class="badge bg-label-primary">{{ $item->category->name }}</span>
                  </td>
                  <td>
                    <div class="form-check form-switch d-flex justify-content-center">
                      <input type="checkbox"
                             class="form-check-input toggle-status"
                             {{ $item->is_active ? 'checked' : '' }}
                             data-id="{{ $item->id }}"
                             data-url="{{ route('dashboard.news.toggle-status', ['news' => $item->id]) }}"
                             style="width: 40px; height: 20px; cursor: pointer;">
                      <label class="form-check-label ms-2" style="cursor: pointer;">
                        <span class="badge {{ $item->is_active ? 'bg-success' : 'bg-secondary' }}">
                          {{ $item->is_active ? __('Active') : __('Inactive') }}
                        </span>
                      </label>
                    </div>
                  </td>
                  <td>
                    <div class="form-check form-switch d-flex justify-content-center">
                      <input type="checkbox"
                             class="form-check-input toggle-featured"
                             {{ $item->is_featured ? 'checked' : '' }}
                             data-id="{{ $item->id }}"
                             data-url="{{ route('dashboard.news.toggle-featured', ['news' => $item->id]) }}"
                             style="width: 40px; height: 20px; cursor: pointer;">
                      <label class="form-check-label ms-2" style="cursor: pointer;">
                        <span class="badge {{ $item->is_featured ? 'bg-warning' : 'bg-secondary' }}">
                          {{ $item->is_featured ? __('Featured') : __('Normal') }}
                        </span>
                      </label>
                    </div>
                  </td>
                  <td>
                    <span class="badge bg-label-info">{{ number_format($item->views) }}</span>
                  </td>
                  <td>
                    <span class="text-muted"><i class="ti ti-calendar me-1"></i>{{ $item->created_at->format('Y-m-d H:i') }}</span>
                  </td>
                  <td>
                    <div class="d-flex gap-2">
                      <a href="{{ route('dashboard.news.edit', ['news' => $item->id, 'country' => $currentCountry]) }}"
                         class="btn btn-icon btn-label-primary btn-sm"
                         data-bs-toggle="tooltip"
                         title="{{ __('Edit') }}">
                        <i class="ti ti-edit"></i>
                      </a>
                      <button type="button"
                              class="btn btn-icon btn-label-danger btn-sm delete-news"
                              data-id="{{ $item->id }}"
                              data-url="{{ route('dashboard.news.destroy', ['news' => $item->id]) }}?country={{ $currentCountry }}"
                              data-bs-toggle="tooltip"
                              title="{{ __('Delete') }}">
                        <i class="ti ti-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" class="text-center py-4">
                    <div class="text-center">
                      <i class="ti ti-news text-secondary mb-2" style="font-size: 3rem;"></i>
                      <p class="mb-0">{{ __('No news found') }}</p>
                      <small class="text-muted">{{ __('Start by adding your first news article') }}</small>
                    </div>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="mt-3">
          {{ $news->appends(Request::except('page'))->links() }}
        </div>
      </div>
    </div>
  </div>
</div>

@push('vendor-script')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
])
@endpush

@push('page-scripts')
<script>
$(document).ready(function() {
  // تفعيل tooltips
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

  // حذف الخبر
  $('.delete-news').on('click', function() {
    const button = $(this);
    const url = button.data('url');

    Swal.fire({
      title: '{{ __("Are you sure?") }}',
      text: '{{ __("You won\'t be able to revert this!") }}',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: '{{ __("Yes, delete it!") }}',
      cancelButtonText: '{{ __("Cancel") }}',
      customClass: {
        confirmButton: 'btn btn-danger me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then(function(result) {
      if (result.isConfirmed) {
        $.ajax({
          url: url,
          type: 'POST',
          data: {
            _token: '{{ csrf_token() }}',
            _method: 'DELETE',
            country: '{{ $currentCountry }}'
          },
          success: function(response) {
            button.closest('tr').remove();
            Swal.fire({
              icon: 'success',
              title: '{{ __("Deleted!") }}',
              text: '{{ __("News has been deleted.") }}',
              customClass: {
                confirmButton: 'btn btn-success'
              }
            });
          },
          error: function(xhr) {
            Swal.fire({
              icon: 'error',
              title: '{{ __("Error!") }}',
              text: '{{ __("Something went wrong.") }}',
              customClass: {
                confirmButton: 'btn btn-primary'
              }
            });
          }
        });
      }
    });
  });

  // تبديل الحالة
  $('.toggle-status').on('change', function() {
    const checkbox = $(this);
    const url = checkbox.data('url');
    const badge = checkbox.siblings('label').find('.badge');

    $.ajax({
      url: url,
      type: 'POST',
      data: {
        _token: '{{ csrf_token() }}',
        _method: 'PATCH',
        country: '{{ $currentCountry }}'
      },
      success: function(response) {
        if (checkbox.is(':checked')) {
          badge.removeClass('bg-secondary').addClass('bg-success');
          badge.text('{{ __("Active") }}');
        } else {
          badge.removeClass('bg-success').addClass('bg-secondary');
          badge.text('{{ __("Inactive") }}');
        }
      },
      error: function(xhr) {
        checkbox.prop('checked', !checkbox.prop('checked'));
        Swal.fire({
          icon: 'error',
          title: '{{ __("Error!") }}',
          text: '{{ __("Failed to update status.") }}',
          customClass: {
            confirmButton: 'btn btn-primary'
          }
        });
      }
    });
  });

  // تبديل التمييز
  $('.toggle-featured').on('change', function() {
    const checkbox = $(this);
    const url = checkbox.data('url');
    const badge = checkbox.siblings('label').find('.badge');

    $.ajax({
      url: url,
      type: 'POST',
      data: {
        _token: '{{ csrf_token() }}',
        _method: 'PATCH',
        country: '{{ $currentCountry }}'
      },
      success: function(response) {
        if (checkbox.is(':checked')) {
          badge.removeClass('bg-secondary').addClass('bg-warning');
          badge.text('{{ __("Featured") }}');
        } else {
          badge.removeClass('bg-warning').addClass('bg-secondary');
          badge.text('{{ __("Normal") }}');
        }
      },
      error: function(xhr) {
        checkbox.prop('checked', !checkbox.prop('checked'));
        Swal.fire({
          icon: 'error',
          title: '{{ __("Error!") }}',
          text: '{{ __("Failed to update featured status.") }}',
          customClass: {
            confirmButton: 'btn btn-primary'
          }
        });
      }
    });
  });
});
</script>
@endpush
@endsection

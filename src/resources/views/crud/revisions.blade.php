@extends(backpack_view('layouts.top_left'))

@php
  $defaultBreadcrumbs = [
    trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
    $crud->entity_name_plural => url($crud->route),
    trans('backpack::crud.preview') => false,
  ];

  // if breadcrumbs aren't defined in the CrudController, use the default breadcrumbs
  $breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;
@endphp

@section('header')
    <section class="container-fluid">
     <h2>
        <span class="text-capitalize">{!! $crud->getHeading() ?? $crud->entity_name_plural !!}</span>
        <small>{!! $crud->getSubheading() ?? mb_ucfirst(trans('backpack::crud.preview')).' '.$crud->entity_name !!}.</small>
        @if ($crud->hasAccess('list'))
          <small><a href="{{ url($crud->route) }}" class="hidden-print font-sm"><i class="fa fa-angle-double-left"></i> {{ trans('backpack::crud.back_to_all') }} <span>{{ $crud->entity_name_plural }}</span></a></small>
        @endif
     </h2>
    </section>
@endsection

@section('content')
<div class="row">
    <div class="{{ $crud->getShowContentClass() }}">

    <!-- Default box -->
      <div class="">
          <div class="card no-padding no-border">
              <table class="table table-striped mb-0">
                  <thead>
                      <td><strong>Name</strong></td>
                      <td><strong>Updated At</strong></td>
                      <td><strong>User</strong></td>
                      <td><strong>Actions</strong></td>
                  </thead>
                  <tbody>
                    @foreach ($crud->versions as $version)
                        <tr>
                            <td>
                                <strong>{{ $version->name }}</strong>
                            </td>
                            <td>
                                <strong>{{ $version->updated_at }}</strong>
                            </td>
                            <td>
                                <strong>{{ $version->user()->name }}</strong>
                            </td>
                            <td>
                                @if ($version->is_published)
                                    Published
                                @else
                                    <a href="/admin/entity/revisions/publish/{{ $version->id }}">Revert</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                  </tbody>
              </table>
          </div><!-- /.box-body -->
      </div><!-- /.box -->

    </div>
</div>
@endsection


@section('after_styles')
    <link rel="stylesheet" href="{{ asset('packages/backpack/crud/css/crud.css') }}">
    <link rel="stylesheet" href="{{ asset('packages/backpack/crud/css/show.css') }}">
@endsection

@section('after_scripts')
    <script src="{{ asset('packages/backpack/crud/js/crud.js') }}"></script>
    <script src="{{ asset('packages/backpack/crud/js/show.js') }}"></script>
@endsection

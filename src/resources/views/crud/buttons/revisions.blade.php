@if ($crud->hasAccess('revisions'))
    <a href="{{ url($crud->route.'/'.$entry->getKey().'/revisions') }}" class="btn btn-sm btn-link"><i class="fa fa-list"></i> Revisions</a>
@endif
@extends('admin')
@section('content')
<div class="row">
    <div class="col">
        <h1 class="display-4">Professors</h1>
    </div>
</div>

<div class="row mt-4">
    <div class="col">
        @if($professors->count() > 0)
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($professors as $professor)
                        <tr>
                            <td>{{ $professor->id }}</td>
                            <td>{{ $professor->name }}</td>
                            <td>{{ $professor->created_at->format('M d, Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="alert alert-info">
                <h4 class="alert-heading">No Professors Found!</h4>
                <p>There are no professors available.</p>
            </div>
        @endif
    </div>
</div>
@endsection

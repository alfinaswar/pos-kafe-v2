@extends('layouts.app')

@section('title', 'Role Detail')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">Roles</a></li>
    <li class="breadcrumb-item active">Detail</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">Role Information: <strong>{{ $role->name }}</strong></h3>
                <div class="card-tools">
                    <a class="btn btn-secondary btn-sm" href="{{ route('roles.index') }}">
                        <i class="fas fa-arrow-left mr-1"></i> Back
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Role Name:</label>
                    <p class="form-control-static font-weight-bold text-primary">{{ $role->name }}</p>
                </div>
                <div class="form-group">
                    <label>Assigned Permissions ({{ $rolePermissions->count() }}):</label>
                    <div>
                        @if(!empty($rolePermissions) && $rolePermissions->count() > 0)
                            @foreach($rolePermissions as $v)
                                <span class="badge badge-info p-2 mr-1 mb-1">{{ $v->name }}</span>
                            @endforeach
                        @else
                            <span class="badge badge-secondary p-2">No Permissions Assigned</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-footer">
                @can('role-edit')
                    <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-edit mr-1"></i> Edit Role
                    </a>
                @endcan
            </div>
        </div>
    </div>
</div>
@endsection

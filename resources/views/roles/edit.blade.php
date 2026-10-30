@extends('layouts.app')

@section('title', 'Edit Role')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">Roles</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title">Edit Role: <strong>{{ $role->name }}</strong></h3>
                <div class="card-tools">
                    <a class="btn btn-secondary btn-sm" href="{{ route('roles.index') }}">
                        <i class="fas fa-arrow-left mr-1"></i> Back
                    </a>
                </div>
            </div>

            {!! Form::model($role, ['method' => 'PATCH', 'route' => ['roles.update', $role->id]]) !!}
            <div class="card-body">
                @if (count($errors) > 0)
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <h5><i class="icon fas fa-ban"></i> Validation Error!</h5>
                        <ul class="mb-0 pl-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                <div class="form-group">
                    <label for="name">Role Name <span class="text-danger">*</span></label>
                    {!! Form::text('name', null, ['id' => 'name', 'placeholder' => 'Enter role name', 'class' => 'form-control ' . ($errors->has('name') ? 'is-invalid' : ''), 'required']) !!}
                </div>

                <div class="form-group">
                    <label class="d-block">Permissions <span class="text-danger">*</span></label>
                    <div class="row">
                        @foreach($permission as $value)
                            <div class="col-md-3 col-sm-6 mb-2">
                                <div class="custom-control custom-checkbox">
                                    {{ Form::checkbox('permission[]', $value->id, in_array($value->id, $rolePermissions) ? true : false, ['class' => 'custom-control-input', 'id' => 'perm-'.$value->id]) }}
                                    <label class="custom-control-label font-weight-normal" for="perm-{{ $value->id }}">
                                        {{ $value->name }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="card-footer text-right">
                <a href="{{ route('roles.index') }}" class="btn btn-default mr-2">Cancel</a>
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-save mr-1"></i> Update Role
                </button>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>
@endsection

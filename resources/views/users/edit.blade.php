@extends('layouts.app')

@section('title', 'Edit User')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title">Edit User: <strong>{{ $user->name }}</strong></h3>
                <div class="card-tools">
                    <a class="btn btn-secondary btn-sm" href="{{ route('users.index') }}">
                        <i class="fas fa-arrow-left mr-1"></i> Back
                    </a>
                </div>
            </div>

            {!! Form::model($user, ['method' => 'PATCH', 'route' => ['users.update', $user->id]]) !!}
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

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Full Name <span class="text-danger">*</span></label>
                            {!! Form::text('name', null, ['id' => 'name', 'placeholder' => 'Enter full name', 'class' => 'form-control ' . ($errors->has('name') ? 'is-invalid' : ''), 'required']) !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="username">Username (Optional)</label>
                            {!! Form::text('username', null, ['id' => 'username', 'placeholder' => 'Enter username', 'class' => 'form-control ' . ($errors->has('username') ? 'is-invalid' : '')]) !!}
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email">Email Address <span class="text-danger">*</span></label>
                            {!! Form::email('email', null, ['id' => 'email', 'placeholder' => 'Enter email', 'class' => 'form-control ' . ($errors->has('email') ? 'is-invalid' : ''), 'required']) !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="outlet_id">Cabang Outlet</label>
                            <select name="outlet_id" id="outlet_id" class="form-control">
                                <option value="">-- Semua Cabang (Pusat) --</option>
                                @foreach($outlets as $out)
                                    <option value="{{ $out->id }}" {{ $user->outlet_id == $out->id ? 'selected' : '' }}>{{ $out->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="password">Password <small class="text-muted">(Leave empty to keep current password)</small></label>
                            {!! Form::password('password', ['id' => 'password', 'placeholder' => 'Enter new password', 'class' => 'form-control ' . ($errors->has('password') ? 'is-invalid' : '')]) !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="confirm-password">Confirm Password</label>
                            {!! Form::password('confirm-password', ['id' => 'confirm-password', 'placeholder' => 'Confirm new password', 'class' => 'form-control ' . ($errors->has('confirm-password') ? 'is-invalid' : '')]) !!}
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="roles">Assign Role <span class="text-danger">*</span></label>
                    {!! Form::select('roles[]', $roles, $userRole, ['id' => 'roles', 'class' => 'form-control select2 ' . ($errors->has('roles') ? 'is-invalid' : ''), 'multiple' => 'multiple', 'required']) !!}
                </div>
            </div>

            <div class="card-footer text-right">
                <a href="{{ route('users.index') }}" class="btn btn-default mr-2">Cancel</a>
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-save mr-1"></i> Update User
                </button>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>
@endsection

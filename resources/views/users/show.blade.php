@extends('layouts.app')

@section('title', 'User Detail')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
    <li class="breadcrumb-item active">Detail</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">User Information</h3>
                <div class="card-tools">
                    <a class="btn btn-secondary btn-sm" href="{{ route('users.index') }}">
                        <i class="fas fa-arrow-left mr-1"></i> Back
                    </a>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th style="width: 30%">Name</th>
                        <td>{{ $user->name }}</td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td>{{ $user->email }}</td>
                    </tr>
                    <tr>
                        <th>Roles</th>
                        <td>
                            @if(!empty($user->getRoleNames()))
                                @foreach($user->getRoleNames() as $v)
                                    <span class="badge badge-success">{{ $v }}</span>
                                @endforeach
                            @else
                                <span class="badge badge-secondary">No Role</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
            <div class="card-footer">
                <a href="{{ route('users.edit', $user->id) }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-edit mr-1"></i> Edit User
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

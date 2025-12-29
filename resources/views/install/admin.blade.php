@extends('install.layout')

@section('content')
<h1 class="install-title">สร้างผู้ดูแลระบบ</h1>
<p class="install-description">
    สร้างบัญชีผู้ดูแลระบบสำหรับจัดการ ThaiVote
</p>

@if($errors->has('user'))
    <div class="alert alert-error">
        {{ $errors->first('user') }}
    </div>
@endif

<form action="{{ route('install.admin.store') }}" method="POST">
    @csrf

    <div class="form-group">
        <label class="form-label">ชื่อผู้ดูแลระบบ</label>
        <input type="text" name="name" class="form-input @error('name') error @enderror"
               value="{{ old('name') }}" placeholder="Admin" required>
        @error('name')
            <div class="form-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label class="form-label">อีเมล</label>
        <input type="email" name="email" class="form-input @error('email') error @enderror"
               value="{{ old('email') }}" placeholder="admin@example.com" required>
        @error('email')
            <div class="form-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-row">
        <div class="form-group">
            <label class="form-label">รหัสผ่าน</label>
            <input type="password" name="password" class="form-input @error('password') error @enderror"
                   placeholder="อย่างน้อย 8 ตัวอักษร" required>
            @error('password')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label">ยืนยันรหัสผ่าน</label>
            <input type="password" name="password_confirmation" class="form-input"
                   placeholder="ยืนยันรหัสผ่าน" required>
        </div>
    </div>

    <div class="install-actions">
        <a href="{{ route('install.application') }}" class="btn btn-secondary">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            ย้อนกลับ
        </a>
        <button type="submit" class="btn btn-primary">
            สร้างผู้ดูแลระบบ
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
    </div>
</form>
@endsection

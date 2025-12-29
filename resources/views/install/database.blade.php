@extends('install.layout')

@section('content')
<h1 class="install-title">ตั้งค่าฐานข้อมูล MySQL</h1>
<p class="install-description">
    กรอกข้อมูลการเชื่อมต่อฐานข้อมูล MySQL ของคุณ ระบบจะสร้างฐานข้อมูลให้อัตโนมัติหากยังไม่มี
</p>

@if($errors->has('db_connection'))
    <div class="alert alert-error">
        {{ $errors->first('db_connection') }}
    </div>
@endif

<form action="{{ route('install.database.store') }}" method="POST">
    @csrf

    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Database Host</label>
            <input type="text" name="db_host" class="form-input @error('db_host') error @enderror"
                   value="{{ old('db_host', $config['host']) }}" placeholder="127.0.0.1">
            @error('db_host')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label">Port</label>
            <input type="number" name="db_port" class="form-input @error('db_port') error @enderror"
                   value="{{ old('db_port', $config['port']) }}" placeholder="3306">
            @error('db_port')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="form-group">
        <label class="form-label">ชื่อฐานข้อมูล</label>
        <input type="text" name="db_database" class="form-input @error('db_database') error @enderror"
               value="{{ old('db_database', $config['database']) }}" placeholder="thaivote">
        <div class="form-hint">หากฐานข้อมูลยังไม่มี ระบบจะสร้างให้อัตโนมัติ</div>
        @error('db_database')
            <div class="form-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Username</label>
            <input type="text" name="db_username" class="form-input @error('db_username') error @enderror"
                   value="{{ old('db_username', $config['username']) }}" placeholder="root">
            @error('db_username')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="db_password" class="form-input @error('db_password') error @enderror"
                   value="{{ old('db_password') }}" placeholder="รหัสผ่าน">
            @error('db_password')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="install-actions">
        <a href="{{ route('install.requirements') }}" class="btn btn-secondary">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            ย้อนกลับ
        </a>
        <button type="submit" class="btn btn-primary">
            ทดสอบและบันทึก
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
    </div>
</form>
@endsection

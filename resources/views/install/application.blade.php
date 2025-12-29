@extends('install.layout')

@section('content')
<h1 class="install-title">ตั้งค่าแอปพลิเคชัน</h1>
<p class="install-description">
    กำหนดการตั้งค่าพื้นฐานสำหรับ ThaiVote
</p>

@if($errors->has('migration'))
    <div class="alert alert-error">
        {{ $errors->first('migration') }}
    </div>
@endif

<form action="{{ route('install.application.store') }}" method="POST">
    @csrf

    <div class="form-group">
        <label class="form-label">ชื่อแอปพลิเคชัน</label>
        <input type="text" name="app_name" class="form-input @error('app_name') error @enderror"
               value="{{ old('app_name', $config['app_name']) }}" placeholder="ThaiVote">
        @error('app_name')
            <div class="form-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label class="form-label">URL ของเว็บไซต์</label>
        <input type="url" name="app_url" class="form-input @error('app_url') error @enderror"
               value="{{ old('app_url', $config['app_url']) }}" placeholder="https://example.com">
        <div class="form-hint">URL ที่จะใช้เข้าถึงเว็บไซต์ (ไม่ต้องใส่ / ต่อท้าย)</div>
        @error('app_url')
            <div class="form-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label class="form-label">Environment</label>
        <select name="app_env" class="form-input @error('app_env') error @enderror">
            <option value="local" {{ old('app_env', $config['app_env']) == 'local' ? 'selected' : '' }}>
                Development (local)
            </option>
            <option value="production" {{ old('app_env', $config['app_env']) == 'production' ? 'selected' : '' }}>
                Production
            </option>
        </select>
        <div class="form-hint">เลือก Production สำหรับใช้งานจริง (ปิด Debug mode)</div>
        @error('app_env')
            <div class="form-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="alert alert-success" style="margin-top: 2rem;">
        <strong>✓ ฐานข้อมูลเชื่อมต่อสำเร็จ</strong><br>
        ระบบจะสร้างตารางฐานข้อมูลในขั้นตอนนี้
    </div>

    <div class="install-actions">
        <a href="{{ route('install.database') }}" class="btn btn-secondary">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            ย้อนกลับ
        </a>
        <button type="submit" class="btn btn-primary">
            บันทึกและสร้างตาราง
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
    </div>
</form>
@endsection

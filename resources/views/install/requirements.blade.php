@extends('install.layout')

@section('content')
<h1 class="install-title">ตรวจสอบความต้องการของระบบ</h1>
<p class="install-description">
    กรุณาตรวจสอบว่าเซิร์ฟเวอร์ของคุณมีความพร้อมสำหรับการติดตั้ง ThaiVote
</p>

<div class="requirements-list">
    @foreach($checks as $key => $check)
        <div class="requirement-item">
            <span class="requirement-name">{{ $check['name'] }}</span>
            <div class="requirement-status {{ $check['passed'] ? 'status-passed' : 'status-failed' }}">
                <span>{{ $check['current'] }}</span>
                <div class="status-icon {{ $check['passed'] ? 'passed' : 'failed' }}">
                    @if($check['passed'])
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    @else
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>

@if(!$allPassed)
    <div class="alert alert-error">
        <strong>⚠️ ไม่ผ่านข้อกำหนดบางข้อ</strong><br>
        กรุณาแก้ไขปัญหาข้างต้นก่อนดำเนินการต่อ
    </div>
@endif

<div class="install-actions">
    <a href="{{ route('install.welcome') }}" class="btn btn-secondary">
        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        ย้อนกลับ
    </a>
    @if($allPassed)
        <a href="{{ route('install.database') }}" class="btn btn-primary">
            ดำเนินการต่อ
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    @else
        <button class="btn btn-primary" disabled style="opacity: 0.5; cursor: not-allowed;">
            ดำเนินการต่อ
        </button>
    @endif
</div>
@endsection

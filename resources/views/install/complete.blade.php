@extends('install.layout')

@section('content')
<div class="complete-icon">
    <svg width="50" height="50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
    </svg>
</div>

<div class="complete-text">
    <h1 class="install-title" style="font-size: 2rem; color: #166534;">ติดตั้งสำเร็จ!</h1>
    <p class="install-description">
        ThaiVote พร้อมใช้งานแล้ว คุณสามารถเริ่มใช้งานระบบได้ทันที
    </p>
</div>

<div class="feature-list">
    <div class="feature-item">
        <div class="feature-icon">🏠</div>
        <div class="feature-content">
            <h4>หน้าแรก</h4>
            <p>ดู Dashboard ผลเลือกตั้งและแผนที่ประเทศไทย</p>
        </div>
    </div>
    <div class="feature-item">
        <div class="feature-icon">⚙️</div>
        <div class="feature-content">
            <h4>แผงควบคุม Admin</h4>
            <p>จัดการข้อมูลการเลือกตั้ง พรรค และผู้สมัคร</p>
        </div>
    </div>
    <div class="feature-item">
        <div class="feature-icon">📊</div>
        <div class="feature-content">
            <h4>API</h4>
            <p>เชื่อมต่อกับแอปพลิเคชันภายนอกผ่าน REST API</p>
        </div>
    </div>
</div>

<div style="background: #fef3c7; border-radius: 0.75rem; padding: 1.5rem; margin-top: 2rem; border: 1px solid #fcd34d;">
    <h4 style="color: #92400e; margin-bottom: 0.5rem;">⚠️ คำแนะนำด้านความปลอดภัย</h4>
    <ul style="color: #b45309; font-size: 0.9rem; margin-left: 1.5rem;">
        <li>ลบโฟลเดอร์ <code>/install</code> หรือปิดการเข้าถึง route</li>
        <li>เปลี่ยน <code>APP_DEBUG=false</code> ใน production</li>
        <li>ตั้งค่า HTTPS สำหรับเว็บไซต์จริง</li>
        <li>สำรองข้อมูลฐานข้อมูลเป็นประจำ</li>
    </ul>
</div>

<div class="install-actions" style="justify-content: center; gap: 1rem;">
    <a href="{{ $appUrl }}" class="btn btn-success" style="padding: 1rem 2.5rem;">
        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
        ไปหน้าแรก
    </a>
    <a href="{{ $appUrl }}/admin" class="btn btn-primary" style="padding: 1rem 2.5rem;">
        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        Admin Panel
    </a>
</div>
@endsection

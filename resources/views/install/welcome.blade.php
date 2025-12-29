@extends('install.layout')

@section('content')
<div class="complete-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">
    🗳️
</div>

<div class="complete-text">
    <h1 class="install-title" style="font-size: 2rem;">ยินดีต้อนรับสู่ ThaiVote</h1>
    <p class="install-description">
        ระบบรายงานผลเลือกตั้งแบบเรียลไทม์ พร้อมแผนที่ประเทศไทย 77 จังหวัด
        และ 400 เขตเลือกตั้ง ตามข้อมูล กกต. 2566
    </p>
</div>

<div class="feature-list">
    <div class="feature-item" style="background: #eff6ff;">
        <div class="feature-icon" style="background: #3b82f6;">📊</div>
        <div class="feature-content">
            <h4 style="color: #1e40af;">ผลเลือกตั้งเรียลไทม์</h4>
            <p style="color: #1d4ed8;">อัปเดตผลคะแนนแบบสด พร้อม WebSocket</p>
        </div>
    </div>
    <div class="feature-item" style="background: #f0fdf4;">
        <div class="feature-icon" style="background: #22c55e;">🗺️</div>
        <div class="feature-content">
            <h4 style="color: #166534;">แผนที่ประเทศไทย</h4>
            <p style="color: #15803d;">แสดงผลรายจังหวัดและรายเขต พร้อมสีพรรค</p>
        </div>
    </div>
    <div class="feature-item" style="background: #fef3c7;">
        <div class="feature-icon" style="background: #f59e0b;">📰</div>
        <div class="feature-content">
            <h4 style="color: #92400e;">ข่าวสาร AI</h4>
            <p style="color: #b45309;">รวบรวมข่าวจากหลายแหล่ง พร้อมวิเคราะห์</p>
        </div>
    </div>
</div>

<div class="install-actions">
    <div></div>
    <a href="{{ route('install.requirements') }}" class="btn btn-primary">
        เริ่มการติดตั้ง
        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>
</div>
@endsection

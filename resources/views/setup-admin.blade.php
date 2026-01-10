<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>สร้างบัญชีแอดมิน - ThaiVote</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans Thai', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #dc2626 0%, #ef4444 50%, #f87171 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .setup-container {
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
        }

        .setup-header {
            background: linear-gradient(135deg, #b91c1c 0%, #dc2626 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .setup-logo {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .setup-logo span {
            color: #fbbf24;
        }

        .setup-subtitle {
            opacity: 0.9;
            font-size: 0.95rem;
        }

        .warning-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            margin-top: 1rem;
            font-size: 0.875rem;
        }

        .warning-badge svg {
            width: 1.25rem;
            height: 1.25rem;
        }

        .setup-content {
            padding: 2rem;
        }

        .setup-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .setup-description {
            color: #64748b;
            margin-bottom: 1.5rem;
            line-height: 1.6;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            font-size: 1rem;
            transition: all 0.2s;
            font-family: inherit;
        }

        .form-input:focus {
            outline: none;
            border-color: #dc2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }

        .form-input.error {
            border-color: #ef4444;
        }

        .form-error {
            color: #ef4444;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.875rem 2rem;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            font-family: inherit;
            width: 100%;
        }

        .btn-danger {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(220, 38, 38, 0.3);
        }

        .alert {
            padding: 1rem 1.25rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .alert-error {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }

        @media (max-width: 640px) {
            body {
                padding: 1rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-header">
            <div class="setup-logo">Thai<span>Vote</span></div>
            <div class="setup-subtitle">ระบบรายงานผลเลือกตั้งแบบเรียลไทม์</div>
            <div class="warning-badge">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                ไม่พบผู้ดูแลระบบ
            </div>
        </div>

        <div class="setup-content">
            <h1 class="setup-title">สร้างบัญชีแอดมินใหญ่</h1>
            <p class="setup-description">
                ระบบไม่พบบัญชีผู้ดูแลระบบ กรุณาสร้างบัญชีแอดมินใหญ่เพื่อใช้งานระบบ
            </p>

            @if($errors->has('user'))
                <div class="alert alert-error">
                    {{ $errors->first('user') }}
                </div>
            @endif

            <form action="{{ url('/setup-admin') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label class="form-label">ชื่อผู้ดูแลระบบ</label>
                    <input type="text" name="name" class="form-input @error('name') error @enderror"
                           value="{{ old('name') }}" placeholder="Admin" required autofocus>
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

                <button type="submit" class="btn btn-danger">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    สร้างบัญชีแอดมินใหญ่
                </button>
            </form>

            <div style="margin-top: 1.5rem; text-align: center; padding-top: 1.5rem; border-top: 1px solid #e2e8f0;">
                <p style="color: #64748b; font-size: 0.875rem;">
                    มีบัญชีอยู่แล้ว?
                    <a href="{{ url('/login') }}" style="color: #dc2626; text-decoration: none; font-weight: 500;">
                        เข้าสู่ระบบ
                    </a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>

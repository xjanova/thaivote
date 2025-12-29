<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'ติดตั้ง ThaiVote' }}</title>

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
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #60a5fa 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .install-container {
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            width: 100%;
            max-width: 700px;
            overflow: hidden;
        }

        .install-header {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .install-logo {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .install-logo span {
            color: #fbbf24;
        }

        .install-subtitle {
            opacity: 0.9;
            font-size: 0.95rem;
        }

        .install-steps {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            padding: 1.5rem 2rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .step {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.85rem;
            color: #64748b;
            background: white;
            border: 2px solid #e2e8f0;
        }

        .step.active {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .step.completed {
            background: #22c55e;
            color: white;
            border-color: #22c55e;
        }

        .step-number {
            width: 1.5rem;
            height: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: currentColor;
            color: white;
            font-weight: 600;
            font-size: 0.75rem;
        }

        .step.active .step-number,
        .step.completed .step-number {
            background: rgba(255,255,255,0.3);
        }

        .install-content {
            padding: 2.5rem;
        }

        .install-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .install-description {
            color: #64748b;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 1.5rem;
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
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-input.error {
            border-color: #ef4444;
        }

        .form-error {
            color: #ef4444;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }

        .form-hint {
            color: #94a3b8;
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
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
        }

        .btn-success {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
        }

        .install-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e2e8f0;
        }

        .requirements-list {
            margin-bottom: 2rem;
        }

        .requirement-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .requirement-name {
            font-weight: 500;
            color: #334155;
        }

        .requirement-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .status-passed {
            color: #22c55e;
        }

        .status-failed {
            color: #ef4444;
        }

        .status-icon {
            width: 1.5rem;
            height: 1.5rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .status-icon.passed {
            background: #dcfce7;
            color: #22c55e;
        }

        .status-icon.failed {
            background: #fee2e2;
            color: #ef4444;
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

        .alert-success {
            background: #dcfce7;
            border: 1px solid #bbf7d0;
            color: #16a34a;
        }

        .complete-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            color: white;
            font-size: 3rem;
        }

        .complete-text {
            text-align: center;
        }

        .feature-list {
            display: grid;
            gap: 1rem;
            margin-top: 2rem;
        }

        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem;
            background: #f0fdf4;
            border-radius: 0.75rem;
        }

        .feature-icon {
            width: 2.5rem;
            height: 2.5rem;
            background: #22c55e;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            flex-shrink: 0;
        }

        .feature-content h4 {
            font-weight: 600;
            color: #166534;
            margin-bottom: 0.25rem;
        }

        .feature-content p {
            color: #15803d;
            font-size: 0.875rem;
        }

        select.form-input {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.25rem;
            padding-right: 3rem;
        }

        @media (max-width: 640px) {
            body {
                padding: 1rem;
            }

            .install-steps {
                flex-wrap: wrap;
            }

            .step-text {
                display: none;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .install-actions {
                flex-direction: column;
                gap: 1rem;
            }

            .install-actions .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <div class="install-logo">Thai<span>Vote</span></div>
            <div class="install-subtitle">ระบบรายงานผลเลือกตั้งแบบเรียลไทม์</div>
        </div>

        @if(isset($step))
        <div class="install-steps">
            @foreach(['ยินดีต้อนรับ', 'ตรวจสอบ', 'ฐานข้อมูล', 'ตั้งค่า', 'ผู้ดูแล', 'เสร็จสิ้น'] as $i => $stepName)
                <div class="step {{ $step > $i + 1 ? 'completed' : ($step == $i + 1 ? 'active' : '') }}">
                    <span class="step-number">{{ $step > $i + 1 ? '✓' : $i + 1 }}</span>
                    <span class="step-text">{{ $stepName }}</span>
                </div>
            @endforeach
        </div>
        @endif

        <div class="install-content">
            @yield('content')
        </div>
    </div>
</body>
</html>

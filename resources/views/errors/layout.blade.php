<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') &mdash; @yield('headline')</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1rem;
            -webkit-font-smoothing: antialiased;
        }
        .card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.06);
            max-width: 30rem;
            width: 100%;
            padding: 2.5rem 2rem;
            text-align: center;
        }
        .code {
            font-size: 5rem;
            font-weight: 800;
            line-height: 1;
            color: #2563eb;
            letter-spacing: -0.025em;
        }
        .headline {
            font-size: 1.25rem;
            font-weight: 600;
            margin-top: 0.5rem;
            color: #0f172a;
        }
        .detail {
            font-size: 0.938rem;
            color: #64748b;
            margin-top: 0.75rem;
            line-height: 1.6;
        }
        .actions {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-block;
            padding: 0.625rem 1.5rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: background .15s, color .15s, border-color .15s;
            border: 1px solid transparent;
        }
        .btn-primary {
            background: #2563eb;
            color: #ffffff;
            border-color: #2563eb;
        }
        .btn-primary:hover { background: #1d4ed8; border-color: #1d4ed8; }
        .btn-secondary {
            background: transparent;
            color: #0f172a;
            border-color: #e2e8f0;
        }
        .btn-secondary:hover { background: #f8fafc; border-color: #cbd5e1; }

        @media (prefers-color-scheme: dark) {
            body { background: #0f172a; color: #f1f5f9; }
            .card { background: #1e293b; border-color: #334155; }
            .headline { color: #f1f5f9; }
            .detail { color: #94a3b8; }
            .btn-secondary { color: #f1f5f9; border-color: #334155; }
            .btn-secondary:hover { background: #334155; border-color: #475569; }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="code">@yield('code')</div>
        <h1 class="headline">@yield('headline')</h1>
        <p class="detail">@yield('detail')</p>
        <div class="actions">
            <a href="{{ url('/dashboard') }}" class="btn btn-primary">Go to dashboard</a>
            <a href="javascript:history.back()" class="btn btn-secondary">Go back</a>
        </div>
    </div>
</body>
</html>

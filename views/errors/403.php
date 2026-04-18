<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>403 Forbidden — ParkSys Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body {
            background: #07090f;
            color: #fff;
            font-family: 'DM Sans', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            overflow: hidden;
        }
        .container { text-align: center; position: relative; z-index: 1; }
        .code {
            font-family: 'Syne', sans-serif;
            font-size: 120px;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 10px;
            background: linear-gradient(180deg, #f04f58 0%, #7c1a1f 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 0 30px rgba(240,79,88,0.3));
        }
        .title { font-family: 'Syne', sans-serif; font-size: 24px; margin-bottom: 16px; }
        .msg { color: #64748b; font-size: 16px; margin-bottom: 32px; max-width: 400px; margin-left: auto; margin-right: auto; }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #4f7cff;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: .2s;
        }
        .btn:hover { background: #3b6aef; transform: translateY(-2px); }
        .grid {
            position: fixed; inset: 0;
            background-image: linear-gradient(rgba(79,124,255,0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(79,124,255,0.05) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="grid"></div>
    <div class="container">
        <div class="code">403</div>
        <div class="title">Access Denied</div>
        <div class="msg">Aba, bawal ka dito tol! System Admin access lang ang pwedeng pumasok sa page na ito.</div>
        <a href="../../index.php" class="btn">Balik sa Safe Zone</a>
    </div>
</body>
</html>

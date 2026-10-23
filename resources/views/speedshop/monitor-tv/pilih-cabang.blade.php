<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pilih Cabang Monitor Antrian</title>
  <style>
    :root {
      --bg: #0b1220;
      --card: #121a2b;
      --line: rgba(255,255,255,.08);
      --text: #f8fafc;
      --muted: #94a3b8;
      --red: #8CFA01;
  --red-ink: #3d6b00;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      min-height: 100vh;
      display: grid;
      place-items: center;
      padding: 24px;
      background: radial-gradient(circle at top, #1e293b 0%, var(--bg) 55%);
      color: var(--text);
      font-family: Inter, system-ui, sans-serif;
    }
    .wrap {
      width: min(720px, 100%);
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: 20px;
      padding: 28px;
      box-shadow: 0 20px 60px rgba(0,0,0,.35);
    }
    h1 { margin: 0 0 8px; font-size: 1.5rem; }
    p { margin: 0 0 22px; color: var(--muted); line-height: 1.5; }
    .list { display: grid; gap: 12px; }
    a.branch {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 16px 18px;
      border-radius: 14px;
      border: 1px solid var(--line);
      background: rgba(255,255,255,.03);
      color: var(--text);
      text-decoration: none;
      transition: .15s ease;
    }
    a.branch:hover {
      border-color: rgba(220,38,38,.55);
      background: rgba(220,38,38,.12);
    }
    a.branch strong { font-size: 1.05rem; }
    a.branch span { color: var(--muted); font-size: .9rem; }
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Pilih Cabang Monitor</h1>
    <p>
      Monitor TV diikat per cabang (bukan per akun kasir).
      Pilih cabang sekali, lalu bookmark URL-nya di layar TV.
      Siapa pun yang login di cabang itu, antrian tetap mengikuti cabang yang dipilih.
    </p>
    <div class="list">
      @foreach($speedshops as $shop)
        <a class="branch" href="{{ route('speedshop.monitor-tv.antrian', ['speedshop_id' => $shop->id]) }}">
          <strong>{{ $shop->nama }}</strong>
          <span>Buka monitor →</span>
        </a>
      @endforeach
    </div>
  </div>
</body>
</html>

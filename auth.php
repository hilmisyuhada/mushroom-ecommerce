<?php
require_once __DIR__ . '/config.php';
$user = currentUser();
$csrfToken = csrfToken();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Akun | Mushroom Organik</title>
    <style>
        :root { --green:#2f6b3f; --dark:#1f4d2e; --cream:#f8f6ed; --line:#dfe5dc; }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; display:grid; place-items:center; padding:24px; background:var(--cream); color:#1f2d22; font-family:Arial,sans-serif; }
        main { width:min(460px,100%); background:#fff; border:1px solid var(--line); border-radius:14px; padding:32px; box-shadow:0 16px 45px rgba(31,77,46,.1); }
        h1 { margin:0 0 8px; color:var(--dark); } p { color:#69736b; }
        label { display:block; margin:16px 0 6px; font-weight:700; font-size:14px; }
        input { width:100%; padding:12px; border:1px solid var(--line); border-radius:8px; font:inherit; }
        button { width:100%; margin-top:22px; padding:13px; border:0; border-radius:8px; background:var(--green); color:#fff; font-weight:700; cursor:pointer; }
        .switch { border:0; background:transparent; color:var(--green); padding:8px; margin-top:10px; }
        .message { min-height:24px; color:#a33; font-size:14px; }
        a { color:var(--green); }
    </style>
</head>
<body>
<main>
    <p><a href="index.php">&larr; Kembali ke toko</a></p>
    <h1 id="title">Masuk ke akun</h1>
    <p id="description">Masuk untuk checkout dan memantau pesanan Anda.</p>
    <form id="accountForm">
        <div id="nameField" hidden><label for="name">Nama</label><input id="name" autocomplete="name"></div>
        <div id="phoneField" hidden><label for="phone">Nomor WhatsApp</label><input id="phone" autocomplete="tel"></div>
        <label for="email">Email</label><input id="email" type="email" required autocomplete="email">
        <label for="password">Password</label><input id="password" type="password" required minlength="8" autocomplete="current-password">
        <p class="message" id="message"></p>
        <button type="submit" id="submit">Masuk</button>
        <button type="button" class="switch" id="switch">Belum punya akun? Buat akun</button>
    </form>
</main>
<script>
let registerMode = false;
const form = document.getElementById('accountForm');
const message = document.getElementById('message');
function renderMode() {
    document.getElementById('title').textContent = registerMode ? 'Buat akun' : 'Masuk ke akun';
    document.getElementById('description').textContent = registerMode ? 'Buat akun untuk checkout dan melihat status pesanan.' : 'Masuk untuk checkout dan memantau pesanan Anda.';
    document.getElementById('nameField').hidden = !registerMode;
    document.getElementById('phoneField').hidden = !registerMode;
    document.getElementById('password').autocomplete = registerMode ? 'new-password' : 'current-password';
    document.getElementById('submit').textContent = registerMode ? 'Buat akun' : 'Masuk';
    document.getElementById('switch').textContent = registerMode ? 'Sudah punya akun? Masuk' : 'Belum punya akun? Buat akun';
}
document.getElementById('switch').addEventListener('click', () => { registerMode = !registerMode; message.textContent = ''; renderMode(); });
form.addEventListener('submit', async event => {
    event.preventDefault();
    message.textContent = '';
    const payload = { name: document.getElementById('name').value, phone: document.getElementById('phone').value, email: document.getElementById('email').value, password: document.getElementById('password').value, expectedRole: 'customer' };
    try {
        const response = await fetch('api/auth.php?action=' + (registerMode ? 'register' : 'login'), { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || 'Aksi akun gagal.');
        window.location.href = result.user.role === 'admin' ? 'admin/index.php' : 'user/index.php';
    } catch (error) { message.textContent = error.message; }
});
renderMode();
</script>
</body>
</html>

<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

if (Auth::check()) {
    header('Location: ' . Auth::getFirstAccessibleUrl());
    exit;
}

$error = '';
$step = 1; // 1: Login, 2: Seleccionar Empresa
$user_data = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';

    if ($action === 'login') {
        $user = trim($_POST['username'] ?? '');
        $pass = $_POST['password']      ?? '';
        
        $user_data = Auth::validate($user, $pass);
        
        if ($user_data) {
            $empresas = $user_data['empresas'] ?? [];
            if (count($empresas) === 0) {
                $error = "Acceso denegado: Sin empresas asignadas.";
            } elseif (count($empresas) === 1) {
                // Login Directo
                Auth::login($user_data, (int)$empresas[0]['id']);
                header('Location: ' . Auth::getFirstAccessibleUrl());
                exit;
            } else {
                // Múltiples Empresas (Mantenemos selector profesional pero simplificado)
                $step = 2;
                $_SESSION['pending_user'] = $user_data;
            }
        } else {
            $error = "Usuario o contraseña inválidos.";
        }
    } elseif ($action === 'select_company') {
        if (isset($_SESSION['pending_user'])) {
            $eid = (int)($_POST['empresa_id'] ?? 0);
            if (Auth::login($_SESSION['pending_user'], $eid)) {
                unset($_SESSION['pending_user']);
                header('Location: ' . Auth::getFirstAccessibleUrl());
                exit;
            }
        }
        $error = "Error de sesión intermitente.";
        $step = 1;
    }
}

if ($step === 2 && isset($_SESSION['pending_user'])) {
    $user_data = $_SESSION['pending_user'];
}

?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-[#020617]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ContaFC – Acceso Administrativo</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%230ea5e9'><path d='M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-2h2v2zm0-4H7v-2h2v2zm0-4H7V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z'/></svg>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: '#0ea5e9'
                    },
                    fontFamily: { sans: ['Inter','sans-serif'] }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body {
            background: 
                radial-gradient(circle at 10% 20%, rgba(14, 165, 233, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(14, 165, 233, 0.05) 0%, transparent 40%),
                #020617;
        }
        .login-card { 
            background: rgba(15, 23, 42, 0.4); 
            backdrop-filter: blur(20px); 
            border: 1px solid rgba(255, 255, 255, 0.05); 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .input-auth { 
            background: rgba(30, 41, 59, 0.3); 
            border: 1px solid rgba(255, 255, 255, 0.05); 
            color: #f8fafc;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .input-auth:focus { 
            background: rgba(30, 41, 59, 0.6); 
            border-color: #0ea5e9; 
            box-shadow: 0 0 20px rgba(14, 165, 233, 0.1);
            outline: none;
        }
        .btn-auth {
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
            transition: all 0.3s;
        }
        .btn-auth:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 25px -5px rgba(14, 165, 233, 0.4);
        }
    </style>
</head>
<body class="h-full flex flex-col items-center justify-center p-6 select-none">

    <div class="mb-12 flex flex-col items-center gap-1 animate-in fade-in slide-in-from-bottom-4 duration-1000">
        <div class="w-14 h-14 bg-brand rounded-2xl flex items-center justify-center shadow-[0_0_30px_rgba(14,165,233,0.3)]">
            <svg class="w-8 h-8 text-slate-950" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-2h2v2zm0-4H7v-2h2v2zm0-4H7V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z"/></svg>
        </div>
        <h1 class="text-3xl font-black text-white tracking-tighter mt-4 italic">Conta<span class="text-brand">FC</span></h1>
        <p class="text-[9px] font-black text-slate-500 uppercase tracking-[0.5em] mt-1 ml-1">Gestión Financiera Central</p>
    </div>

    <div class="w-full max-w-[420px] login-card p-10 rounded-[2.5rem] animate-in zoom-in-95 duration-500">
        
        <?php if ($step === 1): ?>
        <form method="POST" class="space-y-6">
            <input type="hidden" name="action" value="login">
            
            <div class="text-center mb-10">
                <h3 class="text-xl font-bold text-white tracking-tight">Bienvenido</h3>
                <p class="text-xs text-slate-500 font-medium mt-1">Ingrese sus credenciales para continuar</p>
            </div>

            <?php if ($error): ?>
                <div class="p-3 bg-red-500/10 border border-red-500/20 rounded-2xl text-red-500 text-[11px] font-bold text-center animate-pulse">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="space-y-4">
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest pl-4 mb-2">Usuario / RTN</label>
                    <input type="text" name="username" required autofocus placeholder="Usuario"
                           class="w-full h-14 px-6 rounded-2xl input-auth font-bold tracking-tight">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest pl-4 mb-2">Contraseña</label>
                    <div class="relative group">
                        <input type="password" name="password" id="password" required placeholder="••••••••"
                               class="w-full h-14 px-6 rounded-2xl input-auth font-bold tracking-tight pr-12">
                        <button type="button" onclick="togglePassword()" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 hover:text-brand transition-colors p-1">
                            <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.644C3.67 8.5 7.653 6 12 6c4.347 0 8.33 2.5 9.964 5.678.332.644.332 1.288 0 1.932C20.33 15.5 16.347 18 12 18c-4.347 0-8.33-2.5-9.964-5.678z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full h-14 rounded-2xl btn-auth text-white font-black text-[11px] uppercase tracking-[0.2em] shadow-xl">
                IDENTIFICARSE
            </button>
        </form>

        <script>
            function togglePassword() {
                const passwordInput = document.getElementById('password');
                const eyeIcon = document.getElementById('eye-icon');
                
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    eyeIcon.innerHTML = `
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                    `;
                } else {
                    passwordInput.type = 'password';
                    eyeIcon.innerHTML = `
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.644C3.67 8.5 7.653 6 12 6c4.347 0 8.33 2.5 9.964 5.678.332.644.332 1.288 0 1.932C20.33 15.5 16.347 18 12 18c-4.347 0-8.33-2.5-9.964-5.678z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" />
                    `;
                }
            }
        </script>

        <?php else: ?>
        <!-- Paso 2: Selección de Empresa (Casi misma estética para no romper el flujo) -->
        <div class="text-center mb-8">
            <h3 class="text-lg font-bold text-white tracking-tight italic">Portal Multicuentas</h3>
            <p class="text-[10px] text-slate-500 font-black uppercase tracking-widest mt-1">Seleccione Entidad</p>
        </div>
        
        <form method="POST" id="company-form" class="space-y-3">
            <input type="hidden" name="action" value="select_company">
            <input type="hidden" name="empresa_id" id="empresa_id_input">
            
            <div class="grid grid-cols-1 gap-2 max-h-[300px] overflow-auto pr-2 custom-scroll">
                <?php foreach ($user_data['empresas'] as $emp): ?>
                <button type="button" onclick="selectCompany(<?= $emp['id'] ?>)" 
                     class="w-full p-4 bg-white/5 border border-white/5 rounded-2xl flex items-center justify-between group hover:border-brand/50 hover:bg-white/10 transition-all text-left">
                    <span class="text-xs font-black text-slate-300 uppercase group-hover:text-white transition-all"><?= htmlspecialchars($emp['nombre']) ?></span>
                    <svg class="w-4 h-4 text-brand opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/></svg>
                </button>
                <?php endforeach; ?>
            </div>

            <div class="mt-8 pt-6 border-t border-white/5 text-center">
                <a href="login.php" class="text-[9px] font-black text-slate-500 hover:text-rose-500 uppercase tracking-widest transition">Cerrar Sesión</a>
            </div>
        </form>
        <script>
            function selectCompany(id) {
                document.getElementById('empresa_id_input').value = id;
                document.getElementById('company-form').submit();
            }
        </script>
        <?php endif; ?>
    </div>

    <footer class="mt-auto py-10 opacity-20 pointer-events-none">
        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.5em]">ContaFC Honduras &bull; v2.0</p>
    </footer>

</body>
</html>

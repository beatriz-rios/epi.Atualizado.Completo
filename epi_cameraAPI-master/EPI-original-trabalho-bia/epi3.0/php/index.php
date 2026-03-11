<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Login - EPI Guard</title>
    <link rel="stylesheet" href="../css/index.css">
</head>

<body>

    <div class="login-container" id="loginContainer">
        <div class="login-box">
            <h1>EPI Guard</h1>
            <p>Monitoramento de Segurança</p>

            <form method="POST" action="../config/autenticar.php" onsubmit="animarLogin(event)">
                <div class="input-group">
                    <label>Usuário</label>
                    <input type="text" name="usuario" required>
                </div>

                <div class="input-group">
                    <label>Senha</label>
                    <input type="password" name="senha" required>
                </div>

                <button type="submit">Entrar</button>
                <div style="text-align: center; margin-top: 15px;">
                    <a href="cadastro.php" style="color: #666; text-decoration: none; font-size: 14px;">Não tem uma conta? <span style="color: #E30613; font-weight: bold;">Cadastre-se</span></a>
                </div>
                <?php if (isset($_GET['erro'])): ?>
                    <div class="erro">Usuário ou senha inválidos</div>
                <?php
endif; ?>


            </form>
        </div>
    </div>

    <script src="../js/index.js" defer></script>

</body>

</html>
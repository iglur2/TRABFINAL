<?php
session_start();

require_once 'db.php';

function logMessage($message) {
    $logfile = 'registro_log.txt'; // Nome do arquivo de log
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "$timestamp - $message" . PHP_EOL;
    file_put_contents($logfile, $logEntry, FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $confirm_senha = $_POST['confirm_senha'];

    if (!isset($_POST['termos'])) {
        $_SESSION['error'] = "Você deve aceitar os termos de uso para se registrar.";
        header('Location: register.php');
        exit();
    }

    if ($senha !== $confirm_senha) {
        $_SESSION['error'] = "As senhas não coincidem. Por favor, tente novamente.";
        logMessage("Tentativa de registro falhou: senhas não coincidem para o usuário $username.");
        header('Location: register.php');
        exit();
    }

    $stmt = $mysqli->prepare("SELECT * FROM usuarios WHERE user=? OR email=?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result_check_user = $stmt->get_result();

    if ($result_check_user->num_rows > 0) {
        $_SESSION['error'] = "Usuário ou e-mail já existem";
        logMessage("Tentativa de registro falhou: usuário ou e-mail já existe para $username.");
        header('Location: register.php');
        exit();
    }

    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    $stmt = $mysqli->prepare("INSERT INTO usuarios (user, email, senha) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $senha_hash);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Usuário registrado com sucesso!";
        logMessage("Usuário registrado com sucesso: $username.");
        
        if (isset($_POST['autenticacao']) && $_POST['autenticacao'] == 1) {
            $userid = $mysqli->insert_id;
            $codigo_autenticacao = rand(100000, 999999);

            $stmt = $mysqli->prepare("UPDATE usuarios SET autenticacao=1, codigo_autenticacao=? WHERE id=?");
            $stmt->bind_param("ii", $codigo_autenticacao, $userid);
            $stmt->execute();
            logMessage("Autenticação em duas etapas habilitada para o usuário $username com código $codigo_autenticacao.");
            header('Location: login.php');
            exit();
        } else {
            header('Location: login.php');
            exit();
        }
    } else {
        $_SESSION['error'] = "Erro ao registrar o usuário: " . $stmt->error;
        logMessage("Erro ao registrar o usuário $username: " . $stmt->error);
    }
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <style>
        body, html {
            height: 100%;
            width: 100%;
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
        }
        body{
            margin: 0;
            padding: 0;
            height: 100vh;
            background: linear-gradient(#311b92, #000000);
        }
        #container {
            background-color: rgba(0, 0, 0, 0.5);
            width: 20vw;
            padding: 15px;
            padding-left: 30px;
            padding-right: 30px;
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            border-radius: 6px;
            overflow-wrap: break-word;
        }
        input {
            border: none;
            text-decoration: none;
            background-color: rgb(103, 103, 103);
            border-radius: 3px;
            color: white;
            padding: 2px;
            font-size: 14px;
        }
        .txt {
            border: none;
            text-decoration: none;
            background-color: rgb(103, 103, 103);
            border-radius: 3px;
            color: white;
            padding: 2px;
            width: 100%;
        }
        label {
            color: white;
            font-size: 13px;
        }
        ::placeholder {
            color: rgb(191, 191, 191);
        }
        h1 {
            color: white;
        }
        #submit {
            text-decoration: none;
            border: none;
            width: 100%;
            color: rgb(0, 0, 0);
            background-color: white;
            border-radius: 3px;
        }
        #submit:hover {
            background-color: rgb(161, 172, 255);
            cursor: pointer;
        }
        .red {
            font-size: 10px;
            color: red;
        }
        .error-message {
            color: red;
            font-size: 12px;
        }
        #iframe-terms {
            width: 100%;
            height: 200px;
            border: 1px solid #ccc;
            display: none;
        }
        #button {
            width: 100%;
            background-color: #311b92;
            margin-bottom: 20px;
            padding: 10px;
        }
    </style>
</head>
<body>
    <div id="container">
        <h1 id="title">Registro</h1>
        <?php
        if (isset($_SESSION['error'])) {
            echo '<p class="error-message">' . $_SESSION['error'] . '</p>';
            unset($_SESSION['error']);
        }
        ?>
        <form action="register.php" method="post" onsubmit="return validarTermos();">
            <label for="username">Nome de Usuário:</label><br>
            <input type="text" id="username" name="username" required placeholder="Usuario" class="txt"><br><br>
            <label for="email">E-mail:</label><br>
            <input type="email" id="email" name="email" required placeholder="Email" class="txt"><br><br>
            <label for="senha">Senha:</label><br>
            <input type="password" id="senha" name="senha" required placeholder="Sua senha" class="txt"><br><br>
            <label for="confirm_senha">Confirme a Senha:</label><br>
            <input type="password" id="confirm_senha" name="confirm_senha" required placeholder="Confirme a senha" class="txt"><br><br>
            <label>
                <input type="checkbox" name="autenticacao" value="1"> Habilitar Autenticação em Duas Etapas
            </label><br><br>
            <label>
                <input type="checkbox" id="termos" name="termos" value="1"> Li e aceito os <a href="#" onclick="mostrarTermos(); return false;">termos de uso</a>
            </label>
            <iframe id="iframe-terms" src="termos.html"></iframe><br><br>
            <input type="submit" value="Registrar" id="button">
        </form>
    </div>
</body>
<script>
        function verificarSenha() {
            var senha = document.getElementById('senha').value;
            var confirmSenha = document.getElementById('confirm_senha').value;
            var mensagem = document.getElementById('mensagem-senha');
            var forte = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

            if (senha !== confirmSenha) {
                mensagem.className = 'red';
                mensagem.textContent = 'As senhas não coincidem.';
                return false;
            }

            if (forte.test(senha)) {
                mensagem.className = 'green';
                mensagem.textContent = 'Senha forte.';
                return true;
            } else {
                mensagem.className = 'red';
                mensagem.textContent = 'A senha deve ter pelo menos 8 caracteres, incluindo letras maiúsculas, minúsculas, números e caracteres especiais.';
                return false;
            }
        }
        function validarTermos() {
            if (!document.getElementById('termos').checked) {
                alert("Você deve aceitar os termos de uso para se registrar.");
                return false;
            }
            return true;
        }

        function mostrarTermos() {
            var iframe = document.getElementById('iframe-terms');
            iframe.style.display = iframe.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</html>
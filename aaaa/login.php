<?php
session_start();

require_once 'db.php';

function logMessage($message) {
    $logfile = 'login_log.txt'; // Nome do arquivo de log
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "$timestamp - $message" . PHP_EOL;
    file_put_contents($logfile, $logEntry, FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $senha = $_POST['senha'];

    $stmt = $mysqli->prepare("SELECT id, senha, autenticacao FROM usuarios WHERE user=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if (password_verify($senha, $user['senha'])) {
            $_SESSION['userid'] = $user['id'];
            $_SESSION['username'] = $username;
            logMessage("Usuário $username autenticado com sucesso.");

            if ($user['autenticacao'] == 1) {
                header('Location: autenticacao.php');
            } else {
                header('Location: dashboard.php');
            }
            exit();
        } else {
            $_SESSION['error'] = "Senha incorreta";
            logMessage("Falha de login para o usuário $username: senha incorreta.");
        }
    } else {
        $_SESSION['error'] = "Usuário não encontrado.";
        logMessage("Falha de login: usuário $username não encontrado.");
    }
}

$mysqli->close();
?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
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
            width: 100%;
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
        <h1 id="title">Login</h1>
        <?php
        if (isset($_SESSION['error'])) {
            echo '<p class="error-message">' . $_SESSION['error'] . '</p>';
            unset($_SESSION['error']);
        }
        ?>
        <form method="POST" action="login.php">
            <label for="username">Usuario:</label>
            <input type="text" placeholder=" Usuario" required name="username">
            <br> <br>
            <label for="senha">Senha:</label>
            <input type="password" name="senha" required placeholder=" Senha"> 
            <br> <br>
            <input type="submit" name="submit" id="button"></input>
        </form>
    </div>
</body>
</html>
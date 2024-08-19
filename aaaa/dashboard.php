<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['userid'])) {
    header('Location: login.php');
    exit();
}

// Função para realizar o backup do banco de dados
function backupDatabase() {
    require_once 'db.php'; // Inclua o arquivo de conexão com o banco de dados

    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = ''; // Se você tem uma senha, coloque aqui
    $db_name = 'seu_banco_de_dados'; // Substitua pelo nome do seu banco de dados
    $db_port = 3306; // Substitua pela porta correta se não for a padrão

    $backup_file = 'backup_' . date('Ymd_His') . '.sql';
    $command = "mysqldump --opt -h $db_host -P $db_port -u $db_user -p$db_pass $db_name > $backup_file 2>&1";

    // Executa o comando de backup e captura a saída
    $output = [];
    $return_var = null;
    exec($command, $output, $return_var);

    // Verifica se o comando foi executado com sucesso
    if ($return_var === 0 && file_exists($backup_file) && filesize($backup_file) > 0) {
        return $backup_file;
    } else {
        // Captura e retorna a saída de erro
        $error_message = implode("\n", $output);
        $_SESSION['error'] = "Erro ao realizar o backup do banco de dados: $error_message";
        return false;
    }
}

// Processa o backup se o botão for clicado
if (isset($_POST['backup'])) {
    $backup_file = backupDatabase();
    if ($backup_file) {
        $_SESSION['message'] = "Backup realizado com sucesso: <a href='$backup_file'>Baixar Backup</a>";
    } else {
        // A mensagem de erro já está definida na função backupDatabase
    }
}

// Processa o logout se o botão for clicado
if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        background: linear-gradient(#311b92, #000000);
        margin: 0;
        padding: 0;
        height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .container {
        background-color: rgba(0, 0, 0, 0.5);
        color: #fff;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.7);
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 100%;
        max-width: 400px;
        box-sizing: border-box;
    }

    .button {
        display: block;
        width: 100%;
        padding: 10px;
        margin: 10px 0;
        font-size: 16px;
        color: #fff;
        border: none;
        border-radius: 5px;
        text-decoration: none;
        cursor: pointer;
    }

    .button:hover {
        opacity: 0.9;
    }

    .button:not(.logout) {
        background-color: #5e35b1; /* Cor roxa mais clara */
    }

    .button.logout {
        background-color: #dc3545; /* Cor vermelha */
    }

    .message, .error {
        margin-top: 20px;
    }   

    .message a {
        color: #5e35b1;
        text-decoration: none;
    }   

    .message a:hover {
        text-decoration: underline;
    }

    .error {
        color: #dc3545;
    }
    </style>
</head>
<body>
    <div class="container">
        <h1>Dashboard</h1>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message"><?php echo $_SESSION['message']; ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error"><?php echo $_SESSION['error']; ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <form action="dashboard.php" method="post">
            <button type="submit" name="backup" class="button">Backup do Banco de Dados</button>
            <button type="submit" name="logout" class="button logout">Logout</button>
        </form>
    </div>
</body>
</html>

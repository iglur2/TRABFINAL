<?php
session_start();

if (!isset($_SESSION['userid'])) {
    header('Location: login.php');
    exit();
}

require_once 'db.php';

$userid = $_SESSION['userid'];

// Verifica se a autenticação em duas etapas já está habilitada
$sql_check_auth = "SELECT autenticacao, codigo_autenticacao FROM usuarios WHERE id=?";
$stmt_check_auth = $mysqli->prepare($sql_check_auth);
$stmt_check_auth->bind_param("i", $userid);
$stmt_check_auth->execute();
$result_check_auth = $stmt_check_auth->get_result();

if ($result_check_auth->num_rows > 0) {
    $row = $result_check_auth->fetch_assoc();
    if (!$row['autenticacao']) {
        // Se a autenticação em duas etapas não estiver habilitada, redireciona para o dashboard
        $_SESSION['message'] = "Autenticação em duas etapas não está habilitada.";
        header('Location: dashboard.php');
        exit();
    }

    // Se a autenticação em duas etapas estiver habilitada, gera um novo código de autenticação
    $codigo_autenticacao = $row['codigo_autenticacao'];
    if (!$codigo_autenticacao) {
        $codigo_autenticacao = rand(100000, 999999);
        $sql_update = "UPDATE usuarios SET codigo_autenticacao=? WHERE id=?";
        $stmt_update = $mysqli->prepare($sql_update);
        $stmt_update->bind_param("ii", $codigo_autenticacao, $userid);
        $stmt_update->execute();
        $stmt_update->close();
    }
} else {
    $_SESSION['error'] = "Erro ao verificar autenticação em duas etapas.";
    header('Location: dashboard.php');
    exit();
}

$stmt_check_auth->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['codigo'])) {
        $codigo_inserido = $_POST['codigo'];

        if ($codigo_inserido == $codigo_autenticacao) {
            $_SESSION['message'] = "Código correto! Você está autenticado.";
            header('Location: dashboard.php');
            exit();
        } else {
            $_SESSION['error'] = "Código incorreto. Tente novamente.";
        }
    }
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Autenticação em Duas Etapas</title>
    <!-- CSS do modal (pode usar um framework como Bootstrap para facilitar) -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        /* Estilo para centralizar o modal */
        .modal-dialog-centered {
            display: flex;
            align-items: center;
            min-height: calc(100% - 1rem);
        }
        .modal-dialog {
            display: flex;
            align-items: center;
            min-height: calc(100% - 1rem);
        }

        .modal-content {
            background-color: rgba(0, 0, 0, 0.9); 
            color: white; 
            border: none;
        }

        .modal-header {
            border-bottom: 1px solid #444;
        }

        .modal-footer {
            border-top: 1px solid #444; 
        }

        .modal-title {
            color: white; 
        }

        .modal-body {
            color: white; 
        }

        .modal-header .close {
            color: white; 
            opacity: 0.8; 
        }

        .modal-header .close:hover {
            opacity: 1; 
        }

        body, html {
            height: 100%;
            width: 100%;
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
        }

        h2,p {
            color: white;
        }
        body{
            margin: 0;
            padding: 0;
            height: 100vh;
            background: linear-gradient(#311b92, #000000);
        }
        #container {
            background-color: rgba(0, 0, 0, 0.5);
            width: 50vw;
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
        button {
            margin-left: 0;
            width: 100%;
            background-color: #311b92;
            margin-bottom: 20px;
            padding: 10px;
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
        <h2>Autenticação em Duas Etapas</h2>
        <?php if (isset($_SESSION['error'])): ?>
            <p style="color: red;"><?php echo $_SESSION['error']; ?></p>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['message'])): ?>
            <p style="color: green;"><?php echo $_SESSION['message']; ?></p>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        <p>Um código de autenticação foi gerado. Por favor, insira o código abaixo:</p>
        <form action="" method="post">
            <label for="codigo">Código de Autenticação:</label><br>
            <input type="text" id="codigo" name="codigo" required><br><br>
            <input type="submit" value="Verificar Código" id="button">
            <!-- Botão para abrir o modal -->
            <button type="button" class="btn btn-primary ml-3" data-toggle="modal" data-target="#modalCodigo">
                Mostrar Código de Autenticação
            </button>
        </form>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="modalCodigo" tabindex="-1" role="dialog" aria-labelledby="modalCodigoLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCodigoLabel">Código de Autenticação</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>O código de autenticação é: <strong><?php echo $codigo_autenticacao; ?></strong></p>
                    <p>Use este código para completar o processo de autenticação em duas etapas.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript do Bootstrap (necessário para funcionamento do modal) -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
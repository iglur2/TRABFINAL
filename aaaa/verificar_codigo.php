<?php
session_start();

if (!isset($_SESSION['userid'])) {
    header('Location: index.php');
    exit();
}

require_once 'db.php';

$userid = $_SESSION['userid'];
$codigo = $_POST['codigo'];

// Verifica código de autenticação no banco de dados de forma segura com prepared statement
$sql = "SELECT codigo_autenticacao FROM usuarios WHERE id=?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $userid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $codigo_autenticacao_bd = $row['codigo_autenticacao'];

    if ($codigo == $codigo_autenticacao_bd) {
        // Código correto, conclui autenticação em duas etapas de forma segura com prepared statement
        $sql_update = "UPDATE usuarios SET codigo_autenticacao=NULL WHERE id=?";
        $stmt_update = $mysqli->prepare($sql_update);
        $stmt_update->bind_param("i", $userid);

        if ($stmt_update->execute()) {
            $_SESSION['message'] = "Autenticação em duas etapas concluída!";
            header('Location: dashboard.php');
            exit();
        } else {
            $_SESSION['error'] = "Erro ao concluir autenticação em duas etapas: " . $mysqli->error;
        }

        $stmt_update->close();
    } else {
        $_SESSION['error'] = "Código de autenticação incorreto!";
    }
} else {
    $_SESSION['error'] = "Erro ao verificar código de autenticação.";
}

$stmt->close();
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Verificar Código de Autenticação</title>
</head>
<body>
    <h2>Verificar Código de Autenticação</h2>
    <?php if (isset($_SESSION['error'])): ?>
        <p style="color: red;"><?php echo $_SESSION['error']; ?></p>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['message'])): ?>
        <p style="color: green;"><?php echo $_SESSION['message']; ?></p>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>
</body>
</html>

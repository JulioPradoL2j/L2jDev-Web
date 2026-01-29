<?php
$erroOriginal = isset($erroConexao) ? $erroConexao : "Erro desconhecido";

// Detecta problemas comuns
function analisarErro($msg) {

    if (strpos($msg, "Access denied for user") !== false) {
        return [
            "titulo" => "Usuário ou Senha Incorretos",
            "descricao" => "O servidor MySQL recusou o acesso. Verifique se o usuário e senha estão corretos."
        ];
    }

    if (strpos($msg, "Unknown database") !== false) {
        return [
            "titulo" => "Banco de Dados Não Encontrado",
            "descricao" => "O banco de dados informado não existe no servidor."
        ];
    }

    if (strpos($msg, "getaddrinfo failed") !== false ||
        strpos($msg, "No such file or directory") !== false ||
        strpos($msg, "Connection refused") !== false) {

        return [
            "titulo" => "Servidor de Banco de Dados Inacessível",
            "descricao" => "O host ou IP informado está incorreto ou o servidor MySQL está offline."
        ];
    }

    if (strpos($msg, "1049") !== false) {
        return [
            "titulo" => "Banco de Dados Desconhecido",
            "descricao" => "O nome do banco de dados informado não existe."
        ];
    }

    if (strpos($msg, "2002") !== false) {
        return [
            "titulo" => "MySQL Offline ou Host Inválido",
            "descricao" => "Não foi possível se conectar ao servidor. Verifique se ele está ativo."
        ];
    }

    return [
        "titulo" => "Erro de Conexão ao Banco",
        "descricao" => "Ocorreu um erro inesperado ao tentar se conectar ao banco de dados."
    ];
}

$erroInfo = analisarErro($erroOriginal);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title><?= $erroInfo['titulo']; ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
    body {
        margin: 0;
        padding: 0;
        background: #0d1117;
        color: #fff;
        font-family: Arial, Helvetica, sans-serif;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }

    .container {
        text-align: center;
        max-width: 650px;
        padding: 20px;
        animation: fadeIn 1s ease-in-out;
    }

    h1 {
        font-size: 2.8rem;
        font-weight: 700;
        margin-bottom: 10px;
        color: #ff4f4f;
    }

    p {
        font-size: 1.2rem;
        margin-bottom: 15px;
        opacity: 0.85;
        line-height: 1.6;
    }

    .code {
        background: #161b22;
        border-left: 4px solid #ff4f4f;
        padding: 15px;
        font-family: "Courier New", monospace;
        word-wrap: break-word;
        margin: 20px 0;
    }

    button {
        background: #ff4f4f;
        border: none;
        padding: 12px 25px;
        color: #fff;
        font-size: 1rem;
        border-radius: 6px;
        cursor: pointer;
        transition: 0.3s;
    }

    button:hover {
        background: #d63c3c;
    }

    /* gráfico */
    .loader {
        margin: 30px auto;
        width: 120px;
        height: 60px;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
    }

    .bar {
        width: 18px;
        background: #ff4f4f;
        animation: pulse 0.8s infinite ease-in-out;
    }

    .bar:nth-child(2) { animation-delay: 0.2s; }
    .bar:nth-child(3) { animation-delay: 0.4s; }
    .bar:nth-child(4) { animation-delay: 0.6s; }

    @keyframes pulse {
        0% { height: 10px; opacity: 0.4; }
        50% { height: 55px; opacity: 1; }
        100% { height: 10px; opacity: 0.4; }
    }

    @keyframes fadeIn {
        0% { opacity: 0; transform: scale(0.95); }
        100% { opacity: 1; transform: scale(1); }
    }
</style>

</head>
<body>

<div class="container">

    <h1><?= $erroInfo['titulo']; ?></h1>
    <p><?= $erroInfo['descricao']; ?></p>

    <div class="loader">
        <div class="bar"></div>
        <div class="bar"></div>
        <div class="bar"></div>
        <div class="bar"></div>
    </div>

    <div class="code">
        <strong>Erro técnico:</strong><br>
        <?= htmlspecialchars($erroOriginal); ?>
    </div>

    <button onclick="location.reload();">🔄 Tentar Novamente</button>

    <p style="margin-top:20px; font-size:0.9rem; opacity:0.6;">
        Código: <strong>DB-CON-404</strong><br>
        Sistema: <strong>MySQL</strong><br>
        Status: <strong>Falha na Conexão</strong>
    </p>

</div>

</body>
</html>

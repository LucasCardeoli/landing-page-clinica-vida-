<?php
/**
 * includes/cabecalho.php
 * Topo das páginas administrativas. Antes de incluir, defina $titulo.
 * Ex.: $titulo = 'Médicos'; require __DIR__ . '/../includes/cabecalho.php';
 */
$titulo = $titulo ?? 'Painel';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?> | Clínica Vida+ Admin</title>
    <!-- ?v=data do arquivo: evita o navegador usar CSS antigo do cache -->
    <link rel="stylesheet" href="<?= url('css/style.css') ?>?v=<?= filemtime(__DIR__ . '/../css/style.css') ?>">
    <link rel="stylesheet" href="<?= url('css/admin.css') ?>?v=<?= filemtime(__DIR__ . '/../css/admin.css') ?>">
</head>
<body class="admin">
    <header class="admin-header">
        <div class="admin-header-conteudo">
            <a href="<?= url('admin/index.php') ?>" class="admin-marca">
                <img class="logo" src="<?= url('src/logo.png') ?>" alt="Logotipo Clínica Vida+">
                <span>Área administrativa</span>
            </a>
            <nav class="admin-menu">
                <a href="<?= url('admin/index.php') ?>">Painel</a>
                <a href="<?= url('medicos/listar.php') ?>">Médicos</a>
                <a href="<?= url('pacientes/listar.php') ?>">Pacientes</a>
                <a href="<?= url('agendamentos/listar.php') ?>">Agendamentos</a>
                <a href="<?= url('index.php') ?>" class="admin-menu-site">Ver site</a>
            </nav>
        </div>
    </header>

    <main class="admin-conteudo">
        <h1><?= e($titulo) ?></h1>
        <?php exibirMensagem(); ?>

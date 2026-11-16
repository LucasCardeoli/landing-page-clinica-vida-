<?php
/**
 * admin/index.php
 * Painel inicial da área administrativa: mostra quantos registros há em cada tabela.
 * Também serve para testar se a conexão com o banco está funcionando.
 */
require_once __DIR__ . '/../includes/funcoes.php';

$pdo = conectar();

// Consultas sem dado externo (nada vem do usuário), por isso query() simples
$totalMedicos      = $pdo->query('SELECT COUNT(*) FROM medicos')->fetchColumn();
$totalPacientes    = $pdo->query('SELECT COUNT(*) FROM pacientes')->fetchColumn();
$totalAgendamentos = $pdo->query("SELECT COUNT(*) FROM agendamentos
                                  WHERE status IN ('agendado','confirmado') AND data_hora >= NOW()")->fetchColumn();

$titulo = 'Painel';
require __DIR__ . '/../includes/cabecalho.php';
?>

<div class="painel-cards">
    <a class="painel-card" href="<?= url('medicos/listar.php') ?>">
        <strong><?= e($totalMedicos) ?></strong> Médicos cadastrados
    </a>
    <a class="painel-card" href="<?= url('pacientes/listar.php') ?>">
        <strong><?= e($totalPacientes) ?></strong> Pacientes cadastrados
    </a>
    <a class="painel-card" href="<?= url('agendamentos/listar.php') ?>">
        <strong><?= e($totalAgendamentos) ?></strong> Consultas futuras ativas
    </a>
</div>

<?php require __DIR__ . '/../includes/rodape.php'; ?>

<?php
/**
 * agendamentos/listar.php
 * Lista os agendamentos com filtros opcionais: médico, data e status.
 */
require_once __DIR__ . '/../includes/funcoes.php';

$pdo = conectar();

// Filtros vindos da URL (?medico_id=1&data=2026-11-18&status=agendado)
$filtroMedico = (int) ($_GET['medico_id'] ?? 0);
$filtroData   = trim((string) ($_GET['data'] ?? ''));
$filtroStatus = trim((string) ($_GET['status'] ?? ''));

// Ignora filtros com valor inválido em vez de quebrar a consulta
if ($filtroData !== '' && !validarData($filtroData)) {
    $filtroData = '';
}
if (!array_key_exists($filtroStatus, listaStatus())) {
    $filtroStatus = '';
}

// Monta o WHERE aos poucos, sempre com parâmetros (nada concatenado do usuário)
$condicoes = [];
$parametros = [];

if ($filtroMedico > 0) {
    $condicoes[] = 'a.medico_id = :medico_id';
    $parametros[':medico_id'] = $filtroMedico;
}
if ($filtroData !== '') {
    $condicoes[] = 'DATE(a.data_hora) = :data';
    $parametros[':data'] = $filtroData;
}
if ($filtroStatus !== '') {
    $condicoes[] = 'a.status = :status';
    $parametros[':status'] = $filtroStatus;
}

$sql = 'SELECT a.*, m.nome AS medico_nome, p.nome AS paciente_nome, p.telefone AS paciente_telefone
        FROM agendamentos a
        INNER JOIN medicos m ON m.id = a.medico_id
        INNER JOIN pacientes p ON p.id = a.paciente_id';
if ($condicoes) {
    $sql .= ' WHERE ' . implode(' AND ', $condicoes);
}
$sql .= ' ORDER BY a.data_hora DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$agendamentos = $stmt->fetchAll();

// Lista de médicos para o filtro
$medicos = $pdo->query('SELECT id, nome FROM medicos ORDER BY nome')->fetchAll();

$temFiltro = $filtroMedico > 0 || $filtroData !== '' || $filtroStatus !== '';

$titulo = 'Agendamentos';
require __DIR__ . '/../includes/cabecalho.php';
?>

<div class="barra-acoes">
    <a href="<?= url('agendamentos/cadastrar.php') ?>" class="botao">+ Novo agendamento</a>
</div>

<form method="get" class="form-filtros">
    <div class="form-grupo">
        <label for="medico_id">Médico</label>
        <select id="medico_id" name="medico_id">
            <option value="">Todos</option>
            <?php foreach ($medicos as $m): ?>
                <option value="<?= (int) $m['id'] ?>" <?= $filtroMedico === (int) $m['id'] ? 'selected' : '' ?>>
                    <?= e($m['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-grupo">
        <label for="data">Data</label>
        <input type="date" id="data" name="data" value="<?= e($filtroData) ?>">
    </div>

    <div class="form-grupo">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="">Todos</option>
            <?php foreach (listaStatus() as $valor => $texto): ?>
                <option value="<?= e($valor) ?>" <?= $filtroStatus === $valor ? 'selected' : '' ?>><?= e($texto) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-filtros-botoes">
        <button type="submit" class="botao">Filtrar</button>
        <?php if ($temFiltro): ?>
            <a href="<?= url('agendamentos/listar.php') ?>" class="botao botao-secundario">Limpar</a>
        <?php endif; ?>
    </div>
</form>

<?php if (!$agendamentos): ?>
    <div class="alerta alerta-aviso">
        <?= $temFiltro ? 'Nenhum agendamento encontrado com esses filtros.' : 'Nenhum agendamento cadastrado ainda.' ?>
    </div>
<?php else: ?>
    <div class="tabela-container">
        <table class="tabela">
            <thead>
                <tr>
                    <th>Data/hora</th>
                    <th>Médico</th>
                    <th>Paciente</th>
                    <th>Telefone</th>
                    <th>Status</th>
                    <th>Observação</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($agendamentos as $a): ?>
                    <tr>
                        <td><?= e(formatarDataHora($a['data_hora'])) ?></td>
                        <td><?= e($a['medico_nome']) ?></td>
                        <td><?= e($a['paciente_nome']) ?></td>
                        <td><?= e(formatarTelefone($a['paciente_telefone'])) ?></td>
                        <td><span class="etiqueta status-<?= e($a['status']) ?>"><?= e(listaStatus()[$a['status']]) ?></span></td>
                        <td class="observacao"><?= e($a['observacao'] ?? '') ?></td>
                        <td class="acoes">
                            <a href="<?= url('agendamentos/editar.php?id=' . (int) $a['id']) ?>" class="botao botao-pequeno">Editar</a>
                            <a href="<?= url('agendamentos/excluir.php?id=' . (int) $a['id']) ?>" class="botao botao-pequeno botao-perigo">Excluir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="contagem"><?= count($agendamentos) ?> agendamento(s) encontrado(s).</p>
<?php endif; ?>

<?php require __DIR__ . '/../includes/rodape.php'; ?>

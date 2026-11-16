<?php
/**
 * pacientes/listar.php
 * Lista os pacientes, com busca por nome ou CPF (?busca=...).
 */
require_once __DIR__ . '/../includes/funcoes.php';

$busca = trim((string) ($_GET['busca'] ?? ''));

$sql = 'SELECT p.*, COUNT(a.id) AS total_agendamentos
        FROM pacientes p
        LEFT JOIN agendamentos a ON a.paciente_id = p.id';
$parametros = [];

if ($busca !== '') {
    // Busca por parte do nome OU parte do CPF (o CPF é guardado só com dígitos)
    $sql .= ' WHERE p.nome LIKE :nome';
    $parametros[':nome'] = '%' . $busca . '%';

    $digitos = somenteDigitos($busca);
    if ($digitos !== '') {
        $sql .= ' OR p.cpf LIKE :cpf';
        $parametros[':cpf'] = '%' . $digitos . '%';
    }
}

$sql .= ' GROUP BY p.id ORDER BY p.nome';

// Prepared statement: o termo da busca nunca é colado direto no SQL
$stmt = conectar()->prepare($sql);
$stmt->execute($parametros);
$pacientes = $stmt->fetchAll();

$titulo = 'Pacientes';
require __DIR__ . '/../includes/cabecalho.php';
?>

<div class="barra-acoes">
    <a href="<?= url('pacientes/cadastrar.php') ?>" class="botao">+ Cadastrar paciente</a>

    <form method="get" class="form-busca">
        <input type="search" name="busca" value="<?= e($busca) ?>" placeholder="Buscar por nome ou CPF" maxlength="100">
        <button type="submit" class="botao botao-secundario">Buscar</button>
        <?php if ($busca !== ''): ?>
            <a href="<?= url('pacientes/listar.php') ?>" class="botao botao-secundario">Limpar</a>
        <?php endif; ?>
    </form>
</div>

<?php if (!$pacientes): ?>
    <div class="alerta alerta-aviso">
        <?= $busca !== '' ? 'Nenhum paciente encontrado para "' . e($busca) . '".' : 'Nenhum paciente cadastrado ainda.' ?>
    </div>
<?php else: ?>
    <div class="tabela-container">
        <table class="tabela">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>CPF</th>
                    <th>Telefone</th>
                    <th>E-mail</th>
                    <th>Nascimento</th>
                    <th>Agendamentos</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pacientes as $p): ?>
                    <tr>
                        <td><?= e($p['nome']) ?></td>
                        <td><?= e(formatarCpf($p['cpf'])) ?></td>
                        <td><?= e(formatarTelefone($p['telefone'])) ?></td>
                        <td><?= e($p['email']) ?></td>
                        <td><?= e(formatarData($p['data_nascimento'])) ?></td>
                        <td><?= e($p['total_agendamentos']) ?></td>
                        <td class="acoes">
                            <a href="<?= url('pacientes/editar.php?id=' . (int) $p['id']) ?>" class="botao botao-pequeno">Editar</a>
                            <a href="<?= url('pacientes/excluir.php?id=' . (int) $p['id']) ?>" class="botao botao-pequeno botao-perigo">Excluir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="contagem"><?= count($pacientes) ?> paciente(s) encontrado(s).</p>
<?php endif; ?>

<?php require __DIR__ . '/../includes/rodape.php'; ?>

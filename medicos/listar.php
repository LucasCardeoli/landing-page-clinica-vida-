<?php
/**
 * medicos/listar.php
 * Lista todos os médicos com a quantidade de agendamentos de cada um.
 */
require_once __DIR__ . '/../includes/funcoes.php';

// LEFT JOIN + COUNT: traz o médico mesmo que ele não tenha agendamentos
$sql = 'SELECT m.*, COUNT(a.id) AS total_agendamentos
        FROM medicos m
        LEFT JOIN agendamentos a ON a.medico_id = m.id
        GROUP BY m.id
        ORDER BY m.nome';
$medicos = conectar()->query($sql)->fetchAll();

$titulo = 'Médicos';
require __DIR__ . '/../includes/cabecalho.php';
?>

<div class="barra-acoes">
    <a href="<?= url('medicos/cadastrar.php') ?>" class="botao">+ Cadastrar médico</a>
</div>

<?php if (!$medicos): ?>
    <div class="alerta alerta-aviso">Nenhum médico cadastrado ainda.</div>
<?php else: ?>
    <div class="tabela-container">
        <table class="tabela">
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Nome</th>
                    <th>Especialidade</th>
                    <th>CRM</th>
                    <th>Situação</th>
                    <th>Agendamentos</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($medicos as $m): ?>
                    <tr>
                        <td>
                            <?php if ($m['foto']): ?>
                                <img class="miniatura" src="<?= url(e($m['foto'])) ?>" alt="Foto de <?= e($m['nome']) ?>">
                            <?php else: ?>
                                <span class="miniatura miniatura-vazia">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($m['nome']) ?></td>
                        <td><?= e($m['especialidade']) ?></td>
                        <td><?= e($m['crm']) ?></td>
                        <td>
                            <?php if ($m['ativo']): ?>
                                <span class="etiqueta etiqueta-ativo">Ativo</span>
                            <?php else: ?>
                                <span class="etiqueta etiqueta-inativo">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($m['total_agendamentos']) ?></td>
                        <td class="acoes">
                            <a href="<?= url('medicos/editar.php?id=' . (int) $m['id']) ?>" class="botao botao-pequeno">Editar</a>
                            <a href="<?= url('medicos/excluir.php?id=' . (int) $m['id']) ?>" class="botao botao-pequeno botao-perigo">Excluir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/rodape.php'; ?>

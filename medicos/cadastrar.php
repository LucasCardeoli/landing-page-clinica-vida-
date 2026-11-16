<?php
/**
 * medicos/cadastrar.php
 * GET: mostra o formulário vazio. POST: valida e grava o novo médico.
 */
require_once __DIR__ . '/../includes/funcoes.php';

$erros = [];
// Valores iniciais do formulário (vazio, ativo marcado)
$medico = ['nome' => '', 'especialidade' => '', 'crm' => '', 'foto' => null, 'ativo' => 1];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1) Lê os dados enviados
    $medico['nome']          = campo('nome');
    $medico['especialidade'] = campo('especialidade');
    $medico['crm']           = normalizarCrm(campo('crm'));
    $medico['ativo']         = isset($_POST['ativo']) ? 1 : 0;

    // 2) Valida no servidor (mesmo que o HTML também valide)
    $erros = validarMedico($medico);

    // 3) Só processa a foto se o resto estiver certo (evita salvar arquivo à toa)
    if (!$erros) {
        $upload = salvarFotoMedico();
        if ($upload['erro']) {
            $erros[] = $upload['erro'];
        }
        $medico['foto'] = $upload['caminho'];
    }

    // 4) Grava com prepared statement
    if (!$erros) {
        try {
            $sql = 'INSERT INTO medicos (nome, especialidade, crm, foto, ativo)
                    VALUES (:nome, :especialidade, :crm, :foto, :ativo)';
            $stmt = conectar()->prepare($sql);
            $stmt->execute([
                ':nome'          => $medico['nome'],
                ':especialidade' => $medico['especialidade'],
                ':crm'           => $medico['crm'],
                ':foto'          => $medico['foto'],
                ':ativo'         => $medico['ativo'],
            ]);

            definirMensagem('sucesso', 'Médico "' . $medico['nome'] . '" cadastrado com sucesso!');
            redirecionar('medicos/listar.php');
        } catch (PDOException $e) {
            error_log('Erro ao cadastrar médico: ' . $e->getMessage());
            $erros[] = 'Não foi possível cadastrar o médico. Tente novamente.';
        }
    }
}

$titulo = 'Cadastrar médico';
$textoBotao = 'Cadastrar';
require __DIR__ . '/../includes/cabecalho.php';
exibirErros($erros);
require __DIR__ . '/_formulario.php';
require __DIR__ . '/../includes/rodape.php';

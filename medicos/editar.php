<?php
/**
 * medicos/editar.php?id=N
 * GET: mostra o formulário preenchido. POST: valida e atualiza o médico.
 */
require_once __DIR__ . '/../includes/funcoes.php';

// O id vem da URL: converte para inteiro e confere se o médico existe
$id = (int) ($_GET['id'] ?? 0);
$medico = buscarMedico($id);

if (!$medico) {
    definirMensagem('erro', 'Médico não encontrado.');
    redirecionar('medicos/listar.php');
}

$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $medico['nome']          = campo('nome');
    $medico['especialidade'] = campo('especialidade');
    $medico['crm']           = normalizarCrm(campo('crm'));
    $medico['ativo']         = isset($_POST['ativo']) ? 1 : 0;

    // Passa o id para a validação ignorar o próprio CRM na checagem de duplicado
    $erros = validarMedico($medico, $id);

    if (!$erros) {
        $upload = salvarFotoMedico();
        if ($upload['erro']) {
            $erros[] = $upload['erro'];
        } elseif ($upload['caminho']) {
            $medico['foto'] = $upload['caminho']; // trocou a foto
        }
        // sem upload novo: mantém a foto que já estava
    }

    if (!$erros) {
        try {
            $sql = 'UPDATE medicos
                    SET nome = :nome, especialidade = :especialidade, crm = :crm,
                        foto = :foto, ativo = :ativo
                    WHERE id = :id';
            $stmt = conectar()->prepare($sql);
            $stmt->execute([
                ':nome'          => $medico['nome'],
                ':especialidade' => $medico['especialidade'],
                ':crm'           => $medico['crm'],
                ':foto'          => $medico['foto'],
                ':ativo'         => $medico['ativo'],
                ':id'            => $id,
            ]);

            definirMensagem('sucesso', 'Dados do médico "' . $medico['nome'] . '" atualizados com sucesso!');
            redirecionar('medicos/listar.php');
        } catch (PDOException $e) {
            error_log('Erro ao editar médico ' . $id . ': ' . $e->getMessage());
            $erros[] = 'Não foi possível salvar as alterações. Tente novamente.';
        }
    }
}

$titulo = 'Editar médico';
$textoBotao = 'Salvar alterações';
require __DIR__ . '/../includes/cabecalho.php';
exibirErros($erros);
require __DIR__ . '/_formulario.php';
require __DIR__ . '/../includes/rodape.php';

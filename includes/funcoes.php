<?php
/**
 * includes/funcoes.php
 * Funções auxiliares usadas em todo o sistema: segurança, validações,
 * formatação e mensagens. Toda página começa incluindo este arquivo.
 */

require_once __DIR__ . '/../config/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

// Sessão: usada para guardar mensagens entre um redirecionamento e outro
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
 * BASE_URL: endereço da pasta do projeto (ex.: /Projeto-clinica-web/).
 * Calculado automaticamente, assim os links funcionam em qualquer subpasta.
 */
$raizProjeto = str_replace('\\', '/', realpath(__DIR__ . '/..'));
$raizServidor = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
define('BASE_URL', rtrim(substr($raizProjeto, strlen($raizServidor)), '/') . '/');

/**
 * Qualquer erro não tratado cai aqui: registra no log e mostra mensagem amigável.
 * Assim nunca aparece SQL ou erro técnico para o usuário.
 */
set_exception_handler(function (Throwable $e) {
    error_log('Erro não tratado: ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo '<p style="font-family:sans-serif;padding:40px;text-align:center">'
        . 'Ocorreu um erro inesperado. Tente novamente mais tarde.</p>';
});

// =====================================================================
// SEGURANÇA E NAVEGAÇÃO
// =====================================================================

/** Escapa texto antes de exibir no HTML (proteção contra XSS). */
function e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

/** Monta um link a partir da raiz do projeto. Ex.: url('medicos/listar.php') */
function url(string $caminho = ''): string
{
    return BASE_URL . ltrim($caminho, '/');
}

/** Redireciona para outra página do projeto e encerra o script. */
function redirecionar(string $caminho): void
{
    header('Location: ' . url($caminho));
    exit;
}

/** Lê um campo do formulário (POST) já sem espaços nas pontas. */
function campo(string $nome): string
{
    return trim((string) ($_POST[$nome] ?? ''));
}

// =====================================================================
// MENSAGENS (sucesso / erro) - "flash": aparecem uma vez e somem
// =====================================================================

/** Guarda uma mensagem para mostrar na próxima página. $tipo: 'sucesso' ou 'erro'. */
function definirMensagem(string $tipo, string $texto): void
{
    $_SESSION['mensagem'] = ['tipo' => $tipo, 'texto' => $texto];
}

/** Mostra (e apaga) a mensagem guardada, se houver. */
function exibirMensagem(): void
{
    if (!empty($_SESSION['mensagem'])) {
        $m = $_SESSION['mensagem'];
        echo '<div class="alerta alerta-' . e($m['tipo']) . '">' . e($m['texto']) . '</div>';
        unset($_SESSION['mensagem']);
    }
}

/** Mostra uma lista de erros de validação (usado nos formulários). */
function exibirErros(array $erros): void
{
    if (!$erros) {
        return;
    }
    echo '<div class="alerta alerta-erro"><strong>Corrija os campos abaixo:</strong><ul>';
    foreach ($erros as $erro) {
        echo '<li>' . e($erro) . '</li>';
    }
    echo '</ul></div>';
}

// =====================================================================
// VALIDAÇÕES
// =====================================================================

/** Remove tudo que não for número. Ex.: "123.456.789-09" => "12345678909" */
function somenteDigitos(string $valor): string
{
    return preg_replace('/\D/', '', $valor);
}

/** Verifica se o texto tem entre $min e $max caracteres (conta acentos certo). */
function tamanhoValido(string $valor, int $min, int $max): bool
{
    $tamanho = mb_strlen($valor, 'UTF-8');
    return $tamanho >= $min && $tamanho <= $max;
}

/**
 * Valida CPF: 11 dígitos, não pode ser todos iguais e os
 * 2 dígitos verificadores precisam bater com o cálculo oficial.
 */
function validarCpf(string $cpf): bool
{
    $cpf = somenteDigitos($cpf);

    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false; // tamanho errado ou tipo 111.111.111-11
    }

    // Calcula o 1º dígito (pesos 10..2) e depois o 2º (pesos 11..2)
    for ($posicao = 9; $posicao < 11; $posicao++) {
        $soma = 0;
        for ($i = 0; $i < $posicao; $i++) {
            $soma += (int) $cpf[$i] * (($posicao + 1) - $i);
        }
        $digito = ($soma * 10) % 11;
        if ($digito === 10) {
            $digito = 0;
        }
        if ((int) $cpf[$posicao] !== $digito) {
            return false;
        }
    }
    return true;
}

/** Valida e-mail usando o filtro nativo do PHP. */
function validarEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false && mb_strlen($email) <= 150;
}

/**
 * Valida telefone brasileiro com DDD:
 * - fixo: 10 dígitos (DDD + 8)   ex.: (11) 3456-7890
 * - celular: 11 dígitos (DDD + 9 + 8) ex.: (11) 98765-4321
 */
function validarTelefone(string $telefone): bool
{
    $tel = somenteDigitos($telefone);
    // DDD de 11 a 99 (não começa com 0) + 8 dígitos, ou + 9 e 8 dígitos para celular
    return (bool) preg_match('/^[1-9][1-9](9\d{8}|[2-8]\d{7})$/', $tel);
}

/** Confere se a data existe no formato AAAA-MM-DD (o que vem do <input type="date">). */
function validarData(string $data): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $data);
    return $d !== false && $d->format('Y-m-d') === $data;
}

/**
 * Converte o valor do <input type="datetime-local"> (AAAA-MM-DDTHH:MM)
 * em DateTime. Retorna null se for inválido.
 */
function converterDataHora(string $valor): ?DateTime
{
    $d = DateTime::createFromFormat('Y-m-d\TH:i', $valor);
    if ($d === false || $d->format('Y-m-d\TH:i') !== $valor) {
        return null;
    }
    $d->setTime((int) $d->format('H'), (int) $d->format('i'), 0);
    return $d;
}

/**
 * Regra de negócio: o médico já tem agendamento ATIVO (agendado/confirmado)
 * nesse mesmo horário? $ignorarId serve na edição (não comparar com ele mesmo).
 */
function horarioOcupado(int $medicoId, string $dataHora, ?int $ignorarId = null): bool
{
    $sql = "SELECT COUNT(*) FROM agendamentos
            WHERE medico_id = :medico
              AND data_hora = :data_hora
              AND status IN ('agendado', 'confirmado')
              AND id <> :ignorar";
    $stmt = conectar()->prepare($sql);
    $stmt->execute([
        ':medico'    => $medicoId,
        ':data_hora' => $dataHora,
        ':ignorar'   => $ignorarId ?? 0,
    ]);
    return (int) $stmt->fetchColumn() > 0;
}

// =====================================================================
// MÉDICOS
// =====================================================================

/**
 * Valida os dados do formulário de médico.
 * $dados: ['nome', 'especialidade', 'crm']. $idAtual: id na edição (para checar CRM duplicado).
 * Retorna a lista de erros (vazia = tudo certo).
 */
function validarMedico(array $dados, ?int $idAtual = null): array
{
    $erros = [];

    if ($dados['nome'] === '') {
        $erros[] = 'Informe o nome do médico.';
    } elseif (!tamanhoValido($dados['nome'], 3, 100)) {
        $erros[] = 'O nome deve ter entre 3 e 100 caracteres.';
    }

    if ($dados['especialidade'] === '') {
        $erros[] = 'Informe a especialidade.';
    } elseif (!tamanhoValido($dados['especialidade'], 3, 100)) {
        $erros[] = 'A especialidade deve ter entre 3 e 100 caracteres.';
    }

    // Formato esperado: CRM/UF 123456 (sigla do estado + 4 a 7 dígitos)
    if ($dados['crm'] === '') {
        $erros[] = 'Informe o CRM.';
    } elseif (!preg_match('/^CRM\/[A-Z]{2} \d{4,7}$/', $dados['crm'])) {
        $erros[] = 'CRM inválido. Use o formato CRM/UF 123456 (ex.: CRM/SP 123456).';
    } else {
        // Consistência: o CRM não pode pertencer a outro médico
        $stmt = conectar()->prepare('SELECT COUNT(*) FROM medicos WHERE crm = :crm AND id <> :id');
        $stmt->execute([':crm' => $dados['crm'], ':id' => $idAtual ?? 0]);
        if ($stmt->fetchColumn() > 0) {
            $erros[] = 'Já existe um médico cadastrado com este CRM.';
        }
    }

    return $erros;
}

/**
 * Normaliza o CRM digitado: "crm/sp   123456" => "CRM/SP 123456".
 */
function normalizarCrm(string $crm): string
{
    $crm = strtoupper(preg_replace('/\s+/', ' ', trim($crm)));
    return str_replace('CRM / ', 'CRM/', str_replace(' / ', '/', $crm));
}

/**
 * Valida e salva a foto enviada no campo "foto" (opcional).
 * Retorna: ['caminho' => 'src/medicos/xxx.jpg' ou null, 'erro' => texto ou null].
 */
function salvarFotoMedico(): array
{
    // Nenhum arquivo enviado: não é erro, a foto é opcional
    if (empty($_FILES['foto']) || $_FILES['foto']['error'] === UPLOAD_ERR_NO_FILE) {
        return ['caminho' => null, 'erro' => null];
    }

    $arquivo = $_FILES['foto'];

    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        return ['caminho' => null, 'erro' => 'Não foi possível enviar a foto. Tente novamente.'];
    }
    if ($arquivo['size'] > 2 * 1024 * 1024) {
        return ['caminho' => null, 'erro' => 'A foto deve ter no máximo 2 MB.'];
    }

    // Confere o tipo REAL do arquivo (não confia na extensão enviada)
    $tiposPermitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $tipo = mime_content_type($arquivo['tmp_name']);
    if (!isset($tiposPermitidos[$tipo])) {
        return ['caminho' => null, 'erro' => 'A foto deve ser uma imagem JPG, PNG ou WEBP.'];
    }

    // Nome aleatório: evita sobrescrever arquivos e nomes maliciosos
    $nomeArquivo = 'medico_' . bin2hex(random_bytes(8)) . '.' . $tiposPermitidos[$tipo];
    $pasta = __DIR__ . '/../src/medicos/';
    if (!is_dir($pasta)) {
        mkdir($pasta, 0755, true);
    }

    if (!move_uploaded_file($arquivo['tmp_name'], $pasta . $nomeArquivo)) {
        error_log('Falha ao mover upload para ' . $pasta . $nomeArquivo);
        return ['caminho' => null, 'erro' => 'Não foi possível salvar a foto. Tente novamente.'];
    }

    return ['caminho' => 'src/medicos/' . $nomeArquivo, 'erro' => null];
}

/** Busca um médico pelo id (ou null se não existir). */
function buscarMedico(int $id): ?array
{
    $stmt = conectar()->prepare('SELECT * FROM medicos WHERE id = :id');
    $stmt->execute([':id' => $id]);
    return $stmt->fetch() ?: null;
}

// =====================================================================
// PACIENTES
// =====================================================================

/**
 * Valida os dados de paciente. CPF e telefone já devem vir só com dígitos.
 * $checarCpfDuplicado = false é usado no site público, onde CPF existente
 * significa "reutilizar o paciente" e não erro.
 * Retorna a lista de erros (vazia = tudo certo).
 */
function validarPaciente(array $dados, ?int $idAtual = null, bool $checarCpfDuplicado = true): array
{
    $erros = [];

    if ($dados['nome'] === '') {
        $erros[] = 'Informe o nome do paciente.';
    } elseif (!tamanhoValido($dados['nome'], 3, 100)) {
        $erros[] = 'O nome deve ter entre 3 e 100 caracteres.';
    } elseif (!preg_match('/^[\p{L}\s\'.-]+$/u', $dados['nome'])) {
        $erros[] = 'O nome deve conter apenas letras.';
    }

    if ($dados['cpf'] === '') {
        $erros[] = 'Informe o CPF.';
    } elseif (!validarCpf($dados['cpf'])) {
        $erros[] = 'CPF inválido. Confira os números digitados.';
    } elseif ($checarCpfDuplicado) {
        $stmt = conectar()->prepare('SELECT COUNT(*) FROM pacientes WHERE cpf = :cpf AND id <> :id');
        $stmt->execute([':cpf' => $dados['cpf'], ':id' => $idAtual ?? 0]);
        if ($stmt->fetchColumn() > 0) {
            $erros[] = 'Já existe um paciente cadastrado com este CPF.';
        }
    }

    if ($dados['telefone'] === '') {
        $erros[] = 'Informe o telefone.';
    } elseif (!validarTelefone($dados['telefone'])) {
        $erros[] = 'Telefone inválido. Informe com DDD, ex.: (11) 98765-4321.';
    }

    if ($dados['email'] === '') {
        $erros[] = 'Informe o e-mail.';
    } elseif (!validarEmail($dados['email'])) {
        $erros[] = 'E-mail inválido. Ex.: nome@email.com';
    }

    // Data de nascimento: precisa existir, não pode ser futura nem absurda (> 130 anos)
    if ($dados['data_nascimento'] === '') {
        $erros[] = 'Informe a data de nascimento.';
    } elseif (!validarData($dados['data_nascimento'])) {
        $erros[] = 'Data de nascimento inválida.';
    } elseif ($dados['data_nascimento'] > date('Y-m-d')) {
        $erros[] = 'A data de nascimento não pode estar no futuro.';
    } elseif ($dados['data_nascimento'] < date('Y-m-d', strtotime('-130 years'))) {
        $erros[] = 'Data de nascimento muito antiga. Confira o ano.';
    }

    return $erros;
}

/** Lê os campos do formulário de paciente já limpos (CPF/telefone só dígitos). */
function lerPacienteDoFormulario(): array
{
    return [
        'nome'            => campo('nome'),
        'cpf'             => somenteDigitos(campo('cpf')),
        'telefone'        => somenteDigitos(campo('telefone')),
        'email'           => mb_strtolower(campo('email')),
        'data_nascimento' => campo('data_nascimento'),
    ];
}

/** Busca um paciente pelo id (ou null se não existir). */
function buscarPaciente(int $id): ?array
{
    $stmt = conectar()->prepare('SELECT * FROM pacientes WHERE id = :id');
    $stmt->execute([':id' => $id]);
    return $stmt->fetch() ?: null;
}

// =====================================================================
// AGENDAMENTOS
// =====================================================================

/**
 * Valida os dados de agendamento.
 * $dados: ['medico_id', 'paciente_id', 'data_hora' (AAAA-MM-DDTHH:MM), 'status', 'observacao']
 * $original: o agendamento como está no banco (só na edição; null no cadastro).
 * Retorna a lista de erros (vazia = tudo certo).
 */
function validarAgendamento(array $dados, ?array $original = null): array
{
    $erros = [];
    $idAtual = $original ? (int) $original['id'] : null;

    // Médico: precisa existir. No cadastro (ou trocando de médico) precisa estar ativo.
    $medico = $dados['medico_id'] > 0 ? buscarMedico($dados['medico_id']) : null;
    if (!$medico) {
        $erros[] = 'Selecione um médico válido.';
    } elseif (!$medico['ativo'] && (!$original || (int) $original['medico_id'] !== $dados['medico_id'])) {
        $erros[] = 'Este médico está inativo e não aceita novos agendamentos.';
    }

    // Paciente: precisa existir. No site público vem null, porque o paciente
    // ainda pode ser criado na hora (ele é validado à parte com validarPaciente).
    if ($dados['paciente_id'] !== null && ($dados['paciente_id'] <= 0 || !buscarPaciente($dados['paciente_id']))) {
        $erros[] = 'Selecione um paciente válido.';
    }

    // Status: só os valores do ENUM
    if (!array_key_exists($dados['status'], listaStatus())) {
        $erros[] = 'Selecione um status válido.';
    }

    if (mb_strlen($dados['observacao'], 'UTF-8') > 500) {
        $erros[] = 'A observação deve ter no máximo 500 caracteres.';
    }

    // Data e hora
    $dataHora = $dados['data_hora'] === '' ? null : converterDataHora($dados['data_hora']);
    if ($dados['data_hora'] === '') {
        $erros[] = 'Informe a data e a hora da consulta.';
    } elseif (!$dataHora) {
        $erros[] = 'Data/hora inválida.';
    } else {
        $dataHoraBanco = $dataHora->format('Y-m-d H:i:s');
        $mudouHorario = !$original || $original['data_hora'] !== $dataHoraBanco;
        $agora = new DateTime();

        // Não aceita horário no passado (na edição, só se o horário foi alterado,
        // para permitir mudar o status de consultas antigas, ex.: "realizado")
        if ($mudouHorario && $dataHora <= $agora) {
            $erros[] = 'Não é possível agendar em data/hora que já passou.';
        }

        // Consistência: não dá para marcar como realizada uma consulta futura
        if ($dados['status'] === 'realizado' && $dataHora > $agora) {
            $erros[] = 'Uma consulta futura não pode ter status "Realizado".';
        }

        // Regra principal: médico não pode ter 2 agendamentos ativos no mesmo horário
        $statusAtivo = in_array($dados['status'], ['agendado', 'confirmado'], true);
        if ($medico && $statusAtivo && horarioOcupado($dados['medico_id'], $dataHoraBanco, $idAtual)) {
            $erros[] = 'Este médico já possui um agendamento ativo em ' . $dataHora->format('d/m/Y \à\s H:i')
                . '. Escolha outro horário.';
        }
    }

    return $erros;
}

/** Lê os campos do formulário de agendamento. */
function lerAgendamentoDoFormulario(): array
{
    return [
        'medico_id'   => (int) campo('medico_id'),
        'paciente_id' => (int) campo('paciente_id'),
        'data_hora'   => campo('data_hora'),
        'status'      => campo('status'),
        'observacao'  => campo('observacao'),
    ];
}

/** Busca um agendamento com os nomes do médico e do paciente (JOIN). */
function buscarAgendamento(int $id): ?array
{
    $sql = 'SELECT a.*, m.nome AS medico_nome, p.nome AS paciente_nome
            FROM agendamentos a
            INNER JOIN medicos m ON m.id = a.medico_id
            INNER JOIN pacientes p ON p.id = a.paciente_id
            WHERE a.id = :id';
    $stmt = conectar()->prepare($sql);
    $stmt->execute([':id' => $id]);
    return $stmt->fetch() ?: null;
}

// =====================================================================
// FORMATAÇÃO PARA EXIBIÇÃO
// =====================================================================

/** "12345678909" => "123.456.789-09" */
function formatarCpf(string $cpf): string
{
    return preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', $cpf);
}

/** "11987654321" => "(11) 98765-4321"  |  "1134567890" => "(11) 3456-7890" */
function formatarTelefone(string $tel): string
{
    if (strlen($tel) === 11) {
        return preg_replace('/^(\d{2})(\d{5})(\d{4})$/', '($1) $2-$3', $tel);
    }
    return preg_replace('/^(\d{2})(\d{4})(\d{4})$/', '($1) $2-$3', $tel);
}

/** "2026-11-18 09:00:00" => "18/11/2026 09:00" */
function formatarDataHora(string $dataHora): string
{
    return date('d/m/Y H:i', strtotime($dataHora));
}

/** "1990-04-12" => "12/04/1990" */
function formatarData(string $data): string
{
    return date('d/m/Y', strtotime($data));
}

/** Status possíveis de um agendamento (valor no banco => texto na tela). */
function listaStatus(): array
{
    return [
        'agendado'   => 'Agendado',
        'confirmado' => 'Confirmado',
        'cancelado'  => 'Cancelado',
        'realizado'  => 'Realizado',
    ];
}

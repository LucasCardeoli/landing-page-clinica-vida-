<?php
/**
 * testes/executar_testes.php
 * Testes automáticos das validações e regras de negócio (Etapa 6).
 *
 * Como rodar (Apache e MySQL ligados), no terminal:
 *     C:\xampp\php\php.exe testes\executar_testes.php
 *
 * Parte 1: testa as funções de validação direto (CPF, e-mail, telefone, datas).
 * Parte 2: envia formulários de verdade ao servidor (como o navegador faria)
 *          e confere se cada caso inválido é recusado com a mensagem certa.
 * No final, apaga tudo o que os testes criaram no banco.
 *
 * Só roda pelo terminal (o .htaccess desta pasta bloqueia acesso pelo navegador).
 */

if (PHP_SAPI !== 'cli') {
    exit('Execute pelo terminal.');
}

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2); // necessário para o funcoes.php calcular a BASE_URL
require_once __DIR__ . '/../includes/funcoes.php';

const URL_SITE = 'http://localhost/Projeto-clinica-web/';
const MARCA_TESTE = 'TESTE_AUTOMATICO'; // vai na observação, para achar e apagar depois
const CPF_TESTE = '27548438923';         // CPF válido que não está nos dados iniciais

$total = 0;
$falhas = 0;

/** Registra o resultado de um teste e imprime OK/FALHOU. */
function conferir(string $descricao, bool $passou): void
{
    global $total, $falhas;
    $total++;
    if (!$passou) {
        $falhas++;
    }
    echo ($passou ? '  [OK]     ' : '  [FALHOU] ') . $descricao . PHP_EOL;
}

/** Envia um POST ao servidor e devolve o corpo da resposta. */
function enviarPost(string $pagina, array $dados): string
{
    $contexto = stream_context_create(['http' => [
        'method'        => 'POST',
        'header'        => 'Content-Type: application/x-www-form-urlencoded',
        'content'       => http_build_query($dados),
        'ignore_errors' => true,
    ]]);
    $resposta = file_get_contents(URL_SITE . $pagina, false, $contexto);
    if ($resposta === false) {
        exit('Não consegui acessar ' . URL_SITE . '. O Apache está ligado?' . PHP_EOL);
    }
    return $resposta;
}

/** Envia ao agendar.php e devolve o JSON decodificado. */
function agendarPeloSite(array $alteracoes): array
{
    // Dados válidos de base; cada teste altera só o campo que quer quebrar
    $base = [
        'medico_id'       => 1,
        'data_hora'       => date('Y-m-d', strtotime('+30 days')) . 'T07:15',
        'nome'            => 'Paciente de Teste',
        'cpf'             => '275.484.389-23',
        'telefone'        => '(11) 98888-7777',
        'email'           => 'teste@email.com',
        'data_nascimento' => '1995-05-10',
        'observacao'      => MARCA_TESTE,
    ];
    return json_decode(enviarPost('agendar.php', array_merge($base, $alteracoes)), true) ?? [];
}

/** O erro esperado está na lista devolvida? (busca por parte do texto) */
function temErro(array $resposta, string $trecho): bool
{
    foreach ($resposta['erros'] ?? [] as $erro) {
        if (str_contains($erro, $trecho)) {
            return true;
        }
    }
    return str_contains($resposta['mensagem'] ?? '', $trecho);
}

// =====================================================================
echo PHP_EOL . '=== PARTE 1: funções de validação ===' . PHP_EOL;
// =====================================================================

conferir('CPF válido com pontuação (123.456.789-09)', validarCpf('123.456.789-09'));
conferir('CPF válido só números (11144477735)', validarCpf('11144477735'));
conferir('CPF com dígito verificador errado é recusado', !validarCpf('123.456.789-00'));
conferir('CPF com todos os dígitos iguais é recusado', !validarCpf('111.111.111-11'));
conferir('CPF curto é recusado', !validarCpf('123.456'));
conferir('CPF com letras é recusado', !validarCpf('abc.def.ghi-jk'));

conferir('E-mail válido', validarEmail('nome@email.com'));
conferir('E-mail sem domínio é recusado', !validarEmail('nome@'));
conferir('E-mail sem @ é recusado', !validarEmail('nome.email.com'));

conferir('Celular com DDD (11) 98765-4321', validarTelefone('(11) 98765-4321'));
conferir('Fixo com DDD (11) 3456-7890', validarTelefone('(11) 3456-7890'));
conferir('Telefone sem DDD é recusado', !validarTelefone('98765-4321'));
conferir('DDD começando com 0 é recusado', !validarTelefone('(01) 98765-4321'));
conferir('Celular de 11 dígitos sem o 9 é recusado', !validarTelefone('(11) 88765-4321'));

conferir('Data existente 2000-02-29 (ano bissexto)', validarData('2000-02-29'));
conferir('Data inexistente 2026-02-30 é recusada', !validarData('2026-02-30'));
conferir('Data em formato errado 10/05/1995 é recusada', !validarData('10/05/1995'));
conferir('Data/hora válida do datetime-local', converterDataHora('2026-12-01T09:30') !== null);
conferir('Data/hora inválida é recusada', converterDataHora('2026-12-01 25:00') === null);

conferir('Tamanho: "Ana" cabe em 3..100', tamanhoValido('Ana', 3, 100));
conferir('Tamanho: "Jo" é curto demais', !tamanhoValido('Jo', 3, 100));
conferir('Tamanho conta acentos certo ("Zé" = 2)', !tamanhoValido('Zé', 3, 100));

// =====================================================================
echo PHP_EOL . '=== PARTE 2a: agendamento pelo site (agendar.php) ===' . PHP_EOL;
// =====================================================================

$vazio = json_decode(enviarPost('agendar.php', []), true);
conferir('Formulário vazio: recusado com 7 erros', !$vazio['sucesso'] && count($vazio['erros']) === 7);

$r = agendarPeloSite(['nome' => '']);
conferir('Nome vazio', !$r['sucesso'] && temErro($r, 'Informe o nome'));

$r = agendarPeloSite(['nome' => 'Jo']);
conferir('Nome com menos de 3 letras', !$r['sucesso'] && temErro($r, 'entre 3 e 100'));

$r = agendarPeloSite(['nome' => str_repeat('a', 101)]);
conferir('Nome com mais de 100 letras', !$r['sucesso'] && temErro($r, 'entre 3 e 100'));

$r = agendarPeloSite(['nome' => 'Maria123']);
conferir('Nome com números', !$r['sucesso'] && temErro($r, 'apenas letras'));

$r = agendarPeloSite(['cpf' => '123.456.789-00']);
conferir('CPF inválido (dígito verificador)', !$r['sucesso'] && temErro($r, 'CPF inválido'));

$r = agendarPeloSite(['cpf' => '000.000.000-00']);
conferir('CPF 000.000.000-00', !$r['sucesso'] && temErro($r, 'CPF inválido'));

$r = agendarPeloSite(['email' => 'teste@']);
conferir('E-mail inválido', !$r['sucesso'] && temErro($r, 'E-mail inválido'));

$r = agendarPeloSite(['telefone' => '98888-7777']);
conferir('Telefone sem DDD', !$r['sucesso'] && temErro($r, 'Telefone inválido'));

$r = agendarPeloSite(['data_nascimento' => date('Y-m-d', strtotime('+1 day'))]);
conferir('Nascimento no futuro', !$r['sucesso'] && temErro($r, 'não pode estar no futuro'));

$r = agendarPeloSite(['data_nascimento' => '1995-02-30']);
conferir('Nascimento em data inexistente', !$r['sucesso'] && temErro($r, 'nascimento inválida'));

$r = agendarPeloSite(['data_hora' => date('Y-m-d', strtotime('-1 day')) . 'T10:00']);
conferir('Consulta em data passada', !$r['sucesso'] && temErro($r, 'já passou'));

$r = agendarPeloSite(['data_hora' => date('Y-m-d') . 'T00:00']);
conferir('Consulta hoje à meia-noite (já passou)', !$r['sucesso'] && temErro($r, 'já passou'));

$r = agendarPeloSite(['data_hora' => 'amanhã cedo']);
conferir('Data/hora em formato inválido', !$r['sucesso'] && temErro($r, 'Data/hora inválida'));

$r = agendarPeloSite(['medico_id' => 999]);
conferir('Médico inexistente', !$r['sucesso'] && temErro($r, 'médico válido'));

$r = agendarPeloSite(['observacao' => str_repeat('x', 501)]);
conferir('Observação com mais de 500 caracteres', !$r['sucesso'] && temErro($r, 'máximo 500'));

// Horário ocupado pelos dados iniciais: Dra. Mariana, daqui a 2 dias às 09:00
$r = agendarPeloSite(['data_hora' => date('Y-m-d', strtotime('+2 days')) . 'T09:00']);
conferir('Horário já ocupado (dado inicial)', !$r['sucesso'] && temErro($r, 'já possui um agendamento ativo'));

// Caso de sucesso: cria paciente novo
$r = agendarPeloSite([]);
conferir('Dados válidos: agendamento criado', $r['sucesso'] === true);

// O mesmo horário de novo deve ser recusado agora
$r = agendarPeloSite([]);
conferir('Mesmo médico e horário de novo: recusado', !$r['sucesso'] && temErro($r, 'já possui um agendamento ativo'));

// Outro horário com o mesmo CPF: reutiliza o paciente (não duplica)
$r = agendarPeloSite(['data_hora' => date('Y-m-d', strtotime('+30 days')) . 'T07:45', 'nome' => 'Nome Diferente']);
$stmt = conectar()->prepare('SELECT COUNT(*) FROM pacientes WHERE cpf = :cpf');
$stmt->execute([':cpf' => CPF_TESTE]);
conferir('CPF já cadastrado: reutiliza o paciente', $r['sucesso'] && temErro($r, 'Encontramos seu cadastro') && (int) $stmt->fetchColumn() === 1);

// Outro médico no mesmo horário: permitido (a regra é por médico)
$r = agendarPeloSite(['medico_id' => 2]);
conferir('Outro médico no mesmo horário: permitido', $r['sucesso'] === true);

// XSS: o texto deve ser gravado como texto e escapado ao exibir
$r = agendarPeloSite(['medico_id' => 3, 'observacao' => MARCA_TESTE . ' <script>alert(1)</script>']);
$html = file_get_contents(URL_SITE . 'agendamentos/listar.php');
conferir('XSS na observação: exibido escapado no admin', $r['sucesso']
    && str_contains($html, '&lt;script&gt;alert(1)&lt;/script&gt;') && !str_contains($html, '<script>alert(1)'));

// SQL injection: o texto vira dado comum, não comando
$r = agendarPeloSite(['nome' => "Robert'); DROP TABLE pacientes;--"]);
$tabelaExiste = (bool) conectar()->query("SHOW TABLES LIKE 'pacientes'")->fetch();
conferir('SQL injection no nome: recusado e tabela intacta', !$r['sucesso'] && $tabelaExiste);

// =====================================================================
echo PHP_EOL . '=== PARTE 2b: área administrativa ===' . PHP_EOL;
// =====================================================================

$html = enviarPost('pacientes/cadastrar.php', [
    'nome' => 'Duplicado Teste', 'cpf' => '123.456.789-09', 'telefone' => '11987654321',
    'email' => 'dup@email.com', 'data_nascimento' => '1990-01-01',
]);
conferir('Paciente com CPF duplicado', str_contains($html, 'Já existe um paciente cadastrado com este CPF'));

$html = enviarPost('medicos/cadastrar.php', ['nome' => 'Dr. Duplicado', 'especialidade' => 'Teste', 'crm' => 'CRM/SP 123456']);
conferir('Médico com CRM duplicado', str_contains($html, 'Já existe um médico cadastrado com este CRM'));

$html = enviarPost('medicos/cadastrar.php', ['nome' => 'Dr. Teste', 'especialidade' => 'Teste', 'crm' => '12345']);
conferir('Médico com CRM em formato inválido', str_contains($html, 'CRM inválido'));

$html = enviarPost('agendamentos/cadastrar.php', [
    'medico_id' => 1, 'paciente_id' => 1, 'status' => 'realizado',
    'data_hora' => date('Y-m-d', strtotime('+10 days')) . 'T10:00',
]);
conferir('Consulta futura com status "Realizado"', str_contains($html, 'não pode ter status'));

$html = enviarPost('agendamentos/cadastrar.php', [
    'medico_id' => 1, 'paciente_id' => 1, 'status' => 'qualquer',
    'data_hora' => date('Y-m-d', strtotime('+10 days')) . 'T10:00',
]);
conferir('Status fora da lista', str_contains($html, 'status válido'));

// Exclusões bloqueadas (POST direto, como se a confirmação fosse pulada)
enviarPost('medicos/excluir.php?id=1', []);
conferir('Excluir médico com agendamentos: bloqueado', buscarMedico(1) !== null);

enviarPost('pacientes/excluir.php?id=1', []);
conferir('Excluir paciente com agendamentos: bloqueado', buscarPaciente(1) !== null);

// =====================================================================
// LIMPEZA: apaga o que os testes criaram
// =====================================================================
$pdo = conectar();
$stmt = $pdo->prepare('DELETE FROM agendamentos WHERE observacao LIKE :marca');
$stmt->execute([':marca' => MARCA_TESTE . '%']);
$stmt = $pdo->prepare('DELETE FROM pacientes WHERE cpf = :cpf
                       AND NOT EXISTS (SELECT 1 FROM agendamentos a WHERE a.paciente_id = pacientes.id)');
$stmt->execute([':cpf' => CPF_TESTE]);

echo PHP_EOL . "Resultado: " . ($total - $falhas) . " de $total testes passaram." . PHP_EOL;
echo $falhas ? "ATENÇÃO: $falhas teste(s) falharam." . PHP_EOL : 'Tudo certo!' . PHP_EOL;
exit($falhas ? 1 : 0);

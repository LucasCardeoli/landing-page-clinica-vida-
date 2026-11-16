<?php
/**
 * index.php - Site público da Clínica Vida+
 * Os cards dos médicos vêm do banco (só os ativos).
 * O botão "Agendar" abre o formulário em janela (modal) com o médico já selecionado.
 */
require_once __DIR__ . '/includes/funcoes.php';

// Consulta sem dado externo: lista fixa de médicos ativos
$medicos = conectar()
    ->query('SELECT id, nome, especialidade, foto FROM medicos WHERE ativo = 1 ORDER BY nome')
    ->fetchAll();

/** Texto do botão do card conforme o título: "Dra." / "Dr." */
function textoBotaoAgendar(string $nome): string
{
    if (str_starts_with($nome, 'Dra.')) {
        return 'Agendar com a Dra.';
    }
    if (str_starts_with($nome, 'Dr.')) {
        return 'Agendar com o Dr.';
    }
    return 'Agendar consulta';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clínica Vida+</title>
    <!-- ?v=data do arquivo: quando o CSS muda, o navegador baixa de novo (sem cache antigo) -->
    <link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__ . '/css/style.css') ?>">
    <link rel="stylesheet" href="css/header.css?v=<?= filemtime(__DIR__ . '/css/header.css') ?>">
</head>
<body>
    <header class="header">
        <nav id="navbar">
            <a href="#home"><img class="logo" src="src/logo.png" alt="Logotipo Clínica Vida+"></a>

            <!-- class em vez de id: a mesma lista aparece 2 vezes (desktop e mobile) -->
            <ul class="nav_list">
                <li class="nav_item"><a href="#home" class="nav_link">Home</a></li>
                <li class="nav_item"><a href="#profissionais" class="nav_link">Profissionais</a></li>
                <li class="nav_item"><a href="#contato" class="nav_link">Contato</a></li>
            </ul>

            <button type="button" class="btn-default js-abrir-agendamento">
                Agendar Horário
            </button>

            <!-- No celular, este botão abre/fecha o menu -->
            <button type="button" class="btn-mobile" aria-controls="mobile_menu" aria-expanded="false">
                &#9776; Menu
            </button>
        </nav>

        <div id="mobile_menu">
            <ul class="nav_list">
                <li class="nav_item"><a href="#home" class="nav_link">Home</a></li>
                <li class="nav_item"><a href="#profissionais" class="nav_link">Profissionais</a></li>
                <li class="nav_item"><a href="#contato" class="nav_link">Contato</a></li>
            </ul>

            <button type="button" class="btn-default js-abrir-agendamento">
                Agendar Horário
            </button>
        </div>
    </header>

    <main>
        <section id="home" class="hero" aria-label="Apresentação da Clínica Vida+"></section>

        <section id="profissionais" class="profissionais-grid">
            <?php if (!$medicos): ?>
                <p class="sem-medicos">No momento não há profissionais disponíveis para agendamento.</p>
            <?php endif; ?>

            <?php foreach ($medicos as $m): ?>
                <article class="card-medico">
                    <img src="<?= e($m['foto'] ?: 'src/logo.png') ?>" alt="<?= e($m['nome']) ?>">
                    <h3><?= e($m['nome']) ?></h3>
                    <p><?= e($m['especialidade']) ?></p>
                    <button type="button" class="btn-agendar js-abrir-agendamento" data-medico-id="<?= (int) $m['id'] ?>">
                        <?= e(textoBotaoAgendar($m['nome'])) ?>
                    </button>
                </article>
            <?php endforeach; ?>
        </section>
    </main>

    <footer id="contato" class="footer">
        <div class="footer-grid">
            <div class="footer-column">
                <h2>CONTATO</h2>
                <p>Telefone: (11) 3456-7890</p>
                <p>E-mail: contato@clinicavida.com</p>
                <p>WhatsApp: (11) 99876-5432</p>
            </div>

            <div class="footer-column">
                <h2>ENDEREÇO</h2>
                <p>Clínica Vida+ Saúde Integrada</p>
                <p>Avenida das Flores, 250</p>
                <p>Metropolis - SP</p>
            </div>
        </div>

        <!-- Acesso da equipe da clínica ao painel de cadastros e agendamentos -->
        <div class="footer-admin">
            <a href="admin/index.php">
                <!-- Ícone de cadeado (SVG usa a cor do texto: currentColor) -->
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="4" y="11" width="16" height="10" rx="2"></rect>
                    <path d="M8 11V7a4 4 0 0 1 8 0v4"></path>
                </svg>
                Área administrativa
            </a>
        </div>
    </footer>

    <!-- ===== Modal de agendamento ===== -->
    <div id="modal_agendamento" class="modal" role="dialog" aria-modal="true" aria-labelledby="modal_titulo" hidden>
        <div class="modal-caixa">
            <button type="button" class="modal-fechar" aria-label="Fechar">&times;</button>
            <h2 id="modal_titulo">Agendar consulta</h2>

            <!-- Mensagens de sucesso/erro aparecem aqui (preenchidas pelo script.js) -->
            <div id="agendamento_mensagem" aria-live="polite"></div>

            <form id="form_agendamento" action="agendar.php" method="post" novalidate>
                <div class="campo">
                    <label for="ag_medico">Profissional *</label>
                    <select id="ag_medico" name="medico_id" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($medicos as $m): ?>
                            <option value="<?= (int) $m['id'] ?>"><?= e($m['nome']) ?> — <?= e($m['especialidade']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="ag_data_hora">Data e hora *</label>
                    <input type="datetime-local" id="ag_data_hora" name="data_hora" required
                           min="<?= date('Y-m-d\TH:i') ?>">
                </div>

                <h3 class="modal-subtitulo">Seus dados</h3>

                <div class="campo">
                    <label for="ag_nome">Nome completo *</label>
                    <input type="text" id="ag_nome" name="nome" maxlength="100" required>
                </div>

                <div class="campos-lado">
                    <div class="campo">
                        <label for="ag_cpf">CPF *</label>
                        <input type="text" id="ag_cpf" name="cpf" maxlength="14" inputmode="numeric"
                               placeholder="000.000.000-00" required>
                    </div>
                    <div class="campo">
                        <label for="ag_nascimento">Data de nascimento *</label>
                        <input type="date" id="ag_nascimento" name="data_nascimento" max="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>

                <div class="campos-lado">
                    <div class="campo">
                        <label for="ag_telefone">Telefone com DDD *</label>
                        <input type="tel" id="ag_telefone" name="telefone" maxlength="15"
                               placeholder="(11) 98765-4321" required>
                    </div>
                    <div class="campo">
                        <label for="ag_email">E-mail *</label>
                        <input type="email" id="ag_email" name="email" maxlength="150"
                               placeholder="nome@email.com" required>
                    </div>
                </div>

                <div class="campo">
                    <label for="ag_observacao">Observação (opcional)</label>
                    <textarea id="ag_observacao" name="observacao" rows="2" maxlength="500"
                              placeholder="Ex.: primeira consulta, retorno, sintomas..."></textarea>
                </div>

                <button type="submit" class="btn-agendar btn-enviar">Confirmar agendamento</button>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="js/script.js?v=<?= filemtime(__DIR__ . '/js/script.js') ?>"></script>
</body>
</html>

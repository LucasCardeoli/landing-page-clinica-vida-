-- =====================================================================
-- Banco de dados da Clínica Vida+
-- Projeto Integrador - Tecnologias Web (2ª unidade)
--
-- Como usar: phpMyAdmin > aba "Importar" > escolher este arquivo > Executar.
-- O script APAGA e recria o banco "clinica_vida" (útil para resetar os testes).
-- =====================================================================

DROP DATABASE IF EXISTS clinica_vida;

CREATE DATABASE clinica_vida
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE clinica_vida;

-- ---------------------------------------------------------------------
-- Tabela: medicos
-- Guarda os profissionais exibidos no site e usados nos agendamentos.
-- ---------------------------------------------------------------------
CREATE TABLE medicos (
    id            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    nome          VARCHAR(100)  NOT NULL,
    especialidade VARCHAR(100)  NOT NULL,
    crm           VARCHAR(20)   NOT NULL,              -- ex.: CRM/SP 123456
    foto          VARCHAR(255)  NULL,                  -- caminho relativo, ex.: src/MarianaCosta.jpg
    ativo         TINYINT(1)    NOT NULL DEFAULT 1,    -- 1 = aparece no site, 0 = oculto
    criado_em     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_medicos_crm (crm)                    -- não pode repetir CRM
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabela: pacientes
-- CPF e telefone são guardados só com dígitos (a formatação é feita na tela).
-- ---------------------------------------------------------------------
CREATE TABLE pacientes (
    id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    nome            VARCHAR(100)  NOT NULL,
    cpf             CHAR(11)      NOT NULL,            -- 11 dígitos, sem pontos/traço
    telefone        VARCHAR(11)   NOT NULL,            -- DDD + número (10 ou 11 dígitos)
    email           VARCHAR(150)  NOT NULL,
    data_nascimento DATE          NOT NULL,
    criado_em       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_pacientes_cpf (cpf)                  -- não pode repetir CPF
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabela: agendamentos
-- Liga um médico a um paciente em uma data/hora (relacionamento N:N com dados).
-- ON DELETE RESTRICT: o banco impede excluir médico/paciente com agendamentos.
-- ---------------------------------------------------------------------
CREATE TABLE agendamentos (
    id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    medico_id   INT UNSIGNED  NOT NULL,
    paciente_id INT UNSIGNED  NOT NULL,
    data_hora   DATETIME      NOT NULL,
    status      ENUM('agendado','confirmado','cancelado','realizado') NOT NULL DEFAULT 'agendado',
    observacao  VARCHAR(500)  NULL,
    criado_em   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_agendamentos_medico_data (medico_id, data_hora),  -- acelera a checagem de horário ocupado
    KEY idx_agendamentos_paciente (paciente_id),
    CONSTRAINT fk_agendamentos_medico
        FOREIGN KEY (medico_id) REFERENCES medicos (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_agendamentos_paciente
        FOREIGN KEY (paciente_id) REFERENCES pacientes (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- DADOS DE TESTE
-- =====================================================================

-- Os 3 médicos que já estavam no site (fotos na pasta src/)
INSERT INTO medicos (nome, especialidade, crm, foto, ativo) VALUES
('Dra. Mariana Costa', 'Clínica Geral & Cardiologia',     'CRM/SP 123456', 'src/MarianaCosta.jpg', 1),
('Dr. Roberto Santos', 'Neurologia / Cardiologia',        'CRM/SP 234567', 'src/RobertoSantos.jpg', 1),
('Dr. Lucas Almeida',  'Pediatria / Medicina da Família', 'CRM/SP 345678', 'src/LucasAlmeida.jpg',  1);

-- Pacientes (CPFs fictícios, mas com dígitos verificadores válidos)
INSERT INTO pacientes (nome, cpf, telefone, email, data_nascimento) VALUES
('Ana Beatriz Souza',  '12345678909', '11987654321', 'ana.souza@email.com',     '1990-04-12'),
('Carlos Eduardo Lima','98765432100', '11912345678', 'carlos.lima@email.com',   '1985-09-30'),
('Fernanda Oliveira',  '11144477735', '1134567890',  'fernanda.oli@email.com',  '2001-01-22'),
('João Pedro Martins', '39053344705', '21998765432', 'joao.martins@email.com',  '2018-06-05'),
('Patrícia Gomes',     '45317828791', '11955554444', 'patricia.gomes@email.com','1972-11-17');

-- Agendamentos: datas calculadas a partir do dia da importação,
-- assim sempre existem consultas futuras e passadas para testar.
INSERT INTO agendamentos (medico_id, paciente_id, data_hora, status, observacao) VALUES
(1, 1, TIMESTAMP(CURDATE() + INTERVAL 2 DAY, '09:00:00'), 'agendado',   'Primeira consulta, check-up anual.'),
(1, 2, TIMESTAMP(CURDATE() + INTERVAL 2 DAY, '10:00:00'), 'confirmado', 'Retorno com exames de sangue.'),
(2, 5, TIMESTAMP(CURDATE() + INTERVAL 3 DAY, '14:30:00'), 'agendado',   'Dores de cabeça frequentes.'),
(3, 4, TIMESTAMP(CURDATE() + INTERVAL 5 DAY, '08:30:00'), 'confirmado', 'Consulta de rotina (criança).'),
(2, 3, TIMESTAMP(CURDATE() + INTERVAL 3 DAY, '16:00:00'), 'cancelado',  'Paciente pediu cancelamento.'),
(1, 5, TIMESTAMP(CURDATE() - INTERVAL 7 DAY, '11:00:00'), 'realizado',  NULL);

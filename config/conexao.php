<?php
/**
 * config/conexao.php
 * Credenciais do banco e função de conexão (PDO).
 * Fica separado do resto do código: para mudar de servidor, só se mexe aqui.
 */

// --- Credenciais (padrão do XAMPP: usuário root, sem senha) ---
define('DB_HOST', 'localhost');
define('DB_NOME', 'clinica_vida');
define('DB_USUARIO', 'root');
define('DB_SENHA', '');
define('DB_CHARSET', 'utf8mb4');

// --- Erros: nunca exibir na tela, apenas registrar em logs/erros.log ---
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/erros.log');

/**
 * Retorna a conexão PDO (cria só uma vez por requisição).
 */
function conectar(): PDO
{
    static $pdo = null; // "static" guarda o valor entre chamadas da função

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NOME . ';charset=' . DB_CHARSET;

        try {
            $pdo = new PDO($dsn, DB_USUARIO, DB_SENHA, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // erros viram exceções
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // resultados como array associativo
                PDO::ATTR_EMULATE_PREPARES   => false,                  // prepared statements reais
            ]);
        } catch (PDOException $e) {
            // Detalhe técnico vai para o log; o usuário vê só uma mensagem amigável
            error_log('Falha na conexão com o banco: ' . $e->getMessage());
            http_response_code(500);
            exit('<p style="font-family:sans-serif;padding:40px;text-align:center">'
                . 'Sistema temporariamente indisponível. Tente novamente em alguns minutos.</p>');
        }
    }

    return $pdo;
}

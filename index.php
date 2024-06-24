<?php
session_start();
include_once("conexao.php");

// Função para validar CPF
function validaCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/is', '', $cpf);
    if (strlen($cpf) != 11) {
        return false;
    }
    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    return true;
}

// Processar cadastro de usuário
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'cadastrar') {
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $cpf = filter_input(INPUT_POST, 'cpf', FILTER_SANITIZE_STRING);
    $creci = filter_input(INPUT_POST, 'creci', FILTER_SANITIZE_STRING);

    if (validaCPF($cpf)) {
        $result_usuario = "INSERT INTO usuarios (nome, cpf, creci, created) VALUES ('$nome', '$cpf', '$creci', NOW())";
        $resultado_usuario = mysqli_query($conn, $result_usuario);

        if (mysqli_insert_id($conn)) {
            $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>Usuário cadastrado com sucesso!</div>";
        } else {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Erro ao cadastrar usuário!</div>";
        }
    } else {
        $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>CPF inválido!</div>";
    }
}

// Processar edição de usuário
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'editar') {
    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $cpf = filter_input(INPUT_POST, 'cpf', FILTER_SANITIZE_STRING);
    $creci = filter_input(INPUT_POST, 'creci', FILTER_SANITIZE_STRING);

    if (validaCPF($cpf)) {
        $result_usuario = "UPDATE usuarios SET nome='$nome', cpf='$cpf', creci='$creci', modified=NOW() WHERE id='$id'";
        $resultado_usuario = mysqli_query($conn, $result_usuario);
        if (mysqli_affected_rows($conn)) {
            $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>Usuário editado com sucesso!</div>";
        } else {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Erro ao editar usuário!</div>";
        }
    } else {
        $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>CPF inválido!</div>";
    }
}

// Processar exclusão de usuário
if (isset($_GET['acao']) && $_GET['acao'] == 'apagar') {
    $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
    if (!empty($id)) {
        $result_usuario = "DELETE FROM usuarios WHERE id='$id'";
        $resultado_usuario = mysqli_query($conn, $result_usuario);

        if (mysqli_affected_rows($conn)) {
            $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>Usuário apagado com sucesso!</div>";
        } else {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Erro ao apagar usuário!</div>";
        }
    }
}

// Buscar dados do usuário para edição
$usuario_editar = null;
if (isset($_GET['acao']) && $_GET['acao'] == 'editar') {
    $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
    $result_usuario = "SELECT * FROM usuarios WHERE id='$id'";
    $resultado_usuario = mysqli_query($conn, $result_usuario);
    $usuario_editar = mysqli_fetch_assoc($resultado_usuario);
}

// Paginação
$pagina_atual = filter_input(INPUT_GET, 'pagina', FILTER_SANITIZE_NUMBER_INT);
$pagina = (!empty($pagina_atual)) ? $pagina_atual : 1;
$qnt_result_pg = 3;
$inicio = ($qnt_result_pg * $pagina) - $qnt_result_pg;

$result_usuarios = "SELECT * FROM usuarios LIMIT $inicio, $qnt_result_pg";
$resultado_usuarios = mysqli_query($conn, $result_usuarios);

$result_pg = "SELECT COUNT(id) AS num_result FROM usuarios";
$resultado_pg = mysqli_query($conn, $result_pg);
$row_pg = mysqli_fetch_assoc($resultado_pg);
$quantidade_pg = ceil($row_pg['num_result'] / $qnt_result_pg);
$max_links = 2;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Usuários</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Cadastro de Usuários</h1>
        <?php
        if (isset($_SESSION['msg'])) {
            echo $_SESSION['msg'];
            unset($_SESSION['msg']);
        }
        ?>
        <div class="card mb-4">
            <div class="card-body">
                <?php if ($usuario_editar): ?>
                    <h2 class="card-title">Editar Usuário</h2>
                    <form action="" method="post">
                        <input type="hidden" name="id" value="<?php echo $usuario_editar['id']; ?>">
                        <input type="hidden" name="acao" value="editar">
                        <div class="form-group">
                            <label for="nome">Nome:</label>
                            <input type="text" id="nome" name="nome" class="form-control" placeholder="Digite nome completo" value="<?php echo $usuario_editar['nome']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="cpf">CPF:</label>
                            <input type="text" id="cpf" name="cpf" class="form-control" placeholder="000.000.000-00" maxlength="14" pattern="\d{3}.\d{3}.\d{3}-\d{2}" required title="Digite o CPF no Formato 999.999.999-99" value="<?php echo $usuario_editar['cpf']; ?>">
                        </div>
                        <div class="form-group">
                            <label for="creci">CRECI:</label>
                            <input type="text" id="creci" name="creci" class="form-control" placeholder="Digite seu CRECI" value="<?php echo $usuario_editar['creci']; ?>" required>
                        </div>
                            <button type="submit" class="btn btn-success">Salvar</button>
                            <input type="button" value="Voltar" class="btn btn-danger" onclick="voltarParaIndex();">
                    </form>
                <?php else: ?>
                    <h2 class="card-title">Cadastrar Usuário</h2>
                    <form action="" method="post">
                        <input type="hidden" name="acao" value="cadastrar">
                        <div class="form-group">
                            <label for="nome">Nome:</label>
                            <input type="text" id="nome" name="nome" class="form-control" placeholder="Digite nome completo" required>
                        </div>
                        <div class="form-group">
                            <label for="cpf">CPF:</label>
                            <input type="text" id="cpf" name="cpf" class="form-control" placeholder="000.000.000-00" maxlength="14" pattern="\d{3}.\d{3}.\d{3}-\d{2}" required title="Digite o CPF no Formato 999.999.999-99">
                        </div>
                        <div class="form-group">
                            <label for="creci">CRECI:</label>
                            <input type="text" id="creci" name="creci" class="form-control" placeholder="Digite seu CRECI" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Cadastrar</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <h2>Lista de Usuários</h2>
        <?php while($row_usuario = mysqli_fetch_assoc($resultado_usuarios)): ?>
            <div class="card mb-2">
                <div class="card-body">
                    <h5 class="card-title">ID: <?php echo $row_usuario['id']; ?></h5>
                    <p class="card-text">Nome: <?php echo $row_usuario['nome']; ?></p>
                    <p class="card-text">CPF: <?php echo $row_usuario['cpf']; ?></p>
                    <p class="card-text">CRECI: <?php echo $row_usuario['creci']; ?></p>
                    <a href="?acao=editar&id=<?php echo $row_usuario['id']; ?>" class="btn btn-warning">Editar</a>
                    <a href="?acao=apagar&id=<?php echo $row_usuario['id']; ?>" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja excluir este usuário?');">Apagar</a>
                </div>
            </div>
        <?php endwhile; ?>

        <nav>
            <ul class="pagination justify-content-center">
                <li class="page-item"><a class="page-link" href="?pagina=1">Primeira</a></li>
                <?php for ($pag_ant = $pagina - $max_links; $pag_ant <= $pagina - 1; $pag_ant++): ?>
                    <?php if ($pag_ant >= 1): ?>
                        <li class="page-item"><a class="page-link" href="?pagina=<?php echo $pag_ant; ?>"><?php echo $pag_ant; ?></a></li>
                    <?php endif; ?>
                <?php endfor; ?>
                <li class="page-item active"><a class="page-link" href="#"><?php echo $pagina; ?></a></li>
                <?php for ($pag_dep = $pagina + 1; $pag_dep <= $pagina + $max_links; $pag_dep++): ?>
                    <?php if ($pag_dep <= $quantidade_pg): ?>
                        <li class="page-item"><a class="page-link" href="?pagina=<?php echo $pag_dep; ?>"><?php echo $pag_dep; ?></a></li>
                    <?php endif; ?>
                <?php endfor; ?>
                <li class="page-item"><a class="page-link" href="?pagina=<?php echo $quantidade_pg; ?>">Última</a></li>
            </ul>
        </nav>
    </div>
    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="script.js"></script>
    <script>
        function voltarParaIndex() {
            if (confirm('Deseja realmente voltar?')) {
                window.location.href = 'index.php';
            }
        }
    </script>
</body>
</html>

<?php

use WHMCS\Database\Capsule;

if (!defined('WHMCS')) {
    exit('Acesso restrito');
}

function promocoes_thx_config()
{
    return [
        'name' => 'Promoções THX',
        'description' => 'Gerenciamento de campanhas e landing pages do Grupo THX.',
        'version' => '1.0.0',
        'author' => 'Grupo THX',
        'language' => 'portuguese-br',
    ];
}

function promocoes_thx_activate()
{
    try {
        if (!Capsule::schema()->hasTable('mod_promocoes_thx')) {
            Capsule::schema()->create('mod_promocoes_thx', function ($table) {
                $table->increments('id');
                $table->string('titulo', 180);
                $table->string('slug', 180)->unique();
                $table->text('descricao');
                $table->text('conteudo')->nullable();
                $table->string('imagem', 150)->nullable();
                $table->string('botao_texto', 100)->default('Solicitar proposta');
                $table->string('botao_url', 500)->nullable();
                $table->string('seo_titulo', 180)->nullable();
                $table->string('seo_descricao', 255)->nullable();
                $table->boolean('ativo')->default(true);
                $table->timestamps();
            });
        }
        return ['status' => 'success', 'description' => 'Módulo ativado. Nenhuma campanha foi enviada ou publicada automaticamente.'];
    } catch (\Throwable $e) {
        return ['status' => 'error', 'description' => 'Não foi possível criar a tabela do módulo: ' . $e->getMessage()];
    }
}

function promocoes_thx_deactivate()
{
    // Preserva as campanhas mesmo se o módulo for desativado.
    return ['status' => 'success', 'description' => 'Módulo desativado. Campanhas preservadas no banco.'];
}

function promocoes_thx_output($vars)
{
    $base = $vars['modulelink'];
    $esc = static function ($valor) {
        return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
    };

    if (empty($_SESSION['promocoes_thx_csrf'])) {
        $_SESSION['promocoes_thx_csrf'] = bin2hex(random_bytes(24));
    }
    $csrf = $_SESSION['promocoes_thx_csrf'];

    if (!Capsule::schema()->hasTable('mod_promocoes_thx')) {
        echo '<div class="alert alert-danger">Tabela do módulo não localizada. Ative o módulo em Configurações &gt; Módulos Adicionais.</div>';
        return;
    }

    $erro = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar') {
        if (!hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) {
            $erro = 'Sessão expirada. Atualize a página e tente novamente.';
        } else {
            $id = max(0, (int) ($_POST['promocao_id'] ?? 0));
            $titulo = trim((string) ($_POST['titulo'] ?? ''));
            $slug = strtolower(trim((string) ($_POST['slug'] ?? '')));
            $descricao = trim((string) ($_POST['descricao'] ?? ''));
            $imagem = trim((string) ($_POST['imagem'] ?? ''));
            $pastaImagens = __DIR__ . '/../../../thx/promocao/';
            $upload = $_FILES['nova_imagem'] ?? null;

            if ($titulo === '' || $slug === '' || $descricao === '') {
                $erro = 'Preencha título, URL amigável e descrição.';
            } elseif (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
                $erro = 'A URL amigável deve conter apenas letras minúsculas, números e hífens.';
            } elseif ($imagem !== '' && (!preg_match('/^[a-zA-Z0-9_-]+\.(?:jpg|jpeg|png|webp)$/i', $imagem) || !is_file($pastaImagens . $imagem))) {
                $erro = 'Escolha uma imagem existente na pasta thx/promocao.';
            } elseif ($upload && $upload['error'] !== UPLOAD_ERR_NO_FILE && $upload['error'] !== UPLOAD_ERR_OK) {
                $erro = 'Não foi possível receber a imagem.';
            } else {
                if ($upload && $upload['error'] === UPLOAD_ERR_OK) {
                    $tipos = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
                    $tipo = @exif_imagetype($upload['tmp_name']);
                    if ($upload['size'] > 5 * 1024 * 1024 || !isset($tipos[$tipo]) || !is_uploaded_file($upload['tmp_name'])) {
                        $erro = 'Envie uma imagem JPG, PNG ou WebP de até 5 MB.';
                    } else {
                        $novoNome = 'promo-' . bin2hex(random_bytes(8)) . '.' . $tipos[$tipo];
                        if (!move_uploaded_file($upload['tmp_name'], $pastaImagens . $novoNome)) {
                            $erro = 'Não foi possível salvar a imagem no servidor.';
                        } else {
                            $imagem = $novoNome;
                        }
                    }
                }
            }
            if ($erro === '') {
                $dados = [
                    'titulo' => mb_substr($titulo, 0, 180),
                    'slug' => mb_substr($slug, 0, 180),
                    'descricao' => $descricao,
                    'conteudo' => trim((string) ($_POST['conteudo'] ?? '')),
                    'imagem' => $imagem,
                    'seo_titulo' => mb_substr(trim((string) ($_POST['seo_titulo'] ?? '')), 0, 180),
                    'seo_descricao' => mb_substr(trim((string) ($_POST['seo_descricao'] ?? '')), 0, 255),
                    'ativo' => isset($_POST['ativo']) ? 1 : 0,
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                try {
                    $tabela = Capsule::table('mod_promocoes_thx');
                    $registro = $id ? $tabela->where('id', $id)->first() : null;
                    if (!$registro) {
                        $registro = $tabela->where('slug', $dados['slug'])->first();
                    }
                    if ($registro) {
                        $id = (int) $registro->id;
                        $tabela->where('id', $id)->update($dados);
                    } else {
                        $dados['created_at'] = date('Y-m-d H:i:s');
                        $id = $tabela->insertGetId($dados);
                    }
                    if ($erro === '') {
                        $_POST['promocao_id'] = $id;
                        echo '<div class="alert alert-success">Promoção salva com sucesso.</div>';
                    }
                } catch (\Throwable $e) {
                    $erro = 'Erro ao salvar. Consulte o Log de Atividades do WHMCS (Promoções THX).';
                    logActivity('Promoções THX: ' . $e->getMessage());
                }
            }
        }
    }

    echo '<h2>Promoções THX</h2><p>Cadastre campanhas para as landing pages e os e-mails. Nesta etapa, o cadastro não altera o envio de e-mails.</p>';

    if (isset($_GET['salvo'])) {
        echo '<div class="alert alert-success">Promoção salva com sucesso.</div>';
    }
    if ($erro !== '') {
        echo '<div class="alert alert-danger">' . $esc($erro) . '</div>';
    }

    $editId = max(0, (int) ($_POST['promocao_id'] ?? $_GET['editar'] ?? 0));
    $item = $editId ? Capsule::table('mod_promocoes_thx')->where('id', $editId)->first() : null;
    if (!$item) $editId = 0;
    $enviado = $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar';
    $val = static function ($campo, $padrao = '') use ($item, $esc, $enviado, $erro) {
        return $esc($enviado && $erro !== '' ? ($_POST[$campo] ?? $padrao) : ($item->$campo ?? $padrao));
    };

    echo '<h3>' . ($item ? 'Editar promoção' : 'Nova promoção') . '</h3>';
    echo '<form method="post" enctype="multipart/form-data" action="' . $esc($base) . '" class="form-horizontal">';
    echo '<input type="hidden" name="acao" value="salvar"><input type="hidden" name="csrf" value="' . $esc($csrf) . '">';
    echo '<input type="hidden" name="promocao_id" value="' . (int) ($item->id ?? $editId) . '">';

    $campo = static function ($rotulo, $nome, $valor, $tipo = 'text') use ($esc) {
        echo '<div class="form-group"><label class="col-sm-2 control-label">' . $esc($rotulo) . '</label><div class="col-sm-8">';
        if ($tipo === 'textarea') {
            echo '<textarea class="form-control" name="' . $esc($nome) . '"' . ($nome === 'conteudo' ? ' id="promocoes_conteudo" rows="16"' : ' rows="4"') . '>' . $valor . '</textarea>';
        } else {
            echo '<input class="form-control" type="text" name="' . $esc($nome) . '" value="' . $valor . '">';
        }
        echo '</div></div>';
    };

    $campo('Título', 'titulo', $val('titulo'));
    $campo('URL amigável', 'slug', $val('slug'));
    $campo('Descrição curta', 'descricao', $val('descricao'), 'textarea');
    $campo('Conteúdo da landing page', 'conteudo', $val('conteudo'), 'textarea');
    echo '<script src="../assets/js/tinymce/tinymce.min.js"></script>';
    echo '<script>
    (function () {
        function iniciarEditor() {
            if (!window.tinymce) return;
            tinymce.init({
                selector: "#promocoes_conteudo",
                height: 420,
                menubar: false,
                plugins: "link lists table code image",
                toolbar: "undo redo | styleselect | bold italic | bullist numlist | link image table | code",
                entity_encoding: "raw",
                relative_urls: false,
                convert_urls: false
            });
        }
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", iniciarEditor);
        } else {
            iniciarEditor();
        }
    })();
    </script>';

    $imagens = array_filter(glob(__DIR__ . '/../../../thx/promocao/*') ?: [], static function ($arquivo) {
        return is_file($arquivo) && preg_match('/^[a-zA-Z0-9_-]+\\.(jpg|jpeg|png|webp)$/i', basename($arquivo));
    });
    natsort($imagens);
    $imagemSelecionada = $enviado && $erro !== '' ? ($_POST['imagem'] ?? '') : ($item->imagem ?? '');
    echo '<div class="form-group"><label class="col-sm-2 control-label">Imagem</label><div class="col-sm-8">';
    echo '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;max-height:350px;overflow:auto;padding:8px;border:1px solid #ddd;border-radius:4px">';
    foreach ($imagens as $arquivo) {
        $nome = basename($arquivo);
        $url = '../thx/promocao/' . rawurlencode($nome);
        echo '<label style="border:1px solid #ddd;border-radius:5px;padding:7px;cursor:pointer;text-align:center">';
        echo '<img src="' . $esc($url) . '" alt="' . $esc($nome) . '" loading="lazy" style="width:100%;height:105px;object-fit:contain">';
        echo '<div><input type="radio" name="imagem" value="' . $esc($nome) . '"' . ($nome === $imagemSelecionada ? ' checked' : '') . '> ' . $esc($nome) . '</div></label>';
    }
    echo '</div><p class="help-block">Ou envie uma imagem nova (JPG, PNG ou WebP, até 5 MB):</p>';
    echo '<input type="file" name="nova_imagem" accept="image/jpeg,image/png,image/webp">';
    echo '</div></div>';

    $campo('SEO - Título', 'seo_titulo', $val('seo_titulo'));
    $campo('SEO - Descrição', 'seo_descricao', $val('seo_descricao'), 'textarea');

    echo '<div class="form-group"><div class="col-sm-offset-2 col-sm-8"><label><input type="checkbox" name="ativo" value="1"' . (($enviado && $erro !== '' ? isset($_POST['ativo']) : (!$item || $item->ativo)) ? ' checked' : '') . '> Campanha ativa</label></div></div>';
    echo '<div class="form-group"><div class="col-sm-offset-2 col-sm-8"><button type="submit" class="btn btn-primary">Salvar promoção</button> <a class="btn btn-default" href="' . $esc($base) . '">Nova promoção</a></div></div>';
    echo '</form><hr><h3>Promoções cadastradas</h3>';

    $registros = Capsule::table('mod_promocoes_thx')->orderBy('id', 'desc')->get();
    echo '<div class="table-responsive"><table class="table table-striped"><thead><tr><th>ID</th><th>Título</th><th>URL</th><th>Imagem</th><th>Status</th><th></th></tr></thead><tbody>';
    foreach ($registros as $registro) {
        echo '<tr><td>' . (int) $registro->id . '</td><td>' . $esc($registro->titulo) . '</td><td>' . $esc($registro->slug) . '</td><td>' . $esc($registro->imagem) . '</td><td>' . ($registro->ativo ? 'Ativa' : 'Inativa') . '</td><td><a class="btn btn-default btn-sm" href="' . $esc($base) . '&editar=' . (int) $registro->id . '">Editar</a></td></tr>';
    }
    echo '</tbody></table></div>';
}

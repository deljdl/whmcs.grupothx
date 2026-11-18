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
                $table->boolean('divulgar_email')->default(true);
                $table->unsignedBigInteger('acessos')->default(0);
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

function promocoes_thx_gravar_seo($arquivo, $conteudo)
{
    if (is_link($arquivo) || (file_exists($arquivo) && !is_file($arquivo))) {
        return false;
    }

    $tmp = @tempnam(dirname($arquivo), '.thx-seo-');
    if (!$tmp) return false;

    try {
        if (dirname($tmp) !== dirname($arquivo)) return false;
        $modo = is_file($arquivo) ? fileperms($arquivo) & 0777 : 0644;
        return @file_put_contents($tmp, $conteudo) === strlen($conteudo)
            && @chmod($tmp, $modo)
            && @rename($tmp, $arquivo);
    } finally {
        if (is_file($tmp)) @unlink($tmp);
    }
}

function promocoes_thx_gerar_seo($raiz, $baseUrl)
{
    $xmlEsc = static function ($valor) {
        return htmlspecialchars($valor, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    };
    $registros = Capsule::table('mod_promocoes_thx')
        ->where('ativo', 1)->orderBy('id', 'desc')->get(['slug', 'updated_at']);

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    $xml .= '  <url><loc>' . $xmlEsc($baseUrl) . '</loc></url>' . "\n";
    $total = 1;

    foreach ($registros as $registro) {
        $xml .= '  <url><loc>' . $xmlEsc($baseUrl . '/' . rawurlencode($registro->slug)) . '</loc>';
        if (!empty($registro->updated_at)) {
            $data = strtotime((string) $registro->updated_at);
            if ($data !== false) $xml .= '<lastmod>' . date('Y-m-d', $data) . '</lastmod>';
        }
        $xml .= '</url>' . "\n";
        $total++;
    }
    $xml .= '</urlset>' . "\n";

    if (!promocoes_thx_gravar_seo($raiz . '/thx/promocao/sitemap.xml', $xml)) {
        throw new \RuntimeException('Não foi possível gravar sitemap.xml.');
    }

    $robotsPath = $raiz . '/robots.txt';
    $robots = is_file($robotsPath) ? @file_get_contents($robotsPath) : "User-agent: *\nDisallow:\n";
    $sitemapUrl = $baseUrl . '/sitemap.xml';
    $robotsOk = is_string($robots) && !is_link($robotsPath);
    if ($robotsOk) {
        $pattern = '~^[ \t]*Sitemap:[ \t]*' . preg_quote($sitemapUrl, '~') . '[ \t]*(?:#[^\r\n]*)?\r?$~mi';
        if (!preg_match($pattern, $robots)) {
            $robots .= (substr($robots, -1) === "\n" ? '' : "\n") . "\nSitemap: " . $sitemapUrl . "\n";
            $robotsOk = promocoes_thx_gravar_seo($robotsPath, $robots);
        }
    }

    return ['total' => $total, 'robots' => $robotsOk];
}

function promocoes_thx_output($vars)
{
    require_once __DIR__ . '/../../../thx/promocao/cache.php';

    $base = $vars['modulelink'];
    $esc = static function ($valor) {
        return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
    };

    if (empty($_SESSION['promocoes_thx_csrf'])) {
        $_SESSION['promocoes_thx_csrf'] = bin2hex(random_bytes(24));
    }
    $csrf = $_SESSION['promocoes_thx_csrf'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'alterar_status') {
        $responder = static function ($dados, $status = 200) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');
            echo json_encode($dados);
            exit;
        };

        if (empty($_SESSION['adminid']) || !is_string($_POST['csrf'] ?? null) || !hash_equals($csrf, $_POST['csrf'])) {
            $responder(['success' => false, 'message' => 'Sessão inválida. Atualize a página.'], 403);
        }

        $id = (int) ($_POST['promocao_id'] ?? 0);
        $campo = $_POST['campo'] ?? '';
        $valor = $_POST['valor'] ?? '';
        if ($id <= 0 || !in_array($campo, ['ativo', 'divulgar_email'], true) || !in_array($valor, ['0', '1'], true)) {
            $responder(['success' => false, 'message' => 'Dados inválidos.'], 400);
        }

        try {
            $query = Capsule::table('mod_promocoes_thx')->where('id', $id);
            if (!$query->first()) {
                $responder(['success' => false, 'message' => 'Promoção não localizada.'], 404);
            }
            $query->update([$campo => (int) $valor, 'updated_at' => date('Y-m-d H:i:s')]);

            $aviso = '';
            if ($campo === 'ativo' && !thxPromoCacheClear()) {
                $aviso = 'Status salvo, mas não foi possível limpar o cache. Limpe-o manualmente.';
                logActivity('Promoções THX: falha ao invalidar o cache após alterar status.');
            }
            $responder(['success' => true, 'message' => $aviso ?: 'Alteração salva.', 'warning' => $aviso !== '']);
        } catch (\Throwable $e) {
            logActivity('Promoções THX: falha ao alterar status: ' . $e->getMessage());
            $responder(['success' => false, 'message' => 'Não foi possível salvar a alteração.'], 500);
        }
    }

    if (!Capsule::schema()->hasTable('mod_promocoes_thx')) {
        echo '<div class="alert alert-danger">Tabela do módulo não localizada. Ative o módulo em Configurações &gt; Módulos Adicionais.</div>';
        return;
    }

    $erro = '';
    $raizSeo = dirname(__DIR__, 3);
    $baseSeo = rtrim(HTTP_WHMCS, '/') . '/thx/promocao';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'gerar_seo') {
        if (empty($_SESSION['adminid']) || !is_string($_POST['csrf'] ?? null) || !hash_equals($csrf, $_POST['csrf'])) {
            $erro = 'Sessão expirada. Atualize a página e tente novamente.';
        } else {
            try {
                $seo = promocoes_thx_gerar_seo($raizSeo, $baseSeo);
                echo '<div class="alert alert-success">Sitemap gerado com ' . (int) $seo['total'] . ' URLs.</div>';
                if ($seo['robots']) {
                    echo '<div class="alert alert-success">Referência ao sitemap confirmada no robots.txt.</div>';
                } else {
                    echo '<div class="alert alert-warning">Sitemap gerado, mas não foi possível atualizar o robots.txt. Verifique o arquivo e suas permissões.</div>';
                }
            } catch (\Throwable $e) {
                $erro = 'Não foi possível gerar o sitemap. Consulte o Log de Atividades do WHMCS.';
                logActivity('Promoções THX: falha ao gerar SEO: ' . $e->getMessage());
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'limpar_cache') {
        if (empty($_SESSION['adminid']) || !hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) {
            $erro = 'Sessão expirada. Atualize a página e tente novamente.';
        } elseif (thxPromoCacheClear()) {
            echo '<div class="alert alert-success">Cache das promoções limpo com sucesso.</div>';
        } else {
            $erro = 'Não foi possível limpar o cache das promoções. Verifique as permissões do diretório temporário.';
        }
    }

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
                    $tipo = @getimagesize($upload['tmp_name'])[2] ?? false;
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
                    'conteudo' => html_entity_decode(trim((string) ($_POST['conteudo'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
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
                        if (!thxPromoCacheClear()) {
                            echo '<div class="alert alert-warning">A promoção foi salva, mas não foi possível invalidar o cache. Verifique as permissões ou tente limpar o cache manualmente.</div>';
                            logActivity('Promoções THX: falha ao invalidar o cache após salvar.');
                        }
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

    $cacheStats = thxPromoCacheStats();
    echo '<div class="panel panel-default"><div class="panel-heading"><strong>Cache HTML das promoções</strong></div><div class="panel-body">';
    if ($cacheStats === null) {
        echo '<p class="text-warning">Cache indisponível. As páginas continuam funcionando normalmente.</p>';
    } else {
        echo '<p>Arquivos armazenados: <strong>' . (int) $cacheStats['files'] . '</strong> &middot; Espaço utilizado: <strong>' . number_format($cacheStats['bytes'] / 1024, 1, ',', '.') . ' KB</strong></p>';
        echo '<p>O cache não possui prazo de expiração. É invalidado ao salvar uma promoção, alterar o layout ou limpar manualmente.</p>';
        echo '<form method="post" action="' . $esc($base) . '"><input type="hidden" name="acao" value="limpar_cache"><input type="hidden" name="csrf" value="' . $esc($csrf) . '"><button type="submit" class="btn btn-danger">Limpar todo o cache</button></form>';
    }
    echo '</div></div>';

    $sitemapPath = $raizSeo . '/thx/promocao/sitemap.xml';
    clearstatcache(true, $sitemapPath);
    $sitemapConteudo = is_file($sitemapPath) ? @file_get_contents($sitemapPath) : false;
    echo '<div class="panel panel-default"><div class="panel-heading"><strong>SEO — Sitemap e robots.txt</strong></div><div class="panel-body">';
    if (is_string($sitemapConteudo)) {
        echo '<p>Última geração: <strong>' . $esc(date('d/m/Y H:i:s', filemtime($sitemapPath))) . '</strong> &middot; URLs: <strong>' . substr_count($sitemapConteudo, '<url>') . '</strong></p>';
    } else {
        echo '<p>Sitemap ainda não gerado.</p>';
    }
    echo '<p>Arquivos estáticos, atualizados somente pelo botão abaixo. Gere novamente após cadastrar, editar, ativar ou desativar uma promoção. As regras existentes no robots.txt são preservadas.</p>';
    echo '<form method="post" action="' . $esc($base) . '"><input type="hidden" name="acao" value="gerar_seo"><input type="hidden" name="csrf" value="' . $esc($csrf) . '"><button type="submit" class="btn btn-primary">Gerar/Atualizar sitemap e robots.txt</button>';
    if (is_string($sitemapConteudo)) {
        echo ' <a target="_blank" rel="noopener" class="btn btn-default" href="' . $esc($baseSeo . '/sitemap.xml') . '">Visualizar sitemap.xml</a>';
    }
    if (is_file($raizSeo . '/robots.txt')) {
        echo ' <a target="_blank" rel="noopener" class="btn btn-default" href="' . $esc(rtrim(HTTP_WHMCS, '/') . '/robots.txt') . '">Visualizar robots.txt</a>';
    }
    echo '</form></div></div>';

    $editId = max(0, (int) ($_POST['promocao_id'] ?? $_GET['editar'] ?? 0));
    $item = $editId ? Capsule::table('mod_promocoes_thx')->where('id', $editId)->first() : null;
    if (!$item) $editId = 0;
    $enviado = $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar';
    $val = static function ($campo, $padrao = '') use ($item, $esc, $enviado, $erro) {
        return $esc($enviado && $erro !== '' ? ($_POST[$campo] ?? $padrao) : ($item->$campo ?? $padrao));
    };

    echo '<h3>' . ($item ? 'Editar promoção' : 'Nova promoção') . '</h3>';
    echo '<form id="promocoes_form" method="post" enctype="multipart/form-data" action="' . $esc($base) . '" class="form-horizontal">';
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
    echo '<script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>';
    echo '<script>
    (function() {
        function iniciarEditor() {
            if (!window.CKEDITOR) return;

            const campo = document.getElementById("promocoes_conteudo");
            const decoder = document.createElement("textarea");
            decoder.innerHTML = campo.value;
            campo.value = decoder.value;


            CKEDITOR.replace("promocoes_conteudo", {
                height: 420,
                allowedContent: true,
                versionCheck: false,
                entities: false,
                basicEntities: false,
                entities_latin: false,
                autoParagraph: false,
                enterMode: CKEDITOR.ENTER_BR,
                shiftEnterMode: CKEDITOR.ENTER_P
            });
            document.getElementById("promocoes_form").addEventListener("submit", function() {
                CKEDITOR.instances.promocoes_conteudo.updateElement();
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
    echo '<p>As promoções ativas e marcadas para divulgação têm prioridade nos e-mails. Se nenhuma promoção ativa estiver marcada, o sistema sorteará entre todas as promoções ativas.</p><div id="promocoes_status_msg" role="status" aria-live="polite"></div>';

    $registros = Capsule::table('mod_promocoes_thx')->orderBy('id', 'desc')->get();
    echo '<div class="table-responsive"><table class="table table-striped"><thead><tr><th>ID</th><th>Título</th><th>URL</th><th>Imagem</th><th>Acessos</th><th class="text-center">Ativa</th><th class="text-center">Divulgar nos e-mails</th><th></th></tr></thead><tbody>';
    foreach ($registros as $registro) {
        echo '<tr><td>' . (int) $registro->id . '</td><td>' . $esc($registro->titulo) . '</td><td><a target="_blank" href="'.HTTP_WHMCS.'/thx/promocao/' . $esc($registro->slug) . '">' . $esc($registro->slug) . '</a></td><td><img title="' . $esc($registro->imagem) . '" alt="' . $esc($registro->imagem) . '" src="../thx/promocao/' . rawurlencode($registro->imagem) . '" style="max-width:100px;max-height:100px" /></td><td>' . (isset($registro->acessos) ? number_format((int) $registro->acessos, 0, ',', '.') : '—') . '</td><td class="text-center"><input type="checkbox" class="promocao-status" data-id="' . (int) $registro->id . '" data-campo="ativo" aria-label="Campanha ativa: ' . $esc($registro->titulo) . '" style="cursor:pointer;width:18px;height:18px"' . ($registro->ativo ? ' checked' : '') . '></td><td class="text-center"><input type="checkbox" class="promocao-status" data-id="' . (int) $registro->id . '" data-campo="divulgar_email" aria-label="Divulgar nos e-mails: ' . $esc($registro->titulo) . '" style="cursor:pointer;width:18px;height:18px"' . ($registro->divulgar_email ? ' checked' : '') . '></td><td><a class="btn btn-default btn-sm" target="_blank" rel="noopener" href="' . $esc(rtrim(HTTP_WHMCS, '/') . '/thx/promocao/' . rawurlencode($registro->slug)) . '">Ver página</a> <a class="btn btn-default btn-sm" href="' . $esc($base) . '&editar=' . (int) $registro->id . '">Editar</a></td></tr>';
    }
    echo '</tbody></table></div>';

    echo '<script>
    (function() {
        const msg = document.getElementById("promocoes_status_msg");
        const form = document.getElementById("promocoes_form");
        document.querySelectorAll(".promocao-status").forEach(function(box) {
            box.addEventListener("change", async function() {
                const checked = box.checked;
                box.disabled = true;
                msg.className = "alert alert-info";
                msg.textContent = "Salvando...";
                try {
                    const data = new URLSearchParams({
                        acao: "alterar_status",
                        csrf: form.elements.csrf.value,
                        promocao_id: box.dataset.id,
                        campo: box.dataset.campo,
                        valor: checked ? "1" : "0"
                    });
                    const response = await fetch(form.action, {
                        method: "POST",
                        credentials: "same-origin",
                        headers: {"X-Requested-With": "XMLHttpRequest"},
                        body: data
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) throw new Error(result.message || "Erro ao salvar.");
                    if (box.dataset.campo === "ativo" && form.elements.promocao_id.value === box.dataset.id) {
                        form.elements.ativo.checked = checked;
                    }
                    msg.className = "alert alert-" + (result.warning ? "warning" : "success");
                    msg.textContent = result.message;
                } catch (e) {
                    box.checked = !checked;
                    msg.className = "alert alert-danger";
                    msg.textContent = "Não foi possível confirmar a alteração. Atualize a página para verificar o status.";
                } finally {
                    box.disabled = false;
                }
            });
        });
    })();
    </script>';
}

#!/usr/bin/env bash
# PADRAO DE ATUALIZACAO WHMCS - Grupo THX
# 1) bash thx/atualizar-whmcs.sh preparar 9.1.3
# 2) Atualizar pelo painel WHMCS (Update Now); verificar a versao instalada.
# 3) bash thx/atualizar-whmcs.sh finalizar 9.1.3
# Opcional: bash thx/atualizar-whmcs.sh conferir
# O diretorio do site SEMPRE permanece na branch staging.
# O deploy acontece apenas depois de digitar PUBLICAR na finalizacao.
# Se o fluxo oficial do WHMCS mudar, revisar este script antes de usar.
set -euo pipefail
umask 077

USO='Uso: bash thx/atualizar-whmcs.sh {preparar VERSAO|conferir|finalizar VERSAO}'
COMANDO="${1:-}"
VERSAO="${2:-}"

if [[ "$COMANDO" != preparar && "$COMANDO" != conferir && "$COMANDO" != finalizar ]]; then
    echo "$USO"
    exit 1
fi
if [[ "$COMANDO" != conferir && ! "$VERSAO" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo "$USO" >&2
    exit 1
fi

cd "$(dirname "${BASH_SOURCE[0]}")/.."
RAIZ="$PWD"
if [[ "$(git branch --show-current)" != staging ]]; then
    echo 'ERRO: o site deve permanecer na branch staging.' >&2
    exit 1
fi

DESTINO="$HOME/backups/whmcs"
ESTADO="$DESTINO/.atualizacao-whmcs-$VERSAO"
BRANCH="upgrade-whmcs-$VERSAO"

case "$COMANDO" in
    preparar)
        if [[ -f "$ESTADO/base" ]]; then
            echo "ERRO: preparacao desta versao ja registrada em $ESTADO" >&2
            exit 1
        fi
        if ! git diff --cached --quiet; then
            echo 'ERRO: existem alteracoes no Git ja preparadas (staged). Verifique-as antes.' >&2
            exit 1
        fi
        git fetch -q origin staging
        BASE="$(git rev-parse HEAD)"
        if [[ "$BASE" != "$(git rev-parse origin/staging)" ]]; then
            echo 'ERRO: staging local e origin/staging nao estao sincronizadas.' >&2
            exit 1
        fi
        REMOTA="$(git ls-remote --heads origin "refs/heads/$BRANCH" | cut -f1)"
        if [[ -n "$REMOTA" && "$REMOTA" != "$BASE" ]]; then
            echo "ERRO: $BRANCH ja existe em outro commit. Nenhuma branch alterada." >&2
            exit 1
        fi

        DATA="$(date +%Y%m%d-%H%M%S)"
        mkdir -p "$DESTINO"
        ARQUIVOS="$DESTINO/whmcs-files-$DATA.tar.gz"
        BANCO="$DESTINO/whmcs-db-$DATA.sql.gz"
        echo 'Criando backup dos arquivos...'
        tar -czpf "$ARQUIVOS" -C "$(dirname "$RAIZ")" "$(basename "$RAIZ")"
        tar -tzf "$ARQUIVOS" > /dev/null

        echo 'Criando backup do MySQL (informe a senha quando solicitada)...'
        DADOS="$(php -r 'require $argv[1]; echo $db_host, "\n", $db_username, "\n", $db_name;' "$RAIZ/configuration.php")"
        mapfile -t DB <<< "$DADOS"
        if [[ "${#DB[@]}" -ne 3 ]]; then
            echo 'ERRO: dados de conexao MySQL nao identificados.' >&2
            exit 1
        fi
        if ! mysqldump -h "${DB[0]}" -u "${DB[1]}" -p \
            --single-transaction --quick --no-tablespaces \
            --routines --triggers --events "${DB[2]}" | gzip > "$BANCO"; then
            rm -f "$BANCO"
            echo 'ERRO: backup do banco falhou.' >&2
            exit 1
        fi
        gzip -t "$BANCO"
        ls -lh "$ARQUIVOS" "$BANCO"

        if [[ -z "$REMOTA" ]]; then
            git push origin "HEAD:refs/heads/$BRANCH"
        fi
        mkdir -p "$ESTADO"
        printf '%s\n' "$BASE" > "$ESTADO/base"
        git diff --name-only > "$ESTADO/modificados-antes.txt"
        git ls-files --others --exclude-standard | sort > "$ESTADO/novos-antes.txt"
        sha256sum crons/pipe.php | cut -d " " -f1 > "$ESTADO/pipe-antes.sha256"
        echo "Preparacao concluida: $BRANCH criada/validada e staging preservada."
        echo 'Agora atualize pelo painel WHMCS e, depois, execute finalizar.'
        ;;
    conferir)
        echo "Branch: $(git branch --show-current)"
        echo "Commit: $(git rev-parse --short HEAD)"
        git diff --shortstat
        git status --short | sed -n '1,40p'
        echo 'Somente leitura. Nenhum commit ou deploy executado.'
        ;;
    finalizar)
        if [[ ! -f "$ESTADO/base" ]]; then
            echo "ERRO: execute preparar $VERSAO antes. Nao encontrei o registro da preparacao." >&2
            exit 1
        fi
        BASE="$(cat "$ESTADO/base")"
        git fetch -q origin staging
        if [[ "$(git rev-parse HEAD)" != "$BASE" || "$(git rev-parse origin/staging)" != "$BASE" ]]; then
            echo 'ERRO: staging mudou desde a preparacao. Nao vou criar commit nem publicar.' >&2
            exit 1
        fi
        REMOTA="$(git ls-remote --heads origin "refs/heads/$BRANCH" | cut -f1)"
        if [[ "$REMOTA" != "$BASE" ]]; then
            echo "ERRO: $BRANCH mudou desde a preparacao. Nenhuma alteracao efetuada." >&2
            exit 1
        fi
        if ! git diff --cached --quiet; then
            echo 'ERRO: existem alteracoes ja preparadas (staged). Verifique antes.' >&2
            exit 1
        fi
        if [[ "$(sha256sum crons/pipe.php | cut -d " " -f1)" != "$(cat "$ESTADO/pipe-antes.sha256")" ]]; then
            echo 'ERRO: crons/pipe.php mudou durante a atualizacao. Revisar para nao perder personalizacoes.' >&2
            exit 1
        fi

        # Preservar logos personalizados exatamente como na staging anterior.
        git restore --source=HEAD -- admin/templates/blend/images/logo.png assets/img/whmcs.png
        # Registrar somente arquivos que ja eram versionados.
        git add -u
        # Nao incluir alteracoes locais anteriores ao update.
        while IFS= read -r CAMINHO; do
            if [[ -n "$CAMINHO" ]]; then
                git restore --staged -- "$CAMINHO"
            fi
        done < "$ESTADO/modificados-antes.txt"
        git restore --staged -- crons/pipe.php

        if git diff --cached --quiet; then
            echo 'Nenhuma alteracao versionada para registrar.'
            exit 1
        fi
        echo "Arquivos selecionados para o commit WHMCS $VERSAO:"
        git diff --cached --shortstat
        echo 'Arquivos nao versionados NOVOS desde a preparacao (nao entram no commit):'
        comm -13 "$ESTADO/novos-antes.txt" <(git ls-files --others --exclude-standard | sort) | sed -n '1,30p'
        echo 'Alteracoes locais preservadas fora do commit:'
        git diff --name-only | sed -n '1,30p'
        echo 'Confira a lista acima. Novos arquivos oficiais precisam ser analisados separadamente.'
        echo "Digite PUBLICAR para criar o commit, enviar a $BRANCH e disparar o deploy da staging."
        read -r -p 'Confirmacao: ' CONFIRMACAO
        if [[ "$CONFIRMACAO" != PUBLICAR ]]; then
            git restore --staged .
            echo 'Cancelado: nenhum commit ou push realizado.'
            exit 1
        fi
        git commit -m "Atualiza WHMCS para $VERSAO"
        git push origin "HEAD:refs/heads/$BRANCH"
        git push origin staging
        echo "WHMCS $VERSAO registrado nas duas branches. Confira o resultado do deploy no GitHub Actions."
        ;;
esac

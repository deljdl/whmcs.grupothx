#!/usr/bin/env bash
# Uso:
#   bash thx/atualizar-whmcs.sh preparar 9.1.3
#   [Atualize no painel WHMCS]
#   bash thx/atualizar-whmcs.sh conferir
# Nunca troca a branch staging ou faz commit/deploy automaticamente.
set -euo pipefail
umask 077

cd "$(dirname "${BASH_SOURCE[0]}")/.."
RAIZ="$PWD"

if [[ "$(git branch --show-current)" != staging ]]; then
    echo 'ERRO: o site precisa estar na branch staging.' >&2
    exit 1
fi

case "${1:-}" in
    preparar)
        VERSAO="${2:-}"
        if [[ ! "$VERSAO" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
            echo 'Uso: bash thx/atualizar-whmcs.sh preparar 9.1.3' >&2
            exit 1
        fi

        git fetch -q origin staging
        BASE="$(git rev-parse HEAD)"
        if [[ "$BASE" != "$(git rev-parse origin/staging)" ]]; then
            echo 'ERRO: staging local e origin/staging nao estao sincronizadas.' >&2
            exit 1
        fi

        BRANCH="upgrade-whmcs-$VERSAO"
        REMOTA="$(git ls-remote --heads origin "refs/heads/$BRANCH" | cut -f1)"
        if [[ -n "$REMOTA" && "$REMOTA" != "$BASE" ]]; then
            echo "ERRO: $BRANCH ja existe com outro commit. Nao alterei a branch." >&2
            exit 1
        fi

        DESTINO="$HOME/backups/whmcs"
        DATA="$(date +%Y%m%d-%H%M%S)"
        mkdir -p "$DESTINO"
        ARQUIVOS="$DESTINO/whmcs-files-$DATA.tar.gz"
        BANCO="$DESTINO/whmcs-db-$DATA.sql.gz"

        echo 'Backup dos arquivos...'
        tar -czpf "$ARQUIVOS" -C "$(dirname "$RAIZ")" "$(basename "$RAIZ")"
        tar -tzf "$ARQUIVOS" > /dev/null

        echo 'Backup do banco (informe a senha do MySQL)...'
        DADOS="$(php -r 'require $argv[1]; echo $db_host, "\n", $db_username, "\n", $db_name;' "$RAIZ/configuration.php")"
        mapfile -t DB <<< "$DADOS"
        if [[ "${#DB[@]}" -ne 3 ]]; then
            echo 'ERRO: configuracao MySQL nao identificada.' >&2
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
        else
            echo "Branch $BRANCH ja existe no commit correto."
        fi
        echo 'PRONTO: staging preservada. Agora atualize pelo painel WHMCS.'
        ;;
    conferir)
        echo 'Branch: staging'
        echo "Commit: $(git rev-parse --short HEAD)"
        echo 'Resumo das alteracoes versionadas:'
        git diff --shortstat
        echo 'Primeiros itens do git status:'
        git status --short | sed -n '1,40p'
        echo 'Nenhum commit ou deploy foi executado.'
        ;;
    *)
        echo 'Uso: bash thx/atualizar-whmcs.sh {preparar VERSAO|conferir}'
        exit 1
        ;;
esac

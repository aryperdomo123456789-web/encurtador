# Prompt para o Lovable

Use este texto para continuar o trabalho sem ambiguidade:

---

Voce vai continuar o repositorio `aryperdomo123456789-web/encurtador` com foco em producao.

Contexto atual:

- `api-shlink.vr766.com` e o motor principal do repo.
- `me.vr766.com` e o host ativo do painel Laravel.
- `/www/wwwroot/me.vr766.com` esta reservado para o futuro segundo projeto e nao deve ser usado pelo deploy atual.
- O painel atual deve apontar para `panel/laravel/public`.

Objetivo do proximo passo:

1. Manter o painel Laravel em producao sem 404.
2. Separar claramente o motor Shlink do futuro projeto `me.vr766.com`.
3. Documentar a operacao no aaPanel/Nginx.
4. Nao misturar arquivos publicos, secrets ou builds entre os ambientes.

Regras de arquitetura:

- Nao usar `/www/wwwroot/me.vr766.com` como document root do painel atual.
- Usar `panel/laravel/public` como raiz do site atual.
- Garantir permissao de escrita apenas em `storage/`, `bootstrap/cache/` e no banco local.
- Manter `.env` e logs fora do git.
- Nao quebrar o host de slugs publicos.

Checklist de acao:

- Ler `docs/operacao-shlink.md`.
- Ler `docs/sections/me-vr766-com.md`.
- Respeitar o backlog de `docs/lovable/checklist.md`.
- Se precisar criar o futuro `me.vr766.com`, usar `public/` como root do novo app.

Resultado esperado:

- deploy previsivel;
- docs claras;
- nenhum 404 no painel;
- separacao limpa entre o motor Shlink e o futuro site.


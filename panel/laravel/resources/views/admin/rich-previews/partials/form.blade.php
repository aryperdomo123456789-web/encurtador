<section class="card section">
    <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" data-rich-preview-form>
        @csrf
        @if ($formMethod !== 'POST')
            @method($formMethod)
        @endif

        <div class="grid cards-3">
            <div class="card" style="box-shadow:none;">
                <div class="field">
                    <label for="title">Título</label>
                    <input id="title" type="text" name="title" maxlength="120" value="{{ old('title', $richPreview->title ?? '') }}" placeholder="Ex: Oferta de hoje">
                </div>

                <div class="field">
                    <label for="campaign_name">Campanha</label>
                    <input id="campaign_name" type="text" name="campaign_name" maxlength="120" value="{{ old('campaign_name', $richPreview->campaign_name ?? '') }}" placeholder="Ex: Lançamento agosto">
                </div>

                <div class="field">
                    <label for="category_name">Categoria</label>
                    <input id="category_name" type="text" name="category_name" maxlength="120" value="{{ old('category_name', $richPreview->category_name ?? '') }}" placeholder="Ex: Vendas, tráfego, indicação">
                </div>

                <div class="field">
                    <label for="slug">Slug público</label>
                    <input id="slug" type="text" name="slug" maxlength="90" value="{{ old('slug', $richPreview->slug ?? '') }}" placeholder="Ex: oferta-hoje" data-slug-input>
                    <p class="hint">Se ficar vazio, o sistema gera automaticamente. Você pode colar em maiúsculas ou com espaços; o painel normaliza.</p>
                </div>

                <div class="field">
                    <label for="destination_url">Destino final</label>
                    <input id="destination_url" type="url" name="destination_url" maxlength="2048" value="{{ old('destination_url', $richPreview->destination_url ?? '') }}" placeholder="https://seusite.com/oferta">
                </div>

                <div class="field">
                    <label for="cta_label">Texto do botão</label>
                    <input id="cta_label" type="text" name="cta_label" maxlength="50" value="{{ old('cta_label', $richPreview->cta_label ?? 'Abrir link') }}" placeholder="Abrir link">
                </div>
            </div>

            <div class="card" style="box-shadow:none;">
                <div class="field">
                    <label for="description">Descrição</label>
                    <textarea id="description" name="description" maxlength="500" rows="8" placeholder="Texto curto que aparece na página de prévia">{{ old('description', $richPreview->description ?? '') }}</textarea>
                </div>

                <div class="field">
                    <label for="image_upload">Imagem do preview</label>
                    <div class="paste-zone" tabindex="0" aria-label="Cole uma imagem do clipboard nesta area" data-image-paste-zone>
                        <strong>Cole uma imagem aqui</strong>
                        <span>Use Ctrl+V para colar do clipboard. Se preferir, selecione um arquivo logo abaixo.</span>
                    </div>
                    <input id="image_upload" type="file" name="image_upload" accept="image/*" data-image-upload-input>
                    <p class="hint">Envie PNG, JPG, WEBP. Funciona melhor em formato horizontal.</p>
                </div>

                <div class="field">
                    <label for="image_url">Ou cole uma URL de imagem</label>
                    <input id="image_url" type="url" name="image_url" maxlength="2048" value="{{ old('image_url', $richPreview->image_url ?? '') }}" placeholder="https://...">
                </div>
            </div>

            <div class="card" style="box-shadow:none;">
                <div class="mini-card" style="margin-bottom:16px;">
                    <img
                        src="{{ $richPreview->imageUrl() }}"
                        alt="Prévia atual"
                        data-image-preview
                        style="width:100%; max-width:320px; aspect-ratio: 1.91 / 1; object-fit:cover; border-radius:18px; background:#fff;"
                    >
                </div>

                <label class="remember">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $richPreview->is_active ?? true) ? 'checked' : '' }}>
                    Publicar rich preview
                </label>

                @if(($richPreview->exists ?? false))
                    <label class="remember" style="margin-top:10px;">
                        <input type="checkbox" name="reset_image" value="1">
                        Remover imagem enviada e voltar ao padrão
                    </label>
                @endif

                <div class="alert" style="margin-top:16px;">
                    <strong>Como funciona</strong>
                    <p style="margin-top:8px;">
                        O link público abre esta página com Open Graph. Ao tocar na imagem ou no título,
                        o visitante é enviado para o destino final.
                    </p>
                </div>
            </div>
        </div>

        <div class="hero-actions" style="margin-top: 18px;">
            <button type="submit" class="primary">{{ $buttonLabel }}</button>
            <a class="button secondary" href="{{ route('admin.rich-previews.index') }}">Cancelar</a>
        </div>
    </form>
</section>

<script>
(() => {
    const form = document.querySelector('[data-rich-preview-form]');

    if (!form) {
        return;
    }

    const pasteZone = form.querySelector('[data-image-paste-zone]');
    const fileInput = form.querySelector('[data-image-upload-input]');
    const slugInput = form.querySelector('[data-slug-input]');
    const urlInput = form.querySelector('#image_url');
    const preview = form.querySelector('[data-image-preview]');

    if (!pasteZone || !fileInput) {
        return;
    }

    const zoneTitle = pasteZone.querySelector('strong');
    const zoneHint = pasteZone.querySelector('span');
    const originalPreviewSrc = preview ? preview.getAttribute('src') : '';
    const defaultZoneTitle = zoneTitle ? zoneTitle.textContent : '';
    const defaultZoneHint = zoneHint ? zoneHint.textContent : '';
    const normalizeSlug = (value) => value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .replace(/-+/g, '-');

    const resetZoneText = () => {
        pasteZone.classList.remove('is-filled');

        if (zoneTitle) {
            zoneTitle.textContent = defaultZoneTitle;
        }

        if (zoneHint) {
            zoneHint.textContent = defaultZoneHint;
        }
    };

    const setPreview = (file) => {
        if (!preview || !file) {
            return;
        }

        const objectUrl = URL.createObjectURL(file);
        preview.src = objectUrl;
        preview.onload = () => {
            URL.revokeObjectURL(objectUrl);
        };
    };

    const setFile = (file) => {
        if (!file || !file.type || !file.type.startsWith('image/')) {
            return false;
        }

        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        fileInput.files = dataTransfer.files;

        if (urlInput) {
            urlInput.value = '';
        }

        setPreview(file);
        pasteZone.classList.add('is-filled');
        if (zoneTitle) {
            zoneTitle.textContent = 'Imagem pronta para envio';
        }

        if (zoneHint) {
            zoneHint.textContent = file.name ? `Arquivo colado: ${file.name}` : 'Imagem colada do clipboard.';
        }

        return true;
    };

    const handlePaste = (event) => {
        const clipboardItems = event.clipboardData?.items;

        if (!clipboardItems || clipboardItems.length === 0) {
            return;
        }

        for (const item of clipboardItems) {
            if (item.kind === 'file' && item.type.startsWith('image/')) {
                const file = item.getAsFile();

                if (file && setFile(file)) {
                    event.preventDefault();
                }

                break;
            }
        }
    };

    pasteZone.addEventListener('click', () => pasteZone.focus());
    pasteZone.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            pasteZone.focus();
        }
    });
    pasteZone.addEventListener('paste', handlePaste);
    form.addEventListener('paste', (event) => {
        if (document.activeElement && !form.contains(document.activeElement)) {
            return;
        }

        handlePaste(event);
    });

    if (slugInput) {
        slugInput.addEventListener('blur', () => {
            const normalized = normalizeSlug(slugInput.value);

            if (normalized !== '') {
                slugInput.value = normalized;
            }
        });

        slugInput.addEventListener('paste', () => {
            window.setTimeout(() => {
                const normalized = normalizeSlug(slugInput.value);

                if (normalized !== '') {
                    slugInput.value = normalized;
                }
            }, 0);
        });
    }

    fileInput.addEventListener('change', () => {
        const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;

        if (!file) {
            resetZoneText();
            if (preview && originalPreviewSrc) {
                preview.src = originalPreviewSrc;
            }
            return;
        }

        setPreview(file);
        pasteZone.classList.add('is-filled');
        if (zoneTitle) {
            zoneTitle.textContent = 'Imagem selecionada';
        }

        if (zoneHint) {
            zoneHint.textContent = file.name ? `Arquivo: ${file.name}` : 'Arquivo escolhido manualmente.';
        }

        if (urlInput) {
            urlInput.value = '';
        }
    });
})();
</script>

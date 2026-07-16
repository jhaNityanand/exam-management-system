{{-- Shared image editor modal (gallery, banners, editor uploads) --}}
<div id="gallery-image-editor" class="gallery-editor-modal" hidden aria-hidden="true">
    <div class="gallery-editor-modal__backdrop" data-gie-close></div>
    <div class="gallery-editor-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="gallery-editor-title">
        <header class="gallery-editor-modal__header">
            <div class="min-w-0">
                <h3 id="gallery-editor-title" class="gallery-editor-modal__title" data-gie-title>Edit image</h3>
                <p class="gallery-editor-modal__meta" data-gie-meta></p>
            </div>
            <button type="button" class="gallery-icon-btn" data-gie-close aria-label="Close editor">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </header>
        <div class="gallery-editor-modal__body">
            <div class="gallery-editor-modal__stage" data-gie-stage>
                <img data-gie-image alt="Edit preview" />
            </div>
            <aside class="gallery-editor-modal__panel">
                <div class="gallery-editor-tools">
                    <p class="gallery-editor-label">Transform</p>
                    <div class="gallery-editor-toolrow">
                        <button type="button" data-gie-action="rotate-left" title="Rotate left">Rot L</button>
                        <button type="button" data-gie-action="rotate-right" title="Rotate right">Rot R</button>
                        <button type="button" data-gie-action="flip-h" title="Flip horizontal">Flip H</button>
                        <button type="button" data-gie-action="flip-v" title="Flip vertical">Flip V</button>
                        <button type="button" data-gie-action="reset" title="Reset">Reset</button>
                    </div>

                    <p class="gallery-editor-label">Shape</p>
                    <div class="gallery-editor-toolrow" data-gie-shapes>
                        <button type="button" data-shape="rectangle" class="is-active">Rectangle</button>
                        <button type="button" data-shape="square">Square</button>
                        <button type="button" data-shape="circle">Circle</button>
                    </div>

                    <label class="gallery-editor-field">
                        <span>Brightness <strong data-gie-brightness-val>0</strong></span>
                        <input type="range" min="-40" max="40" step="1" value="0" data-gie-brightness>
                    </label>

                    <label class="gallery-editor-field">
                        <span>Contrast <strong data-gie-contrast-val>0</strong></span>
                        <input type="range" min="-40" max="40" step="1" value="0" data-gie-contrast>
                    </label>

                    <label class="gallery-editor-field">
                        <span>Quality <strong data-gie-quality-val>85%</strong></span>
                        <input type="range" min="40" max="100" step="1" value="85" data-gie-quality>
                    </label>

                    <div class="gallery-editor-size">
                        <label class="gallery-editor-field">
                            <span>Width</span>
                            <input type="number" min="1" data-gie-width class="panel-input text-sm">
                        </label>
                        <label class="gallery-editor-field">
                            <span>Height</span>
                            <input type="number" min="1" data-gie-height class="panel-input text-sm">
                        </label>
                    </div>
                    <label class="gallery-editor-check">
                        <input type="checkbox" data-gie-lock-ratio checked>
                        <span>Lock aspect ratio</span>
                    </label>

                    <div class="gallery-editor-preview-wrap" data-gie-preview-wrap hidden>
                        <p class="gallery-editor-label">Export preview</p>
                        <img data-gie-preview alt="Edited preview" class="gallery-editor-preview">
                    </div>
                </div>

                <div class="gallery-editor-actions">
                    <button type="button" class="gallery-action-btn" data-gie-action="preview">Preview</button>
                    <button type="button" class="gallery-action-btn" data-gie-action="skip">Keep original</button>
                    <button type="button" class="panel-button-primary" data-gie-action="save">Save edited</button>
                </div>
            </aside>
        </div>
    </div>
</div>

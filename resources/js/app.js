document.addEventListener('alpine:init', () => {
    Alpine.data('codePlayground', (starterCode, solutionCode) => ({
        editorView: null,
        debounceTimer: null,

        async init() {
            const [{ EditorView, basicSetup }, { html }, { oneDark }] = await Promise.all([
                import('codemirror'),
                import('@codemirror/lang-html'),
                import('@codemirror/theme-one-dark'),
            ]);

            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

            this.editorView = new EditorView({
                doc: starterCode,
                extensions: [
                    basicSetup,
                    html(),
                    ...(prefersDark ? [oneDark] : []),
                    EditorView.updateListener.of((update) => {
                        if (update.docChanged) {
                            this.scheduleRun();
                        }
                    }),
                ],
                parent: this.$refs.editor,
            });

            this.run();
        },

        scheduleRun() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => this.run(), 400);
        },

        run() {
            this.$refs.preview.srcdoc = this.editorView.state.doc.toString();
        },

        resetCode() {
            this.setCode(starterCode);
        },

        loadSolution() {
            this.setCode(solutionCode ?? '');
        },

        setCode(value) {
            this.editorView.dispatch({
                changes: { from: 0, to: this.editorView.state.doc.length, insert: value },
            });
            this.run();
        },

        destroy() {
            this.editorView?.destroy();
        },
    }));
});

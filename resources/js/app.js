function buildGradingScript(checks) {
    return `\n<script>(function () {\n`
        + `var __checks = ${JSON.stringify(checks)};\n`
        + `var __alertLog = [];\n`
        + `window.alert = function (msg) { __alertLog.push(String(msg)); };\n`
        + `function __normalize(prop, value) {\n`
        + `  var probe = document.createElement('div');\n`
        + `  probe.style.cssText = prop + ':' + value + ';display:none;';\n`
        + `  document.body.appendChild(probe);\n`
        + `  var normalized = getComputedStyle(probe)[prop];\n`
        + `  probe.remove();\n`
        + `  return normalized;\n`
        + `}\n`
        + `function __run() {\n`
        + `  var results = __checks.map(function (check) {\n`
        + `    try {\n`
        + `      if (check.type === 'text') {\n`
        + `        var el = document.querySelector(check.selector);\n`
        + `        var pass = !!el && el.textContent.indexOf(check.expected) !== -1;\n`
        + `        return { pass: pass, selector: check.selector };\n`
        + `      }\n`
        + `      if (check.type === 'style') {\n`
        + `        var el = document.querySelector(check.selector);\n`
        + `        if (!el) return { pass: false, selector: check.selector };\n`
        + `        var actual = getComputedStyle(el)[check.property];\n`
        + `        var expected = __normalize(check.property, check.expected);\n`
        + `        return { pass: actual === expected, selector: check.selector };\n`
        + `      }\n`
        + `      if (check.type === 'alert') {\n`
        + `        var el = document.querySelector(check.selector);\n`
        + `        if (!el) return { pass: false, selector: check.selector };\n`
        + `        __alertLog = [];\n`
        + `        el.click();\n`
        + `        var pass = __alertLog.some(function (m) { return m.indexOf(check.expected) !== -1; });\n`
        + `        return { pass: pass, selector: check.selector };\n`
        + `      }\n`
        + `      return { pass: false, selector: check.selector || '' };\n`
        + `    } catch (e) {\n`
        + `      return { pass: false, selector: check.selector || '' };\n`
        + `    }\n`
        + `  });\n`
        + `  window.parent.postMessage({ __exerciseGrading: true, results: results }, '*');\n`
        + `}\n`
        + `window.addEventListener('load', function () { setTimeout(__run, 50); });\n`
        + `})();<\/script>\n`;
}

document.addEventListener('alpine:init', () => {
    Alpine.data('codePlayground', (starterCode, solutionCode, checks) => ({
        editorView: null,
        debounceTimer: null,
        checks: checks || [],
        checking: false,
        checkResults: null,
        gradingListener: null,

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
            this.checkResults = null;
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => this.run(), 400);
        },

        run() {
            this.$refs.preview.srcdoc = this.editorView.state.doc.toString();
        },

        runChecks() {
            if (! this.checks.length) {
                return;
            }

            this.checking = true;
            this.checkResults = null;

            if (this.gradingListener) {
                window.removeEventListener('message', this.gradingListener);
            }

            this.gradingListener = (event) => {
                if (event.source !== this.$refs.preview.contentWindow) {
                    return;
                }
                if (! event.data || ! event.data.__exerciseGrading) {
                    return;
                }

                window.removeEventListener('message', this.gradingListener);
                this.gradingListener = null;
                this.checking = false;
                this.checkResults = event.data.results;

                if (this.checkResults.every((r) => r.pass) && this.$wire) {
                    this.$wire.call('submitExercise');
                }
            };

            window.addEventListener('message', this.gradingListener);

            const doc = this.editorView.state.doc.toString();
            this.$refs.preview.srcdoc = doc + buildGradingScript(this.checks);
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
            this.checkResults = null;
            this.run();
        },

        destroy() {
            this.editorView?.destroy();

            if (this.gradingListener) {
                window.removeEventListener('message', this.gradingListener);
            }
        },
    }));
});

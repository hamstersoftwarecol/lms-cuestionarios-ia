/**
 * Motor del cuestionario en el navegador: navegación, selección MCQ/SATA,
 * temporizador con envío automático y atajos de teclado (1-6, ←, →).
 * Las respuestas correctas nunca llegan al cliente; se corrige en el servidor.
 */
export function quizRunner({ questions, remainingSeconds = null }) {
    return {
        questions,
        index: 0,
        answers: {},
        remaining: remainingSeconds,
        submitting: false,
        timer: null,

        init() {
            this.questions.forEach((q) => (this.answers[q.id] = []));

            if (this.remaining !== null) {
                if (this.remaining <= 0) return this.submit();
                this.timer = setInterval(() => {
                    this.remaining--;
                    if (this.remaining <= 0) {
                        clearInterval(this.timer);
                        this.submit();
                    }
                }, 1000);
            }

            window.addEventListener('beforeunload', (event) => {
                if (!this.submitting && this.answeredCount > 0) {
                    event.preventDefault();
                    event.returnValue = '';
                }
            });

            window.addEventListener('keydown', (event) => {
                if (['INPUT', 'TEXTAREA', 'SELECT'].includes(event.target.tagName)) return;
                const number = parseInt(event.key, 10);
                if (number >= 1 && number <= this.current.options.length) this.toggle(number - 1);
                if (event.key === 'ArrowRight') this.next();
                if (event.key === 'ArrowLeft') this.prev();
            });
        },

        get current() {
            return this.questions[this.index];
        },

        get isLast() {
            return this.index === this.questions.length - 1;
        },

        get answeredCount() {
            return Object.values(this.answers).filter((a) => a.length > 0).length;
        },

        get progress() {
            return Math.round((this.answeredCount / this.questions.length) * 100);
        },

        get clock() {
            if (this.remaining === null) return null;
            const m = Math.floor(this.remaining / 60);
            const s = this.remaining % 60;
            return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        },

        isSelected(option) {
            return this.answers[this.current.id].includes(option);
        },

        isAnswered(question) {
            return this.answers[question.id].length > 0;
        },

        toggle(option) {
            const id = this.current.id;
            if (this.current.type === 'mcq') {
                this.answers[id] = [option];
            } else {
                this.answers[id] = this.isSelected(option)
                    ? this.answers[id].filter((o) => o !== option)
                    : [...this.answers[id], option].sort();
            }
        },

        next() {
            if (!this.isLast) this.index++;
        },

        prev() {
            if (this.index > 0) this.index--;
        },

        goTo(i) {
            this.index = i;
        },

        confirmSubmit() {
            const pending = this.questions.length - this.answeredCount;
            if (pending > 0 && !window.confirm(`Tienes ${pending} pregunta(s) sin responder. ¿Enviar de todos modos?`)) return;
            this.submit();
        },

        submit() {
            if (this.submitting) return;
            this.submitting = true;
            clearInterval(this.timer);
            this.$nextTick(() => this.$refs.form.submit());
        },
    };
}

<?php

use App\Models\Lesson;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Validation\Rule;

use function Livewire\Volt\{layout, mount, state, with};

layout('layouts.app');

state([
    'lesson' => null,
    'quiz' => null,
    'quizTitle' => '',
    'quizDescription' => '',
    'showModal' => false,
    'editingQuestionId' => null,
    'qType' => Question::TYPE_MULTIPLE_CHOICE,
    'questionText' => '',
    'explanation' => '',
    'correctAnswer' => '',
    'order' => 0,
    'options' => [],
    'correctOptionIndex' => null,
    'confirmingDeleteId' => null,
]);

mount(function (Lesson $lesson) {
    abort_unless($lesson->type === Lesson::TYPE_QUIZ, 404);

    $quiz = Quiz::firstOrCreate(
        ['lesson_id' => $lesson->id],
        ['title' => $lesson->title]
    );

    $this->lesson = $lesson;
    $this->quiz = $quiz;
    $this->quizTitle = $quiz->title;
    $this->quizDescription = $quiz->description;
});

with(fn () => [
    'questions' => $this->quiz->questions()->with('options')->get(),
]);

$saveQuizMeta = function () {
    $validated = $this->validate([
        'quizTitle' => ['required', 'string', 'max:255'],
        'quizDescription' => ['nullable', 'string'],
    ]);

    $this->quiz->update([
        'title' => $validated['quizTitle'],
        'description' => $validated['quizDescription'],
    ]);
};

$resetQuestionForm = function () {
    $this->reset(['editingQuestionId', 'questionText', 'explanation', 'correctAnswer', 'order', 'options', 'correctOptionIndex']);
    $this->qType = Question::TYPE_MULTIPLE_CHOICE;
    $this->order = 0;
    $this->options = [['text' => ''], ['text' => '']];
};

$openCreate = function () {
    $this->resetQuestionForm();
    $this->showModal = true;
};

$openEdit = function (Question $question) {
    $question->load('options');

    $this->editingQuestionId = $question->id;
    $this->qType = $question->type;
    $this->questionText = $question->question_text;
    $this->explanation = $question->explanation;
    $this->correctAnswer = $question->correct_answer;
    $this->order = $question->order;

    if ($question->type === Question::TYPE_MULTIPLE_CHOICE) {
        $this->options = $question->options->map(fn ($o) => ['text' => $o->option_text])->all();
        $this->correctOptionIndex = $question->options->search(fn ($o) => $o->is_correct);
    } else {
        $this->options = [['text' => ''], ['text' => '']];
        $this->correctOptionIndex = null;
    }

    $this->showModal = true;
};

$addOption = function () {
    $this->options[] = ['text' => ''];
};

$removeOption = function (int $index) {
    unset($this->options[$index]);
    $this->options = array_values($this->options);

    if ($this->correctOptionIndex === $index) {
        $this->correctOptionIndex = null;
    } elseif ($this->correctOptionIndex > $index) {
        $this->correctOptionIndex--;
    }
};

$saveQuestion = function () {
    $rules = [
        'qType' => ['required', Rule::in(Question::TYPES)],
        'questionText' => ['required', 'string'],
        'explanation' => ['nullable', 'string'],
        'order' => ['integer', 'min:0'],
    ];

    if ($this->qType === Question::TYPE_SHORT_ANSWER) {
        $rules['correctAnswer'] = ['required', 'string', 'max:255'];
    } else {
        $rules['options'] = ['array', 'min:2'];
        $rules['options.*.text'] = ['required', 'string', 'max:255'];
    }

    $validated = $this->validate($rules);

    if ($this->qType === Question::TYPE_MULTIPLE_CHOICE && $this->correctOptionIndex === null) {
        $this->addError('correctOptionIndex', __('Pilih satu jawaban yang benar.'));

        return;
    }

    $questionData = [
        'quiz_id' => $this->quiz->id,
        'type' => $validated['qType'],
        'question_text' => $validated['questionText'],
        'explanation' => $validated['explanation'],
        'order' => $validated['order'],
        'correct_answer' => $this->qType === Question::TYPE_SHORT_ANSWER ? $validated['correctAnswer'] : null,
    ];

    $question = $this->editingQuestionId
        ? tap(Question::findOrFail($this->editingQuestionId))->update($questionData)
        : Question::create($questionData);

    $question->options()->delete();

    if ($this->qType === Question::TYPE_MULTIPLE_CHOICE) {
        foreach ($this->options as $index => $option) {
            $question->options()->create([
                'option_text' => $option['text'],
                'is_correct' => $index === (int) $this->correctOptionIndex,
                'order' => $index,
            ]);
        }
    }

    $this->showModal = false;
    $this->resetQuestionForm();
};

$deleteQuestion = function () {
    if ($this->confirmingDeleteId) {
        Question::find($this->confirmingDeleteId)?->delete();
    }

    $this->confirmingDeleteId = null;
};

?>

<div>
    <x-slot:header>
        <p class="text-sm text-ink-muted">
            <a href="{{ route('admin.lessons.index', $lesson->module_id) }}" wire:navigate class="hover:text-brand font-medium motion-safe:transition-colors duration-150">{{ __('Lessons') }}</a>
            <span class="mx-1">/</span>
            <span class="text-ink-primary font-semibold">{{ $lesson->title }} — {{ __('Quiz') }}</span>
        </p>
    </x-slot:header>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-surface border border-border rounded-2xl p-6">
                <h3 class="font-display font-bold text-lg text-ink-primary mb-4">{{ __('Informasi Quiz') }}</h3>

                <form wire:submit="saveQuizMeta" class="space-y-4">
                    <div>
                        <x-input-label for="quizTitle" :value="__('Judul Quiz')" />
                        <x-text-input wire:model="quizTitle" id="quizTitle" class="block mt-1 w-full" type="text" />
                        <x-input-error :messages="$errors->get('quizTitle')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="quizDescription" :value="__('Instruksi/Deskripsi')" />
                        <textarea wire:model="quizDescription" id="quizDescription" rows="2" class="border-2 border-border bg-surface text-ink-primary focus:border-brand focus:ring-0 rounded-xl shadow-sm block mt-1 w-full"></textarea>
                    </div>

                    <x-primary-button type="submit">{{ __('Simpan') }}</x-primary-button>
                </form>
            </div>

            <div class="bg-surface border border-border rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-display font-bold text-lg text-ink-primary">{{ __('Soal') }} <span class="text-ink-muted font-normal">({{ $questions->count() }})</span></h3>
                    <x-primary-button wire:click="openCreate">{{ __('+ Tambah Soal') }}</x-primary-button>
                </div>

                <div class="space-y-3">
                    @forelse ($questions as $question)
                        <div wire:key="question-{{ $question->id }}" class="border border-border rounded-xl p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <x-badge color="accent" class="uppercase">{{ $question->type === \App\Models\Question::TYPE_MULTIPLE_CHOICE ? __('Pilihan Ganda') : __('Isian Singkat') }}</x-badge>
                                    <p class="mt-2 text-ink-primary">{{ $question->question_text }}</p>

                                    @if ($question->type === \App\Models\Question::TYPE_MULTIPLE_CHOICE)
                                        <ul class="mt-2 space-y-1 text-sm text-ink-secondary">
                                            @foreach ($question->options as $option)
                                                <li class="flex items-center gap-2 {{ $option->is_correct ? 'text-brand font-semibold' : '' }}">
                                                    <span class="w-4 h-4 rounded-full {{ $option->is_correct ? 'bg-brand/20' : 'border border-border' }} flex items-center justify-center text-[9px] shrink-0">{{ $option->is_correct ? '✓' : '' }}</span>
                                                    {{ $option->option_text }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <p class="mt-2 text-sm text-ink-secondary">{{ __('Jawaban benar') }}: <span class="font-mono font-semibold text-brand">{{ $question->correct_answer }}</span></p>
                                    @endif
                                </div>

                                <div class="flex gap-3 text-sm whitespace-nowrap">
                                    <button type="button" wire:click="openEdit({{ $question->id }})" class="font-semibold text-ink-secondary hover:text-ink-primary motion-safe:transition-colors duration-150">{{ __('Edit') }}</button>
                                    <button type="button" wire:click="confirmingDeleteId = {{ $question->id }}" class="font-semibold text-danger hover:opacity-75 motion-safe:transition-opacity duration-150">{{ __('Hapus') }}</button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-ink-secondary">{{ __('Belum ada soal.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div x-show="$wire.showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto px-4 py-6">
        <div class="fixed inset-0 bg-black/50" wire:click="$set('showModal', false)"></div>

        <div class="relative bg-surface border border-border rounded-2xl shadow-2xl max-w-2xl mx-auto p-6 text-ink-primary">
            <h3 class="font-display text-lg font-bold mb-4">
                {{ $editingQuestionId ? __('Edit Soal') : __('Soal Baru') }}
            </h3>

            <form wire:submit="saveQuestion" class="space-y-4 max-h-[70vh] overflow-y-auto pr-1">
                <div>
                    <x-input-label for="qType" :value="__('Tipe Soal')" />
                    <select wire:model.live="qType" id="qType" class="border-2 border-border bg-surface text-ink-primary focus:border-brand focus:ring-0 rounded-xl shadow-sm block mt-1 w-full">
                        <option value="{{ \App\Models\Question::TYPE_MULTIPLE_CHOICE }}">{{ __('Pilihan Ganda') }}</option>
                        <option value="{{ \App\Models\Question::TYPE_SHORT_ANSWER }}">{{ __('Isian Singkat') }}</option>
                    </select>
                </div>

                <div>
                    <x-input-label for="questionText" :value="__('Pertanyaan')" />
                    <textarea wire:model="questionText" id="questionText" rows="2" class="border-2 border-border bg-surface text-ink-primary focus:border-brand focus:ring-0 rounded-xl shadow-sm block mt-1 w-full"></textarea>
                    <x-input-error :messages="$errors->get('questionText')" class="mt-2" />
                </div>

                @if ($qType === \App\Models\Question::TYPE_MULTIPLE_CHOICE)
                    <div class="space-y-2">
                        <x-input-label :value="__('Opsi Jawaban (pilih radio untuk jawaban benar)')" />
                        <x-input-error :messages="$errors->get('correctOptionIndex')" class="mt-1" />

                        @foreach ($options as $index => $option)
                            <div class="flex items-center gap-2" wire:key="option-{{ $index }}">
                                <input type="radio" wire:model="correctOptionIndex" value="{{ $index }}" name="correctOptionIndex" class="border-border text-brand focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                <x-text-input wire:model="options.{{ $index }}.text" class="block w-full" type="text" placeholder="{{ __('Teks opsi') }}" />
                                @if (count($options) > 2)
                                    <button type="button" wire:click="removeOption({{ $index }})" class="text-danger text-sm px-2 hover:opacity-75 transition-opacity duration-150">&times;</button>
                                @endif
                            </div>
                            <x-input-error :messages="$errors->get('options.'.$index.'.text')" class="mt-1" />
                        @endforeach

                        <button type="button" wire:click="addOption" class="text-sm text-ink-secondary hover:text-ink-primary underline transition-colors duration-150">{{ __('+ Tambah Opsi') }}</button>
                    </div>
                @else
                    <div>
                        <x-input-label for="correctAnswer" :value="__('Jawaban Benar (cocok tanpa membedakan huruf besar/kecil)')" />
                        <x-text-input wire:model="correctAnswer" id="correctAnswer" class="block mt-1 w-full" type="text" />
                        <x-input-error :messages="$errors->get('correctAnswer')" class="mt-2" />
                    </div>
                @endif

                <div>
                    <x-input-label for="explanation" :value="__('Pembahasan (opsional, ditampilkan setelah quiz dinilai)')" />
                    <textarea wire:model="explanation" id="explanation" rows="2" class="border-2 border-border bg-surface text-ink-primary focus:border-brand focus:ring-0 rounded-xl shadow-sm block mt-1 w-full"></textarea>
                </div>

                <div>
                    <x-input-label for="order" :value="__('Urutan')" />
                    <x-text-input wire:model="order" id="order" class="block mt-1 w-full" type="number" min="0" />
                </div>

                <div class="flex justify-end gap-3 pt-2 sticky bottom-0 bg-surface">
                    <x-secondary-button type="button" wire:click="$set('showModal', false)">{{ __('Batal') }}</x-secondary-button>
                    <x-primary-button type="submit">{{ __('Simpan') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <x-confirm-delete-modal
        :title="__('Hapus Soal?')"
        :message="__('Soal ini akan dihapus permanen. Tindakan ini tidak bisa dibatalkan.')"
        action="deleteQuestion"
    />
</div>

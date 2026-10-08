<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuestionRequest;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;

class QuestionController extends Controller
{
    public function store(QuestionRequest $request, Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);

        $quiz->questions()->create($request->validated() + [
            'position' => (int) $quiz->questions()->max('position') + 1,
        ]);

        return redirect()->to(route('quizzes.edit', $quiz).'#questions')->with('success', 'Pregunta añadida.');
    }

    public function update(QuestionRequest $request, Question $question): RedirectResponse
    {
        $this->authorize('update', $question->quiz);

        $question->update($request->validated());

        return redirect()->to(route('quizzes.edit', $question->quiz_id).'#question-'.$question->id)->with('success', 'Pregunta actualizada.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $this->authorize('update', $question->quiz);

        $question->delete();

        return back()->with('success', 'Pregunta eliminada.');
    }
}

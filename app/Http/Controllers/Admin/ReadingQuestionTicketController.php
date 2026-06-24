<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReplyReadingQuestionTicketRequest;
use App\Models\ReadingQuestionTicket;
use App\Services\Exam\ReadingQuestionTicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReadingQuestionTicketController extends Controller
{
    public function __construct(private readonly ReadingQuestionTicketService $tickets)
    {
    }

    public function index(Request $request): View
    {
        $status = $request->string('status')->toString() ?: null;

        return view('pages.admin.reading-tickets.index', [
            'tickets' => $this->tickets->paginateForAdmin($status),
            'status' => $status,
            'counts' => $this->tickets->statusCounts(),
        ]);
    }

    public function show(ReadingQuestionTicket $readingTicket): View
    {
        $readingTicket->load(['user:id,name,email', 'question.group.passage', 'test:id,title', 'attempt:id,uuid']);

        return view('pages.admin.reading-tickets.show', [
            'ticket' => $readingTicket,
        ]);
    }

    public function reply(ReplyReadingQuestionTicketRequest $request, ReadingQuestionTicket $readingTicket): RedirectResponse
    {
        $this->tickets->reply($readingTicket, $request->string('admin_reply')->toString());

        return redirect()
            ->route('admin.reading-tickets.show', $readingTicket)
            ->with('status', 'Reply sent.');
    }

    public function resolve(ReadingQuestionTicket $readingTicket): RedirectResponse
    {
        $this->tickets->resolve($readingTicket);

        return redirect()
            ->route('admin.reading-tickets.index', ['status' => 'resolved'])
            ->with('status', 'Ticket resolved.');
    }
}

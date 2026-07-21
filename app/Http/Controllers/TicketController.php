<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketRequest;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function __construct(private readonly TicketService $ticketService) {}

    /**
     * Список тикетов: сотрудник видит свои, админ — все. Вкладки «Новые» / «Обработанные».
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $scope = $request->query('scope') === 'processed' ? 'processed' : 'new';

        $query = Ticket::query()->with('user');
        if (! $user->isAdmin()) {
            $query->forUser($user);
        }

        $tickets = $scope === 'processed'
            ? $query->processed()->latest()->paginate(50)->withQueryString()
            : $query->opened()->latest()->paginate(50)->withQueryString();

        // Счётчики для обоих табов (свой диапазон: свои у сотрудника, все у админа).
        $scopeBase = fn ($q) => $user->isAdmin() ? $q : $q->forUser($user);
        $newCount = $scopeBase(Ticket::query())->opened()->count();
        $processedCount = $scopeBase(Ticket::query())->processed()->count();

        return view('tickets.index', [
            'title' => 'Тикеты',
            'tickets' => $tickets,
            'scope' => $scope,
            'newCount' => $newCount,
            'processedCount' => $processedCount,
        ]);
    }

    /**
     * Форма создания тикета с автоподстановкой URL проблемной страницы.
     */
    public function create(Request $request): View
    {
        return view('tickets.create', [
            'title' => 'Новый тикет',
            'pageUrl' => $request->query('url'),
        ]);
    }

    /**
     * Сохранить тикет от текущего сотрудника.
     */
    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $ticket = $this->ticketService->create(
            $request->user(),
            $request->validated(),
            $request->file('screenshot'),
        );

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Тикет отправлен. Спасибо!');
    }

    /**
     * Просмотр тикета (только автор или администратор). Подгружает автора и админа
     * с ролями для вывода ФИО/роли в карточке. Автор при просмотре тикета с ответом
     * помечает ответ прочитанным (answer_read_at).
     */
    public function show(Request $request, Ticket $ticket): View
    {
        $this->authorize('view', $ticket);

        $ticket->loadMissing(['user.role', 'admin.role']);

        $user = $request->user();
        if ($user->id === $ticket->user_id && $ticket->admin_comment && ! $ticket->answer_read_at) {
            $ticket->markAnswerRead();
        }

        return view('tickets.show', [
            'title' => 'Тикет #'.$ticket->id,
            'ticket' => $ticket,
            'user' => $user,
        ]);
    }

    /**
     * Перевести тикет в работу (только администратор).
     */
    public function start(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('start', $ticket);
        $this->ticketService->start($ticket);

        return redirect()
            ->route('tickets.index', ['scope' => 'new'])
            ->with('success', 'Тикет #'.$ticket->id.' взят в работу');
    }

    /**
     * Закрыть тикет (только администратор) с опциональным комментарием.
     */
    public function close(Request $request, Ticket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'admin_comment' => ['required', 'string', 'max:5000'],
        ]);

        $this->authorize('close', $ticket);
        $this->ticketService->close($ticket, $validated['admin_comment']);

        return redirect()
            ->route('tickets.index', ['scope' => 'new'])
            ->with('success', 'Тикет #'.$ticket->id.' закрыт');
    }

    /**
     * Отправить тикет в корзину (только администратор).
     */
    public function delete(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('delete', $ticket);
        $this->ticketService->delete($ticket);

        return redirect()
            ->route('tickets.index', ['scope' => 'new'])
            ->with('success', 'Тикет #'.$ticket->id.' перемещён в корзину');
    }
}

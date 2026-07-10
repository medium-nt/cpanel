<?php

namespace App\Http\Controllers;

use App\Http\Resources\RatingBoardResource;
use App\Models\Workshop;
use App\Services\RatingBoard\RatingBoardDataService;

class RatingBoardController extends Controller
{
    /**
     * Отдаёт HTML-страницу мотивационной доски рейтинга сотрудников (для телевизора в цехе).
     *
     * @param  string  $token  Токен доступа из URL.
     * @param  string  $workshop  Идентификатор цеха из URL.
     */
    public function index(string $token, string $workshop)
    {
        $this->validateAccess($token);
        $this->resolveWorkshop($workshop);

        return view('rating_board.index', [
            'token' => $token,
            'workshop' => $workshop,
        ]);
    }

    /**
     * Отдаёт JSON с данными доски для polling-обновления экрана.
     *
     * @param  string  $token  Токен доступа из URL.
     * @param  string  $workshop  Идентификатор цеха из URL.
     */
    public function data(string $token, string $workshop, RatingBoardDataService $service)
    {
        $this->validateAccess($token);
        $workshopModel = $this->resolveWorkshop($workshop);

        return RatingBoardResource::make($service->getData($workshopModel->id));
    }

    /**
     * Проверяет токен доступа к доске рейтинга (timing-safe сравнение).
     *
     * @param  string  $token  Токен из URL.
     */
    private function validateAccess(string $token): void
    {
        $expected = (string) config('services.rating_board.token');

        abort_unless($expected !== '' && hash_equals($expected, $token), 403);
    }

    /**
     * Возвращает цех по идентификатору из URL (404 при отсутствии).
     *
     * @param  string  $workshop  Идентификатор цеха из URL.
     */
    private function resolveWorkshop(string $workshop): Workshop
    {
        return Workshop::findOrFail((int) $workshop);
    }
}

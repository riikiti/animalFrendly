<?php

use App\Shared\Infrastructure\Http\Middleware\AssignRequestId;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // Чистый JSON API без сессий/CSRF — авторизация приватных каналов идёт через тот же
    // Bearer-токен (Sanctum), что и весь остальной API, поэтому явно задаём middleware/prefix
    // вместо стандартной группы "web", которую подставила бы просто channels: у withRouting().
    ->withBroadcasting(
        channels: __DIR__.'/../routes/channels.php',
        attributes: ['middleware' => ['auth:sanctum'], 'prefix' => 'api'],
    )
    ->withSchedule(function (Schedule $schedule): void {
        // Эскроу-сделки старше 7 дней без спора — авто-подтверждение, см.
        // docs/rules/04-payments-escrow.md.
        $schedule->command('deals:auto-confirm')->hourly();

        // Автосписание за истёкшие периоды подписки, past_due-разбор, завершение отменённых
        // подписок, см. docs/plan/09-flow-subscriptions.md.
        $schedule->command('subscriptions:process-billing')->daily();

        // Страховка от рассинхрона поискового индекса (адрес владельца изменился задним
        // числом, событийная индексация могла быть пропущена и т.п.).
        $schedule->command('search:reindex')->daily();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(AssignRequestId::class);

        // Это чистый JSON API — веб-роута "login" для редиректа гостей не существует.
        // Без этой строки неаутентифицированный запрос без явного заголовка
        // "Accept: application/json" падает с 500 (route('login') не определён)
        // вместо чистого 401.
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();

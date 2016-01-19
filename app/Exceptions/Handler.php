<?php namespace ImguBox\Exceptions;

use Exception;
use Slack;
use Log;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Foundation\Validation\ValidationException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {
        if (!config('app.debug')) {
            Log::error($e);
        }

        return parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        if ($e->getCode() >= 500) {
            $this->sendNotification($request, $e);
        if (config('app.debug')) {
            return $this->renderExceptionWithWhoops($e);
        }

        if ($e instanceof ModelNotFoundException) {
            return response()->view('errors.404', [], 404);
        }

        return parent::render($request, $e);
    }

    private function sendNotification($request, $e)
    /**
     * Render an exception using Whoops.
     *
     * @param  \Exception $e
     * @return \Illuminate\Http\Response
     */
    protected function renderExceptionWithWhoops(Exception $e)
    {
        $attachment = [
            'fallback' => 'ImguBox Error',
            'text'     => 'ImguBox Error',
            'color'    => '#c0392b',
            'fields' => [
                [
                    'title' => 'Requested URL',
                    'value' => $request->url(),
                    'short' => true
                ],
                [
                    'title' => 'HTTP Code',
                    'value' => $e->getCode(),
                    'short' => true
                ],
                [
                    'title' => 'Exception',
                    'value' => $e->getMessage(),
                    'short' => true
                ],
                [
                    'title' => 'Input',
                    'value' => json_encode($request->all()),
                    'short' => true
                ]
            ]
        ];
        $whoops = new \Whoops\Run;
        $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler());

        Slack::attach($attachment)->send('ImguBox Error');
        return new \Illuminate\Http\Response(
            $whoops->handleException($e),
            $e->getStatusCode(),
            $e->getHeaders()
        );
    }
}

<?php
namespace Whis;

use Dotenv\Dotenv;
use ReflectionClass;
use Throwable;
use Whis\Config\Config;
use Whis\Database\Drivers\DatabaseDriver;
use Whis\Database\Model;
use Whis\Exceptions\HttpNotFoundException;
use Whis\Http\HttpMethod;
use Whis\Http\Request;
use Whis\Http\Response;
use Whis\Routing\Router;
use Whis\Server\Server;
use Whis\Session\Session;
use Whis\Session\SessionStorage;
use Whis\Validation\Exceptions\ValidationException;
use Whis\View\ViewEngine;

class App
{
    public static string $root;
    /**
     * Singleton arquitecture
     */
    public Router $router;

    /**
     * @var Request
     */
    public Request $request;

    /**
     * @var Server
     */
    public Server $server;

    /**
     * @var ViewEngine
     */
    public ViewEngine $viewEngine;

    /**
     * @var Session
     */
    public Session $session;

    public DatabaseDriver $database;

    /**
     * @return Container\string|mixed
     */
    public static function bootstrap(string $root)
    {
        self::$root = $root;

        $app = singleton(self::class);
        return $app
            ->loadConfig()
            ->runServiceProviders("boot")
            ->setHttpHandlers()
            ->setupDatabaseConnection()
            ->runServiceProviders("runtime");
        // echo "<pre>";
        // ($app);
        // ($app->view_engine);
        // echo "</pre>";
        // exit;
        return $app;
    }

    /**
     * @return void
     */
    public function prepareNextRequest()
    {
        if ($this->request->method() == HttpMethod::GET) {
            $this->session->set('_previous', $this->request->uri());
        }
    }

    /**
     * @param Response $response
     * @return void
     */
    protected function terminate(Response $response)
    {
        $this->prepareNextRequest();
        $this->server->sendResponse($response);
        $this->database->close();
        exit();
    }

    protected function runServiceProviders(string $type): self
    {
        foreach (config('providers.' . $type) as $provider) {
            $provider = new $provider();
            $provider->registerServices();
        }
        return $this;
    }

    protected function setHttpHandlers(): self
    {
        $this->router  = singleton(Router::class);
        $this->server  = app(Server::class);
        $this->request = singleton(Request::class, fn() => $this->server->getRequest());
        $this->session = singleton(Session::class, fn() => new Session(app(SessionStorage::class)));

        return $this;
    }

    protected function setupDatabaseConnection(): self
    {
        $this->database = app(DatabaseDriver::class);
        $this->database->connect(
            config("database.connection"),
            config("database.host"),
            config("database.port"),
            config("database.database"),
            config("database.username"),
            config("database.password")
        );
        Model::setDatabaseDriver($this->database);
        return $this;
    }

    protected function loadConfig()
    {
        Dotenv::createImmutable(self::$root)->load();
        Config::load(self::$root . "/config");

        return $this;
    }

    protected function expectsJson(Request $request): bool
    {
        return $request->expectsJson();
    }

    /**
     * @return void
     */
    public function run()
    {
        try {
            $this->terminate($this->router->resolve($this->request));
        } catch (HttpNotFoundException $e) {
            /*
     * Si HttpNotFoundException trae mensaje, probablemente NO es un 404 real de ruta,
     * sino un error interno del motor de vistas:
     *
     * - Template not found
     * - Layout not found
     * - Template file not readable
     * - Template path outside views directory
     *
     * En desarrollo no debemos esconderlo detrás de la vista 404.
     */
            $isDev = str_contains(strtolower((string) config('app.env')), 'dev')
            || str_contains(strtolower((string) config('app.env')), 'local');

            if ($isDev && trim($e->getMessage()) !== '') {
                $this->abort(
                    Response::text(
                        "Stencil/View error:\n\n" .
                        $e->getMessage() . "\n\n" .
                        "File: " . $e->getFile() . "\n" .
                        "Line: " . $e->getLine()
                    )->setStatus(500)
                );
            }

            $this->abort(
                Response::view(
                    'errors/error',
                    [
                        "code" => 404,
                        "text" => "Page not found",
                    ],
                    "error",
                    "Error 404"
                )->setStatus(404)
            );
        } catch (ValidationException $e) {
            if ($this->expectsJson($this->request)) {
                $this->abort(
                    Response::json([
                        'ok'      => false,
                        'message' => 'Revisa los campos enviados.',
                        'errors'  => $e->errors(),
                    ])->setStatus(422)
                );
            }

            $this->abort(back()->withErrors($e->errors(), 422));
        } catch (Throwable $e) {
            $error = new ReflectionClass($e);
            $show  = config('app.error');

            if (! is_dir(self::$root . '/logs')) {
                mkdir(self::$root . '/logs', 0775, true);
            }

            $log = self::$root . '/logs/' . date('Y-m-d') . '.txt';

            $message  = "\t" . date('Y-m-d H:i:s') . ": " . $error->getShortName() . " by " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
            $message .= "\nUncaught exception: '" . get_class($e) . "'";
            $message .= " with message '" . $e->getMessage() . "'";
            $message .= "\nStack trace: " . $e->getTraceAsString();
            $message .= "\nThrown in '" . $e->getFile() . "' on line " . $e->getLine() . "\n\n\n";

            file_put_contents($log, $message, FILE_APPEND);

            if ($this->expectsJson($this->request)) {
                $response = Response::json([
                    'ok'      => false,
                    'error'   => $error->getShortName(),
                    'message' => $show === 'false'
                        ? 'Error interno del servidor.'
                        : $e->getMessage(),
                    'file'    => $show === 'false' ? null : $e->getFile(),
                    'line'    => $show === 'false' ? null : $e->getLine(),
                ])->setStatus(500);

                $this->abort($response);
            }

            if ($show === 'false') {
                $response = Response::view(
                    'errors/error',
                    [
                        'code' => 500,
                        'text' => 'An error has occurred',
                    ],
                    'error', "Error 500"
                )->setStatus(500);

                $this->abort($response);
            }

            $response = Response::text(
                "Error: " . $error->getShortName() . PHP_EOL .
                "Message: " . $e->getMessage() . PHP_EOL .
                "File: " . $e->getFile() . PHP_EOL .
                "Line: " . $e->getLine()
            )->setStatus(500);

            $this->abort($response);
        }

    }

    /**
     * @param Response $response
     * @return void
     */
    public function abort(Response $response)
    {
        $this->terminate($response);
    }
}

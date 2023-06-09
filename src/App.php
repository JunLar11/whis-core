<?php

namespace Whis;

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
use Dotenv\Dotenv;
use ReflectionClass;
use Throwable;

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
        self::$root=$root;

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

    protected function runServiceProviders(string $type):self{
        foreach (config('providers.'.$type) as $provider) {
            $provider = new $provider();
            $provider->registerServices();
        }
        return $this;
    }

    protected function setHttpHandlers():self{
        $this->router = singleton(Router::class);
        $this->server = app(Server::class);
        $this->request = singleton(Request::class,  fn()=>$this->server->getRequest());
        $this->session = singleton(Session::class, fn () => new Session(app(SessionStorage::class)));

        return $this;
    }

    protected function setupDatabaseConnection():self{
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
        Config::load(self::$root."/config");

        return $this;
    }

    /**
     * @return void
     */
    public function run()
    {
        try {
            $this->terminate($this->router->resolve($this->request));
        } catch (HttpNotFoundException $e) {
            //throw new \Exception('No route matched.', 404);
            $this->abort(Response::view('error',["code"=>404,"text"=>"Page not found"])->setStatus(404));
        } catch (ValidationException $e) {
            //throw new \Exception('No route matched.', 422);
            $this->abort(back()->withErrors($e->errors(), 422));
        } catch (Throwable $e) {
            $error = new ReflectionClass($e);
            $show=config('app.error');
            if($show=="false"){
                if(!is_dir(self::$root . '/logs')){
                    mkdir(self::$root . '/logs');
                }
                $log = self::$root . '/logs/' . date('Y-m-d') . '.txt';
                $message="\t".date('Y-m-d H:i:s').": ".$error->getShortName() . " by ". $_SERVER['REMOTE_ADDR'];
                $message .= "\nUncaught exception: '" . get_class($e) . "'";
                $message .= " with message '" . $e->getMessage() . "'";
                $message .= "\nStack trace: " . $e->getTraceAsString();
                $message .= "\nThrown in '" . $e->getFile() . "' on line " . $e->getLine()."\n\n\n";
                file_put_contents($log, $message, FILE_APPEND);
                //error_log($message);
                $response = view('errors/error',["code"=>500, "text"=>"An error has ocurred"]);
                
            }else{
                $response = json([
                    "error" => $error->getShortName(),
                    "message" => $e->getMessage(),
                    "file" => $e->getFile(),
                    "line" => $e->getLine(),
                    "trace" => $e->getTraceAsString()
                ]);
            }
            
            

            //throw new \Exception('No route matched.', 500);
            $this->abort($response->setStatus(500));
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
